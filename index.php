<?php
$tab = 'Products';

include_once 'head.php';
include_once 'header.php';
?>
<script type="text/javascript">
    $( document ).ready(function() {
        objShop.init(<?=(isset($_SESSION['sort'], $_SESSION['layout']) ? json_encode($_SESSION['sort']) . ',' . json_encode($_SESSION['layout']) : '')?>);
    });
</script>
<link href="css/index.css?<?=time()?>" rel="stylesheet">
<div class="container mb-5">
    <div class="row">
        <h3 class="w-100 mt-4 mt-sm-5">Products</h3>
        <h5 class="search-results w-100 mb-lg-4">Search results: <span class="search-results-text"></span></h5>
        <a id="mob_filter_open"><h6 class="d-lg-none fw-bold py-2"><i class="fas fa-filter"></i> Filters <i class="fas fa-caret-down"></i></h6></a>
    </div>
    <div class="row">
        <div class="col-3 d-none d-lg-block filter-container">
            <div class="spec-filters row p-3 me-lg-2 bg-white border"></div>
        </div>
        <div class="col product-container">
            <input type="hidden" id="csrf_token" name="token" value="<?=htmlspecialchars($_SESSION['user_token'] ?? '')?>" />
            <div id="search_options" style="height: 50px;" class="row mb-2">
                <div class="col d-flex ms-1 ms-sm-2 ps-2 ps-sm-3 border-start border-top border-bottom bg-white align-items-center">
                    <i class="d-none fas fa-sort fs-5 d-sm-inline-block" style="width: 25px"></i>
                    <select id="productSort" class="form-select w-auto d-inline-block" aria-label="Sorting select">
                        <option value="A to Z" <?=(!isset($_SESSION['sort'])||$_SESSION['sort'] == 'A to Z') ? 'Selected' : '' ?>>Sort: A to Z</option>
                        <option value="Z to A" <?=(isset($_SESSION['sort'])&&$_SESSION['sort'] == 'Z to A') ? 'Selected' : '' ?>>Sort: Z to A</option>
                        <option value="Price desc" <?=(isset($_SESSION['sort'])&&$_SESSION['sort'] == 'Price desc') ? 'Selected' : '' ?>>Sort: Price descending</option>
                        <option value="Price asc" <?=(isset($_SESSION['sort'])&&$_SESSION['sort'] == 'Price asc') ? 'Selected' : '' ?>>Sort: Price ascending</option>
                    </select>
                </div>
                <div class="col d-flex me-sm-2 me-1 pe-0 bg-white border-top border-bottom align-items-center justify-content-end">
                    <div id="gridSelect" style="line-height: 50px" class="fs-5 px-3 border-start border-end h-100 grid-view-button">
                        <i class="fas fa-th-large"></i>
                    </div>
                    <div id="listSelect" style="line-height: 50px" class="fs-5 px-3 border-end h-100 list-view-button">
                        <i class="fas fa-align-justify"></i>
                    </div>
                </div>
            </div>
            <div class="col-12 px-3" id="products_loading">
                <div class="p-3 border shadow-sm fw-bold text-center">
                    <div><i class="fas fa-spinner fa-spin fs-1 my-3 text-muted"></i></div>
                </div>
            </div>
            <div class="product-view-container">
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
                Something went wrong, try again later!
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

