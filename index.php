<?php
$tab = 'Products';

//    include_once 'conn.php';
//    require_once('objects/Product.php');
//    
//    const PRODUCT_TAB_NAME_DESCRIPTION = "description";
//    const PRODUCT_TAB_NAME_SPECIFICATIONS = "specifications";
//    
//    $productSql = "
//        SELECT P.*, I.quantity, D.discount_percent, C.name AS category, (SELECT photo_path FROM product_photo PP WHERE P.id = PP.product_id LIMIT 1) AS photo_path  FROM `product` P
//        LEFT JOIN product_inventory I ON P.inventory_id = I.id
//        LEFT JOIN product_category C ON P.category_id = C.id
//        LEFT JOIN product_specification PS ON P.id = PS.product_id
//        LEFT JOIN product_discount D ON D.id = (SELECT MAX(PD.id) FROM product_discount PD WHERE PD.product_id = P.id AND PD.is_active = 1 AND (NOW() between PD.starting_at AND PD.ending_at))
//        WHERE P.name LIKE CONCAT('%', ?, '%') OR ? is NULL
//    ";
//    
//    $specFilters = array();
//
//    foreach ($_GET as $key => $value) {
//        if (strpos($key, 'fs_') === 0) {
//            // Removes 'fs_'
//            $specLabel = substr($key, 3);
//            $specFilters[$specLabel] = $value;
//            //Create template for filter values to be inserted as parameters in sql
//            $productSql .= ' AND (PS.label LIKE ? AND PS.info LIKE ?)';
//        }
//    }
//    
//    $productSql .= ' ORDER BY P.name';
//    
//    $stmt = $conn->prepare($productSql);
//    
//    if (isset($_GET['q']) && $_SERVER['REQUEST_METHOD'] === "GET" && !empty($_GET['q']))
//    {
//        $filteredSearch = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS);
//        $stmt->bindParam(1, $filteredSearch);
//        $stmt->bindParam(2, $filteredSearch);
//    }
//    else
//    {
//        $stmt->bindValue(1, null);
//        $stmt->bindValue(2, null);
//    }
//    
//    // Fill specification filter templates in sql
//    $i = 3;
//    foreach ($specFilters as $label => $value) {
//        $stmt->bindValue($i, $label);
//        $stmt->bindValue($i+1, $value);
//        $i += 2;
//    }
//    
//    $stmt->execute();
//    
//    $productRows = $stmt->fetchAll();
//    
//    $products = array();
//    $productSpecs = array();
//    
//    foreach ($productRows as $row)
//    {
//        $product = new Product();
//        $product->getProductDataFromRow($row);
//        
//        $productSpecsSql = "SELECT label, info FROM product_specification WHERE product_id = :productId";
//        
//        
//        $stmt = $conn->prepare($productSpecsSql);
//        $stmt->bindParam(':productId', $row['id']);
//        
//        $stmt->execute();
//        $specifications = $stmt->fetchAll();
//        
//        $product->getSpecifactions($specifications);
//        
//        // Create array with specifications for filters
//        foreach ($product->specifications as $spec => $value) {
//            if (!isset($productSpecs[$spec]) || !in_array($value, $productSpecs[$spec], false)){
//                $productSpecs[$spec][] = $value;
//            }
//        }
//        
//        $products[$row['id']] = $product;
//    }
?>

<?php
include_once 'head.php';
include_once 'header.php';
?>
<script type="text/javascript">
    $( document ).ready(function() {
        objShop.init();
    });
</script>
<div class="container mb-5">
    <div class="row">
        <h3 class="w-100 mt-5 mb-5">Products</h3>
    </div>
    <div class="row">
        <div class="col-3 d-none d-sm-block">
            <div class="spec-filters row pe-5">
<!--                <?php
/*                foreach ($productSpecs as $specName => $specValues) {
                    if(count($specValues) > 1) {
                */?>    
                <div class="filter-option">
                    <h6 class="mb-3"><?/*=$specName*/?></h6>
                    <div class="filter-buttons">
                        <?php
/*                        foreach ($specValues as $value) {
                        */?>
                            <div class="form-check">
                            <input class="form-check-input filter-input-button" type="checkbox" value="<?/*=$value*/?>" name="fs_<?/*=$specName*/?>" id="check<?/*=$specName.$value*/?>">
                            <label class="form-check-label" for="check<?/*=$specName.$value*/?>">
                                <?/*=$value*/?>
                            </label>
                        </div>
                        <?php
/*                        }
                        */?>
                    </div>
                </div>
                <hr class="my-2">
                --><?php /* 
                    }
                }
                */?>
            </div>
        </div>
        <div class="col product-container">
            <div class="col-12 px-3" id="products_loading">
                <div class="p-3 border shadow-sm fw-bold text-center">
                    <div><i class="fas fa-spinner fa-spin fs-1 my-3 text-muted"></i></div>
                </div>
            </div>
            <div class="row row-cols-xl-3 row-cols-lg-2 row-cols-sm-2 row-cols-2">
<!--                
                <?php
/*                
                foreach ($products as $product)
                {
                */?>
                    <div class="col mb-2 mb-sm-3 px-1 px-sm-2">
                        <div class="card product-card h-100 shadow-sm" data-product-id="<?/*=$product->id*/?>">
                            <div class="card-image-container p-1 p-sm-3">
                                <img src="test_images/<?/*=$product->photoPath*/?>" class="card-img-top" alt="...">
                            </div>
                            <div class="card-body">
                                <span class="card-subtitle fw-light text-uppercase text-muted"><?/*=$product->category*/?></span>
                                <h5 class="card-title mb-3" onclick="objShop.openProductModal(<?/*=$product->id*/?>)" data-bs-toggle="modal"
                                    data-bs-target="#productModal"><?/*=$product->name*/?></h5>
                                <div class="card-text text-muted d-none d-sm-block mb-4">
                                    <ul class="card-info-list">
                                        <?php
/*                                            $specCounter = 1;
                                            foreach ($product->specifications as $label => $name)
                                            {
                                                if($specCounter > 3) {
                                                    */?>
                                                    <li><a class="link-secondary" data-bs-toggle="modal" data-bs-target="#productModal" onclick="objShop.openProductModal(<?/*=$product->id*/?>, '<?/*=PRODUCT_TAB_NAME_SPECIFICATIONS*/?>')">Show more...</a on></li>
                                                    <?php
/*                                                    break;
                                                }
                                            */?>
                                            <li>
                                                <i class="far fa-circle"></i>
                                                <span><?/*=$label*/?>:</span>
                                                <span class="card-info-list-text fw-bold ms-1 text-body">
                                                    <?/*=$name*/?>
                                                </span>
                                            </li>    
                                            <?php
/*                                                $specCounter++;
                                            }
                                        */?>
                                    </ul>
                                </div>

                                <div class="card-price">
                                    <div class="retail-price-container">
                                        <span class="retail-price-label text-muted">Retail</span>
                                        <?php
/*                                        if ($product->discountPercent > 0) {
                                        */?>
                                            <span class="retail-price-text fw-bold price-sale"><?/*=$product->price*/?> €</span>
                                            <span class="retail-price-text fw-bold price-new"><?/*=$product->discountPrice*/?> €</span>
                                        <?php
/*                                        }
                                        else
                                        {
                                        */?>
                                        <span class="retail-price-text fw-bold"><?/*=$product->price*/?></span>
                                        <span class="retail-price-currency fw-bold">€</span>
                                        <?php
/*                                        }
                                        */?>
                                    </div>
                                    <div class="quantity-container">
                                        <form method="post" action="cart_process.php">
                                            <div class="text-muted mb-1">Qty</div>
                                            <div class="quantity-picker-container">
                                                <div onclick="objShop.changeQuantityPickerAmount(0, <?/*=$product->id*/?>)"
                                                     class="minus"><i class="fas fa-minus"></i></div>
                                                <input class="form-control quantity-picker-input" name="cart_quantity" type="number" placeholder="0" min="0">
                                                <input type="hidden" value="<?/*=$product->id*/?>" name="cart_product_id">
                                                <div onclick="objShop.changeQuantityPickerAmount(1, <?/*=$product->id*/?>)"
                                                     class="plus"><i class="fas fa-plus"></i></div>
                                            </div>
                                            <div class="total-price" style="visibility: hidden">
                                                <div class="text-muted mb-1 mt-1 d-inline-block">Total:</div>
                                                <span class="fw-bold total-price-text d-inline-block"></span>
                                                <button type="submit" class="w-100 btn-add-cart">Add to cart</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                --><?php
/*                }
                
                */?>
            </div>
        </div>
    </div>
</div>

<section id="product_page">
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-body pb-4 pt-4 h-100">
                    <div class="row h-100">
                        <div class="col-5 align-items-center d-none d-lg-block my-auto product-images">
                        </div>
                        <div class="col border-start info-column">
                            <button type="button" class="btn-close-modal d-block fw-bold p-0 mb-2" data-bs-dismiss="modal" aria-label="Close"><i class="fas fa-arrow-left"></i> Return to products</button>
                            <span class="card-subtitle fw-light text-uppercase text-muted"></span>
                            <h5 class="card-title mb-3"></h5>
                            <div class="col d-block d-lg-none product-images-mobile">
                            </div>
                            <div class="card-price pb-3">
                                <div class="retail-price-container">
                                    <span class="retail-price-label text-muted">Retail</span>
                                    <span class="retail-price-text retail-price-default fw-bold"></span>
                                    <span class="retail-price-text fw-bold price-new"></span>
                                </div>

                            </div>
                            <div class="page-switch-container mb-3">
                                <button class="active w-50" data-tab="description" onclick="objShop.changeProductModalTab(this)">Description</button><button data-tab="specifications" class="w-50" onclick="objShop.changeProductModalTab(this)">Specifications</button>
                            </div>
                            <div class="product-tab product-description ps-2 pe-2">
                            </div>
                            <div class="product-tab product-specifications mt-4">
                                <table class="table table-striped table-bordered">
                                    <tbody class="product-specification-table">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!--TODO : Successful product add to cart message-->
<section id="cart_add_page">
    <div class="modal fade" id="cartAddModal" tabindex="-1" aria-labelledby="cartAddModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-body pb-4 pt-4 h-100">
                    <div><i class="fas fa-cart-plus fs-1"></i></div>
                    <div>Product has been added to cart!</div>
                    <div>
                        <button>Continue shopping</button>
                        <button>Go to cart</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<?php include_once 'footer.php' ?>

<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>

