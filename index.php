<?php
$tab = 'Products';

include_once 'head.php';
include_once 'header.php';
?>
<script type="text/javascript">
    $( document ).ready(function() {
        objShop.init();
    });
</script>
<link href="css/index.css?<?=time()?>" rel="stylesheet">
<div class="container mb-5">
    <div class="row">
        <h3 class="w-100 mt-4 mt-sm-5">Products</h3>
        <h5 class="search-results w-100 mb-4 mb-sm-5 <?=isset($_GET['q']) ? 'visible' : 'invisible'?>">Search results: <span class="search-results-text"><?= isset($_GET['q']) ? '"' . htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') . '"' : ''?></span></h5>
    </div>
    <div class="row">
        <div class="col-3 d-none d-sm-block">
            <div class="spec-filters row p-3 me-2 bg-white border"></div>
        </div>
        <div class="col product-container">
            <div class="col-12 px-3" id="products_loading">
                <div class="p-3 border shadow-sm fw-bold text-center">
                    <div><i class="fas fa-spinner fa-spin fs-1 my-3 text-muted"></i></div>
                </div>
            </div>
            <div class="row row-cols-xl-3 row-cols-lg-2 row-cols-sm-2 row-cols-2"></div>
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

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header justify-content-between">
            <div class="cart-success cart-msg">
                <i class="far fa-check-circle me-1 text-success"></i>
                <strong class="me-auto text-success">Success!</strong>
            </div>
            <div class="cart-error cart-msg">
                <i class="fas fa-exclamation-circle me-1 text-danger"></i>
                <strong class="me-auto text-danger">Error!</strong>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <div class="cart-success cart-msg">
            Product was added to the cart
            </div>
            <div class="cart-error cart-msg">
                Something went wrong. Try again later!
            </div>
        </div>
    </div>
</div>


<?php include_once 'footer.php' ?>

<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>

