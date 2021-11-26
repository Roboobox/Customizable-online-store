<?php
header('Content-type: application/json');
include_once '../conn.php';
require_once('../objects/Product.php');

const PRODUCT_TAB_NAME_DESCRIPTION = "description";
const PRODUCT_TAB_NAME_SPECIFICATIONS = "specifications";

// Small sql to only select search matching products
$liteProductSql = "SELECT id FROM `product` P WHERE (P.name LIKE CONCAT('%', :productName, '%') OR :productName is NULL) AND P.is_deleted = (0)";

// Main product selection sql
$productSql = "
        SELECT P.*, I.quantity, D.discount_percent, C.name AS category FROM `product` P
        LEFT JOIN product_inventory I ON P.inventory_id = I.id
        LEFT JOIN product_category C ON P.category_id = C.id
        LEFT JOIN product_discount D ON D.id = (SELECT MAX(PD.id) FROM product_discount PD WHERE PD.product_id = P.id AND PD.is_active = 1 AND (NOW() between PD.starting_at AND PD.ending_at))
        WHERE (P.name LIKE CONCAT('%', :productName, '%') OR :productName is NULL) AND P.is_deleted = (0)
";

$specFilters = array();
$productFilterSql = "";
// Check if there are any filters applied
if (isset($_POST['filterParams'])) {
    $specFilterData = getSpecFilters($_POST['filterParams']);
    $specFilters = $specFilterData['specFilters'];
    $productFilterSql = $specFilterData['sql'];
}

// Add selected filter sql to product sql
$productSql .= $productFilterSql;

// Sort changing based on user choice
if (!isset($_POST['sort']) || empty($_POST['sort']) || $_POST['sort'] == "A to Z") {
    $productSql .= ' ORDER BY P.name';
} else if ($_POST['sort'] == "Z to A") {
    $productSql .= ' ORDER BY P.name DESC';
} else if ($_POST['sort'] == "Price asc") {
    $productSql .= ' ORDER BY IFNULL((P.price - P.price * (D.discount_percent / 100)), P.price)';
} else if ($_POST['sort'] == "Price desc") {
    $productSql .= ' ORDER BY IFNULL((P.price - P.price * (D.discount_percent / 100)), P.price) DESC';
} else {
    $productSql .= ' ORDER BY P.name';
}

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
            $stmt->bindValue(':spec' . ($cnt + 1), $info);
            $cnt += 2;
        }
    }
}
$stmt->execute();
$stmtLite->execute();
$productRows = $stmt->fetchAll();

$products = array();
$allProductData = array();
$productSpecs = array();
$specProductCount = array();

// Gets product data and stores it into product object
foreach ($productRows as $row) {
    $product = new Product();
    $product->getProductDataFromRow($row);

    // Sql to select product specifications and photos
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
    // Save product object in products array
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
        $allProductData[$productRow['id']][] = $row;
        if (!isset($productSpecs[$row['label']]) || !in_array($row['info'], $productSpecs[$row['label']], false)) {
            $productSpecs[$row['label']][] = $row['info'];
        }
    }
}

// Get each filter (specification) returned product count when it will be added
foreach ($productSpecs as $specName => $specValues) {
    if ($specValues > 1) {
        foreach ($specValues as $value) {
            // Check for currently applied filters
            $params = $_POST['filterParams'] ?? array();
            $paramInArray = false;
            foreach ($params as $param) {
                if ($param[1] == $value) {
                    $paramInArray = true;
                    break;
                }
            }
            // If filter is not currently applied, add it to filter (specification) parameters as it would be if it were applied
            if (!$paramInArray) {
                $params[] = array("fs_" . $specName . "_0", $value);
            }
            // Get returned product results with filter added
            $specSql = getSpecFilters($params)['sql'];
            $productFilterSql = "SELECT COUNT(id) as Count FROM product P WHERE (P.name LIKE CONCAT('%', :productName, '%') OR :productName is NULL) AND P.is_deleted = (0) AND P.id IN (SELECT product_id FROM product_specification WHERE label = \"".$specName."\" AND info = \"".$value. "\") " . $specSql;
            $productFilterStmt = $conn->prepare($productFilterSql);
            $cnt = 1;
            // Bind currently applied filter (specification) variables to statement
            foreach ($specFilters as $label => $infos) {
                foreach ($infos as $info) {
                    $productFilterStmt->bindValue(':spec' . $cnt, $label);
                    $productFilterStmt->bindValue(':spec' . ($cnt + 1), $info);
                    $cnt += 2;
                }
            }
            // Bind new filter to statement
            if (!$paramInArray) {
                $productFilterStmt->bindValue(':spec' . $cnt, $specName);
                $productFilterStmt->bindValue(':spec' . ($cnt + 1), $value);
            }
            // Bind search phrase to statement
            if (isset($_POST['q']) && !empty($_POST['q'])) {
                $productFilterStmt->bindParam(':productName', $_POST['q']);
            } else {
                $productFilterStmt->bindValue(':productName', null);
            }
            $productFilterStmt->execute();
            // Save each filter returned items count
            $specProductCount[$specName][$value] = $productFilterStmt->fetch()['Count'];
        }
    }
}

function getSpecFilters(array $filterParams): array
{
    $productFilterSql = "";
    $specFilters = array();
    // Check if there are any filters applied
    if (isset($filterParams)) {
        foreach ($filterParams as $param) {
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
            $productFilterSql .= ' AND ';
            $counter = 1;
            $i = 0;
            foreach ($specFilters as $label => $infos) {
                if ($i > 0) {
                    $productFilterSql .= ' AND ';
                }
                $productFilterSql .= 'EXISTS (SELECT id FROM product_specification PS WHERE P.id = PS.product_id AND (';
                $i++;
                $k = 0;
                foreach ($infos as $info) {
                    if ($k > 0) {
                        $productFilterSql .= ' OR ';
                    }
                    $productFilterSql .= '(PS.label = :spec' . $counter . ' AND PS.info = :spec' . ($counter + 1) . ')';
                    $counter += 2;
                    $k++;
                }
                $productFilterSql .= ')) ';
            }
        }
    }
    return array('sql' => $productFilterSql, 'specFilters' => $specFilters);
}

// Create specification HTML code which is filled with data received from database
function generateSpecificationHtml(array $productSpecs, array $specProductCount): string
{
    $specHtml = '<div class="d-block d-lg-none border-bottom w-100 mb-3 pb-2 text-center"><div class="fw-bold fs-5 d-inline-block">Product filters</div><a id="mob_filter_hide" class="float-end text-dark"><i class="fas fa-times-circle fs-2 align-middle"></i></a></div>';
    $i = 0;
    foreach ($productSpecs as $specName => $specValues) {
        if (count($specValues) > 1) {
            if ($i > 0) {
                $specHtml .= '<hr class="my-2">';
            }
            $specHtml .= '
            <div class="filter-option">
            <h6 class="mb-3">' . htmlspecialchars($specName) . '</h6>
            <div class="filter-buttons">';

            foreach ($specValues as $value) {
                $specHtml .= '
                <div class="form-check">
                <input '.((!isset($specProductCount[$specName][$value]) || $specProductCount[$specName][$value] == 0) ? 'disabled' : '').' class="form-check-input filter-input-button" type="checkbox" value="' . htmlspecialchars($value) . '" name="fs_' . htmlspecialchars($specName) . '" id="check' . htmlspecialchars($specName) . htmlspecialchars($value) . '">
                <label class="form-check-label" for="check' . htmlspecialchars($specName) . htmlspecialchars($value) . '">
                    ' . htmlspecialchars($value) . '<span class="text-muted">' . ((!isset($specProductCount[$specName][$value]) || $specProductCount[$specName][$value] == 0) ? '' : ' ('.htmlspecialchars($specProductCount[$specName][$value]).')') . '</span>
                </label>
                </div>';
            }

            $specHtml .= '
            </div>
            </div>';
            $i++;
        }
    }
    if ($i > 0) {
        return $specHtml;
    }
    return '';
}

// Create product (LIST/ROW view) HTML code which is filled with data received from database
function generateProductRVHtml(array $products): string
{
    $productsHtml = '<div class="row product-row">';
    if (!empty($products)) {
        $productsHtml .= '<div class="table-responsive p-sm-2 p-0"><table class="table table-bordered text-center bg-white">
                    <tr>
                        <th>Info</th>
                        <th class="d-sm-table-cell d-none">Category</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th class="d-none d-sm-table-cell">Quantity</th>
                        <th class="d-table-cell d-sm-none">Qty</th>
                        <th class="d-sm-table-cell d-none">Total</th>
                        <th></th>
                    </tr>';
        foreach ($products as $product) {
            $productsHtml .= '<tr class="product mb-2 mb-sm-3 px-1 px-sm-2" data-product-id="' . htmlspecialchars($product->id) . '">';
            $productsHtml .= '<td class="row-view-image" onclick="objShop.openProductModal(' . htmlspecialchars($product->id) . ')" data-bs-toggle="modal" data-bs-target="#productModal"><i class="far fa-eye"></i></td>';
            $productsHtml .= '<td class="text-start d-sm-table-cell d-none">' . htmlspecialchars($product->category) . '</td>';
            $productsHtml .= '<td class="text-start">' . htmlspecialchars($product->name) . '</td>';
            $productsHtml .= '<td class="retail-price-text fs-6 '.(($product->discountPercent > 0) ? "row-price-sale" : "").'"><b>' . htmlspecialchars($product->discountPrice) . ' €</b></td>';
            $productsHtml .= '<td>';
            $productsHtml .= '<div class="quantity-container">';
            $productsHtml .= '<div class="quantity-picker-container">';
            $productsHtml .= '<div onclick="objShop.changeQuantityPickerAmount(0,' . htmlspecialchars($product->id) . ')" class="minus d-md-flex d-none"><i class="fas fa-minus"></i></div>';
            $productsHtml .= '<input class="form-control quantity-picker-input" data-product-id="' . htmlspecialchars($product->id) . '" name="cart_quantity" type="number" value="1" min="1" onchange="objShop.validateQuantity(' . htmlspecialchars($product->id) . ')">';
            $productsHtml .= '<input type="hidden" value="' . htmlspecialchars($product->id) . '" name="cart_product_id">';
            $productsHtml .= '<div onclick="objShop.changeQuantityPickerAmount(1,' . htmlspecialchars($product->id) . ')" class="plus d-md-flex d-none"><i class="fas fa-plus"></i></div>';
            $productsHtml .= '</div><div class="total-price-text d-block d-sm-none">' . htmlspecialchars($product->discountPrice()) . ' €</td>';
            $productsHtml .= '</div>';
            $productsHtml .= '</td>';
            $productsHtml .= '<td class="total-price-text fs-6 d-sm-table-cell d-none">' . htmlspecialchars($product->discountPrice) . ' €</td>';
            $productsHtml .= '<td>
                            <button class="btn-add-cart" data-product="' . htmlspecialchars($product->id) . '">
                                <span class="btn-text">
                                    <span class="d-md-flex d-none justify-content-center">Add to cart</span>
                                    <span class="d-md-none d-flex"><i class="fas fa-shopping-cart"></i></span>
                                </span>
                                <i class="fas fa-spinner fa-spin loading" aria-hidden="true"></i>
                            </button>
                        </td>';
            $productsHtml .= '<tr>';

        }
        $productsHtml .= '</table></div>';
    } else {
        $productsHtml .= '
        <div class="col-12 px-3">
        <div class="p-3 border shadow-sm fw-bold text-center">
            <div><i class="fas fa-search fs-1 mb-3"></i></div>
            <div class="fs-5">No products were found that match criteria</div>
        </div>
        </div>';
    }
    $productsHtml .= '</div>';
    return $productsHtml;
}

// Create product (GRID view) HTML code which is filled with data received from database
function generateProductHtml(array $products): string
{
    $productsHtml = '<div class="row product-row row-cols-xl-3 row-cols-lg-2 row-cols-sm-2 row-cols-2">';
    if (!empty($products)) {
        foreach ($products as $product) {
            $productsHtml .= '<div class="col mb-2 mb-sm-3 px-1 px-sm-2">';
            $productsHtml .= '<div class="product card product-card h-100 shadow-sm" data-product-id="' . htmlspecialchars($product->id) . '">';
            $productsHtml .= '<div class="card-image-container p-1 p-sm-3 border-bottom" onclick="objShop.openProductModal(' . htmlspecialchars($product->id) . ')" data-bs-toggle="modal" data-bs-target="#productModal">';
            $productsHtml .= '<img src="test_images/' . htmlspecialchars($product->photoPath) . '" class="card-img-top" alt="Product photo">';
            $productsHtml .= '</div>';
            $productsHtml .= '<div class="card-body">';
            $productsHtml .= '<span class="card-subtitle fw-light text-uppercase text-muted">' . htmlspecialchars($product->category) . '</span>';
            $productsHtml .= '<h5 class="card-title mb-3" onclick="objShop.openProductModal(' . htmlspecialchars($product->id) . ')" data-bs-toggle="modal" data-bs-target="#productModal">' . htmlspecialchars($product->name) . '</h5>';
            $productsHtml .= '<div class="card-text text-muted d-none d-sm-block">';
            $productsHtml .= '<ul class="card-info-list">';

            $specCounter = 1;
            foreach ($product->specifications as $label => $name) {
                if ($specCounter > 3) {
                    $productsHtml .= '<li><a class="link-secondary show-more" data-bs-toggle="modal" data-bs-target="#productModal" onclick="objShop.openProductModal(' . htmlspecialchars($product->id) . ', \'' . PRODUCT_TAB_NAME_SPECIFICATIONS . '\')">Show more...</a on></li>';
                    break;
                }
                $productsHtml .= '<li><i class="far fa-circle"></i><span style="white-space: nowrap">' . htmlspecialchars($label) . '</span><span class="card-info-list-text fw-bold ms-1 text-body">' . htmlspecialchars($name) . '</span></li>';
                $specCounter++;
            }

            $productsHtml .= '</ul></div>';
            $productsHtml .= '<hr class="mb-2 d-none d-sm-block" style="width: auto;margin: 0 -1rem">';
            $productsHtml .= '<div class="card-price">';
            $productsHtml .= '<div class="row row-cols-1 row-cols-md-2 order-product-container">';
            $productsHtml .= '<div class="col">';

            $productsHtml .= '<div class="quantity-container mb-2">';
            $productsHtml .= '<div class="text-muted mb-1">Qty</div>';
            $productsHtml .= '<div class="quantity-picker-container">';
            $productsHtml .= '<div onclick="objShop.changeQuantityPickerAmount(0,' . htmlspecialchars($product->id) . ')" class="minus"><i class="fas fa-minus"></i></div>';
            $productsHtml .= '<input class="form-control quantity-picker-input" data-product-id="' . htmlspecialchars($product->id) . '" name="cart_quantity" type="number" value="1" min="1" onchange="objShop.validateQuantity(' . htmlspecialchars($product->id) . ')">';
            $productsHtml .= '<input type="hidden" value="' . htmlspecialchars($product->id) . '" name="cart_product_id">';
            $productsHtml .= '<div onclick="objShop.changeQuantityPickerAmount(1,' . htmlspecialchars($product->id) . ')" class="plus"><i class="fas fa-plus"></i></div>';
            $productsHtml .= '</div></div>';

            $productsHtml .= '<div class="retail-price-container">';
            $productsHtml .= '<span class="retail-price-label text-muted">Price</span>';
            if ($product->discountPercent > 0) {
                $productsHtml .= '<span class="retail-price-text fw-bold price-sale">' . htmlspecialchars($product->price) . ' €</span>';
                $productsHtml .= '<span class="retail-price-text fw-bold price-new">' . htmlspecialchars($product->discountPrice) . ' €</span>';
            } else {
                $productsHtml .= '<span class="retail-price-text fw-bold">' . htmlspecialchars($product->price) . ' €</span>';
            }
            $productsHtml .= '</div>';
            $productsHtml .= '</div>';
            $productsHtml .= '<div class="col">';

            $productsHtml .= '<button class="btn-add-cart mb-2" data-product="' . htmlspecialchars($product->id) . '"><span class="btn-text">Add to cart</span><i class="fas fa-spinner fa-spin loading"></i></button>';
            $productsHtml .= '<div class="total-price">
                          <div class="text-muted">Total</div>
                          <span class="fw-bold total-price-text">' . htmlspecialchars($product->discountPrice) . ' €</span>
                          </div>';

            $productsHtml .= '</div>';
            $productsHtml .= '</div></div></div></div></div>';
        }
        $productsHtml .= "</div>";
    } else {
        $productsHtml .= '
        <div class="col-12 px-3">
        <div class="p-3 border shadow-sm fw-bold text-center">
            <div><i class="fas fa-search fs-1 mb-3"></i></div>
            <div class="fs-5">No products were found that match criteria</div>
        </div>
        </div>';
    }
    $productsHtml .= '</div>';
    return $productsHtml;
}

echo json_encode(array('specs' => $productSpecs, 'products' => $products, 'product_html' => (($_POST['layout'] == "list") ? generateProductRVHtml($products) : generateProductHtml($products)), 'spec_html' => generateSpecificationHtml($productSpecs, $specProductCount)));
