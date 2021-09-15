<?php
include_once '../conn.php';
require_once('../objects/Product.php');

const PRODUCT_TAB_NAME_DESCRIPTION = "description";
const PRODUCT_TAB_NAME_SPECIFICATIONS = "specifications";
    
$liteProductSql = "SELECT id FROM product P WHERE (P.name LIKE CONCAT('%', :productName, '%') OR :productName is NULL)";

$productSql = "
        SELECT P.*, I.quantity, D.discount_percent, C.name AS category FROM `product` P
        LEFT JOIN product_inventory I ON P.inventory_id = I.id
        LEFT JOIN product_category C ON P.category_id = C.id
        LEFT JOIN product_discount D ON D.id = (SELECT MAX(PD.id) FROM product_discount PD WHERE PD.product_id = P.id AND PD.is_active = 1 AND (NOW() between PD.starting_at AND PD.ending_at))
        WHERE (P.name LIKE CONCAT('%', :productName, '%') OR :productName is NULL)
";

$specFilters = array();
// Check if there are any filters applied
if (isset($_POST['filterParams'])) {
    foreach ($_POST['filterParams'] as $param) {
        // Check if valid filter
        if (empty($param[1]) || strlen($param[0]) < 6) {
            continue;
        }
        // Removes 'fs_' from start and '_X' numbering from end
        $dbLabel = substr(urldecode($param[0]), 3, -2);
        // Adds filter to array {['label'] = array(infos)}
        $specFilters[$dbLabel][] = urldecode($param[1]);
    }
    
    if (!empty($specFilters)) {
        // Creates placeholders in sql for binding filter values
        $productSql .= ' AND ';
        $counter = 1;
        $i = 0;
        foreach ($specFilters as $label => $infos) {
            if ($i > 0) {
                $productSql .= ' AND ';
            }
            $productSql .= 'EXISTS (SELECT id FROM product_specification PS WHERE P.id = PS.product_id AND (';
            $i++;
            $k = 0;
            foreach ($infos as $info) {
                if ($k > 0) {
                    $productSql .= ' OR ';
                }
                $productSql .= '(PS.label = :spec'.$counter.' AND PS.info = :spec'.($counter+1).')';
                $counter += 2;
                $k++;
            }
            $productSql .= ')) ';
        }
    }
}

$productSql .= ' ORDER BY P.name';
$stmt = $conn->prepare($productSql);
$stmtLite = $conn->prepare($liteProductSql);
// Binds search input value to placeholder
if (isset($_POST['q']) && !empty($_POST['q'])) {
    $filteredSearch = filter_input(INPUT_POST, 'q', FILTER_SANITIZE_SPECIAL_CHARS);
    $stmtLite->bindParam(':productName', $filteredSearch);
    $stmt->bindParam(':productName', $filteredSearch);
} else {
    $stmtLite->bindValue(':productName', null);
    $stmt->bindValue(':productName', null);
}
// Binds filter values to placeholders
if (!empty($specFilters)) {
    $cnt = 1;
    foreach ($specFilters as $label => $infos) {
        foreach ($infos as $info) {
            $stmt->bindValue(':spec' . $cnt, $label);
            $stmt->bindValue(':spec' . ($cnt+1), $info);
            $cnt += 2;
        }
    }
}
$stmt->execute();
$stmtLite->execute();
$productRows = $stmt->fetchAll();

$products = array();
$productSpecs = array();
// Gets product data and stores it into product object
foreach ($productRows as $row) {
    $product = new Product();
    $product->getProductDataFromRow($row);

    $productSpecsSql = "SELECT label, info FROM product_specification WHERE product_id = :productId";
    $productPhotoSql = "SELECT photo_path FROM product_photo WHERE product_id = :productId";

    $stmt = $conn->prepare($productSpecsSql);
    $stmt->bindParam(':productId', $row['id']);
    $stmtPhoto = $conn->prepare($productPhotoSql);
    $stmtPhoto->bindParam(':productId', $row['id']);

    $stmt->execute();
    $specifications = $stmt->fetchAll();
    $stmtPhoto->execute();
    $photos = $stmtPhoto->fetchAll();

    $product->getSpecifactions($specifications);
    $product->getPhotos($photos);

    $products[$row['id']] = $product;
}

// Get product specifications for filters
foreach ($stmtLite->fetchAll() as $productRow) {
    $productSpecsSql = "SELECT label, info FROM product_specification WHERE product_id = :productId";
    $stmt = $conn->prepare($productSpecsSql);
    $stmt->bindParam(':productId', $productRow['id']);
    $stmt->execute();
    $specifications = $stmt->fetchAll();
    // Create array with specifications for filters
    foreach ($specifications as $row) {
        if (!isset($productSpecs[$row['label']]) || !in_array($row['info'], $productSpecs[$row['label']], false)) {
            $productSpecs[$row['label']][] = $row['info'];
        }
    }
}

function generateSpecificationHtml(array $productSpecs): string
{
    $specHtml = '';
    $i = 0;
    foreach ($productSpecs as $specName => $specValues) {
        if(count($specValues) > 1) {
            if ($i > 0) {
                $specHtml .= '<hr class="my-2">';
            }
            $specHtml .= '
            <div class="filter-option">
            <h6 class="mb-3">' . $specName . '</h6>
            <div class="filter-buttons">';

            foreach ($specValues as $value) {
                $specHtml .= '
                <div class="form-check">
                <input class="form-check-input filter-input-button" type="checkbox" value="' . $value . '" name="fs_' . $specName . '" id="check' . $specName . $value . '">
                <label class="form-check-label" for="check' . $specName . $value . '">
                    ' . $value . '
                </label>
                </div>';
            }

            $specHtml .= '
            </div>
            </div>';
            $i++;
        }
    }
    return $specHtml;
}   

function generateProductHtml(array $products): string {
    $productsHtml = "";
    if (!empty($products)) {
        foreach ($products as $product) {
            $productsHtml .= '<div class="col mb-2 mb-sm-3 px-1 px-sm-2">';
            $productsHtml .= '<div class="card product-card h-100 shadow-sm" data-product-id="' . $product->id . '">';
            $productsHtml .= '<div class="card-image-container p-1 p-sm-3 border-bottom" onclick="objShop.openProductModal(' . $product->id . ')" data-bs-toggle="modal" data-bs-target="#productModal">';
            $productsHtml .= '<img src="test_images/' . $product->photoPath . '" class="card-img-top" alt="Product photo">';
            $productsHtml .= '</div>';
            $productsHtml .= '<div class="card-body">';
            $productsHtml .= '<span class="card-subtitle fw-light text-uppercase text-muted">' . $product->category . '</span>';
            $productsHtml .= '<h5 class="card-title mb-3" onclick="objShop.openProductModal(' . $product->id . ')" data-bs-toggle="modal" data-bs-target="#productModal">' . $product->name . '</h5>';
            $productsHtml .= '<div class="card-text text-muted d-none d-sm-block">';
            $productsHtml .= '<ul class="card-info-list">';

            $specCounter = 1;
            foreach ($product->specifications as $label => $name) {
                if ($specCounter > 3) {
                    $productsHtml .= '<li><a class="link-secondary show-more" data-bs-toggle="modal" data-bs-target="#productModal" onclick="objShop.openProductModal(' . $product->id . ', \'' . PRODUCT_TAB_NAME_SPECIFICATIONS . '\')">Show more...</a on></li>';
                    break;
                }
                $productsHtml .= '<li><i class="far fa-circle"></i><span>' . $label . '</span><span class="card-info-list-text fw-bold ms-1 text-body">' . $name . '</span></li>';
                $specCounter++;
            }

            $productsHtml .= '</ul></div>';
            $productsHtml .= '<hr class="mb-2" style="width: auto;margin: 0 -1rem">';
            $productsHtml .= '<div class="card-price">';
            $productsHtml .= '<form method="post" action="/cart_process.php">';
            $productsHtml .= '<div class="row row-cols-1 row-cols-md-2 order-product-container">';
            $productsHtml .= '<div class="col">';
            
            $productsHtml .= '<div class="quantity-container mb-2">';
            $productsHtml .= '<div class="text-muted mb-1">Qty</div>';
            $productsHtml .= '<div class="quantity-picker-container">';
            $productsHtml .= '<div onclick="objShop.changeQuantityPickerAmount(0,' . $product->id . ')" class="minus"><i class="fas fa-minus"></i></div>';
            $productsHtml .= '<input class="form-control quantity-picker-input" name="cart_quantity" type="number" value="1" min="1" max="' . $product->inventoryAmount . '" onchange="objShop.validateQuantity(' . $product->id . ')">';
            $productsHtml .= '<input type="hidden" value="' . $product->id . '" name="cart_product_id">';
            $productsHtml .= '<div onclick="objShop.changeQuantityPickerAmount(1,' . $product->id . ')" class="plus"><i class="fas fa-plus"></i></div>';
            $productsHtml .= '</div></div>';
            
            $productsHtml .= '<div class="retail-price-container">';
            $productsHtml .= '<span class="retail-price-label text-muted">Price</span>';
            if ($product->discountPercent > 0) {
                $productsHtml .= '<span class="retail-price-text fw-bold price-sale">' . $product->price . ' €</span>';
                $productsHtml .= '<span class="retail-price-text fw-bold price-new">' . $product->discountPrice . ' €</span>';
            } else {
                $productsHtml .= '<span class="retail-price-text fw-bold">' . $product->price . ' €</span>';
            }
            $productsHtml .= '</div>';
            $productsHtml .= '</div>';
            $productsHtml .= '<div class="col">';
            
            $productsHtml .= '<button type="submit" class="btn-add-cart mb-2">Add to cart</button>';
            $productsHtml .= '<div class="total-price">
                          <div class="text-muted">Total</div>
                          <span class="fw-bold total-price-text">'.$product->price.' €</span>
                          </div>';
            
            $productsHtml .= '</div></div>';
            $productsHtml .= '</form></div></div></div></div></div>';
        }
        $productsHtml .= "</div>";
    }
    else {
        $productsHtml .= '
        <div class="col-12 px-3">
        <div class="p-3 border shadow-sm fw-bold text-center">
            <div><i class="fas fa-search fs-1 mb-3"></i></div>
            <div class="fs-5">No products were found that match criteria</div>
        </div>
        </div>';
    }
    return $productsHtml;
}


echo json_encode(array('specs' => $productSpecs, 'products' => $products ,'product_html' => generateProductHtml($products), 'spec_html' => generateSpecificationHtml($productSpecs)));
