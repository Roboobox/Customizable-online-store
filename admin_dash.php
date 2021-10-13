<?php
session_start();
if ($_SESSION['user_role'] != 1) {
    header('Location: index.php');
    exit;
}

$tab = 'Admin';
include_once 'head.php';
include_once 'header.php';
include_once "conn.php";
require_once('objects/Order.php');
// TODO : Make page switching using ajax?
// TODO : Make about us managing page
// TODO : add inventory quantity field for product
// TODO : add product editing page
$page = $_GET['p'] ?? 'create_product';
$availablePages = ['create_product', 'delete_product', 'store_orders', 'store_settings', 'contact_messages', 'product_discounts'];
if (!in_array($page, $availablePages, false)) {
    $page = 'create_product';
}

if ($page === 'create_product' || $page === 'delete_product') {
//    if ($page === 'create_product') {
//        include_once "admin/create_new_product.php";
//    }
    $productQuery = $conn->query("
        SELECT P.name, P.id, P.price, D.discount_percent, C.name AS category FROM `product` P
        LEFT JOIN product_category C ON P.category_id = C.id
        LEFT JOIN product_discount D ON D.id = (SELECT MAX(PD.id) FROM product_discount PD WHERE PD.product_id = P.id AND PD.is_active = 1 AND (NOW() between PD.starting_at AND PD.ending_at))
    ");
    $products = $productQuery->fetchAll();
} else if ($page === 'contact_messages') {
    $messageQuery = $conn->query("SELECT * FROM contact_message ORDER BY ID DESC");
    $messages = $messageQuery->fetchAll();
} else if ($page === 'store_settings') {
    $settingsQuery = $conn->query("SELECT * FROM store_setting");
    $storeSettings = $settingsQuery->fetch();
} else if ($page === 'store_orders') {
    $orderQuery = $conn->query("SELECT * FROM `order` ORDER BY id DESC");
    $orderRows = $orderQuery->fetchAll();

    $orders = [];
    foreach ($orderRows as $row) {
        $order = new Order();
        $order->getOrderFromRow($row);
        $orders[] = $order;
    }
} else if ($page === 'product_discounts') {
    $discountQuery = $conn->query("SELECT PD.*, P.name FROM `product_discount` PD LEFT JOIN `product` P ON P.id = PD.product_id  WHERE (NOW() between starting_at AND ending_at)");
    $discounts = $discountQuery->fetchAll();
    $productQuery = $conn->query("SELECT id, name FROM product");
    $products = $productQuery->fetchAll();
}
?>
<script type="text/javascript" src="./js/admin.js?v=1"></script>
<link href="css/admin.css?<?=time()?>" rel="stylesheet">
<div class="container mb-5">
    <div class="row">
        <h2 class="w-100 mt-5 mb-4">Admin dashboard</h2>
    </div>
    <div class="admin-container">
        <div class="row">
            <div class="col-md-3 col-12 mb-3">
                <div class="list-group">
                    <a href="admin_dash.php?p=create_product" id="btn_product_create" class="list-group-item list-group-item-action <?=($page==='create_product') ? 'active' : ''?>">Create a product</a>
                    <a href="admin_dash.php?p=delete_product" id="btn_product_delete" class="list-group-item list-group-item-action <?=($page==='delete_product') ? 'active' : ''?>">Delete a product</a>
                    <a href="admin_dash.php?p=store_orders" id="btn_store_orders" class="list-group-item list-group-item-action <?=($page==='store_orders') ? 'active' : ''?>">Store orders</a>
                    <a href="admin_dash.php?p=store_settings" id="btn_store_settings" class="list-group-item list-group-item-action <?=($page==='store_settings') ? 'active' : ''?>">Store settings</a>
                    <a href="admin_dash.php?p=contact_messages" id="btn_contact_messages" class="list-group-item list-group-item-action <?=($page==='contact_messages') ? 'active' : ''?>">Contact messages</a>
                    <a href="admin_dash.php?p=product_discounts" id="btn_product_discounts" class="list-group-item list-group-item-action <?=($page==='product_discounts') ? 'active' : ''?>">Product discounts</a>
                </div>
            </div>
            <div class="col border rounded">
                <?php
                if ($page === 'create_product') {
                ?>
                <section class="p-4" id="product_create">
                    <h5>Create a new product</h5>
                    <form action="admin/create_new_product.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="inputProductName" class="form-label">Product name</label>
                            <input name="prodName" type="text" class="form-control" id="inputProductName" required>
                        </div>
                        <div class="mb-3">
                            <label for="inputProductCategory" class="form-label">Product category</label>
                            <input name="prodCat" type="text" class="form-control" id="inputProductCategory" required>
                        </div>
                        <div class="mb-3">
                            <label for="inputProductPrice" class="form-label">Product retail price</label>
                            <input name="prodPrice" type="number" class="form-control" id="inputProductPrice" min="0.00" step="0.01" required />
                        </div>
                        <div class="mb-3">
                            <label for="inputProductDescription" class="form-label">Product description</label>
                            <textarea name="prodDesc" class="form-control" id="inputProductDescription" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="inputProductSpecifications" class="form-label">Product specifications</label>
                            <div class="row" id="inputProductSpecifications">
                                <div class="col-6" id="inputProductSpecificationsLabel">
                                    <label for="inputProductSpecificationsLabel" class="form-label text-muted">Label</label>
                                    <input name="specsLabel[]" type="text" class="form-control my-2"/>    
                                </div>
                                <div class="col-6" id="inputProductSpecificationsValue">
                                    <label for="inputProductSpecificationsValue" class="form-label text-muted">Value</label>
                                    <input name="specsValue[]" type="text" class="form-control my-2"/>
                                </div>
                                <div class="col-12">
                                    <a id="add_new_spec" class="btn mt-2 float-end btn-success">Add new</a>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="inputProductImages" class="form-label">Product image/s</label>
                            <input name="prodImg[]" type="file" class="form-control" id="inputProductImages" multiple accept="image/png, image/jpeg" aria-labelledby="imageInfo" required/>
                            <div id="imageInfo" class="form-text">Allowed image formats: jpg, png</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-25">Create</button>
                    </form>
                </section>
                <?php
                } else if ($page === 'delete_product') {
                ?>
                <section class="p-4" id="product_delete">
                    <h5>Delete a product</h5>
                    <table class="table table-bordered table-striped">
                        <tr>
                            <th>Product name</th>
                            <th>Product category</th>
                            <th>Product price</th>
                            <th>Remove</th>
                        </tr>
                        <?php
                        foreach ($products as $row) {
                            echo "<tr>";
                            echo "<td>" . $row['name'] . "</td>";
                            echo "<td>" . $row['category'] . "</td>";
                            echo "<td>" . number_format(($row['price'] - ($row['discount_percent'] * $row['price'] / 100)), 2, '.', '') . " €</td>";
                            echo "<td class='text-center'>";
                            echo '<a onclick="return confirm(\'Are you sure you want to delete this product\')" class="btn btn-primary" href="admin/remove_product.php?token='. ($_SESSION['user_token'] ?? "").'&id='.$row['id'].'">Remove</a>';
                            echo "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </table>
                </section>
                <?php
                } else if ($page === 'store_settings') {
                ?>
                <section class="p-4" id="store_settings">
                    <h5>Store settings</h5>
                    <form action="admin/edit_store_settings.php" method="post" enctype="multipart/form-data" novalidate>
                        <div class="mb-3">
                            <label for="inputStoreEmail" class="form-label">Store email address</label>
                            <input name="storeEmail" value="<?=$storeSettings['store_email'] ?? ''?>" type="text" class="form-control" id="inputStoreEmail">
                        </div>
                        <div class="mb-3">
                            <label for="inputStoreAddress" class="form-label">Store physical address</label>
                            <input name="storeAddress" value="<?=$storeSettings['store_address'] ?? ''?>" type="text" class="form-control" id="inputStoreAddress" >
                        </div>
                        <div class="mb-3">
                            <label for="inputStorePhone" class="form-label">Store phone number</label>
                            <input name="storePhone" value="<?=$storeSettings['store_phonenr'] ?? ''?>" type="text" class="form-control" id="inputStorePhone"  />
                        </div>
                        <div class="mb-3">
                            <label for="inputStoreLogo" class="form-label">Store logo</label>
                            <br>
                            <img class="mb-2" src="test_images/<?=$storeSettings['logo_path']?>" height="60" width="100%" alt="Store logo"/>
                            <input name="storeLogo" type="file" class="form-control" id="inputStoreLogo" accept="image/png, image/jpeg" aria-labelledby="imageInfo" />
                            <div id="imageInfo" class="form-text">Allowed image formats: jpg, png</div>
                        </div>
                        <div class="mb-3">
                            <label for="inputAbout" class="form-label">About us information</label>
                            <textarea name="storeAbout" class="form-control" id="inputAbout" rows="5" aria-labelledby="aboutInfo"><?=$storeSettings['about_text'] ?? ''?></textarea>
                            <div id="aboutInfo" class="form-text">Use **Title** format to mark a new title. For example: **Development** Lorem ipsum dolor sit amet...</div>
                        </div>
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-6 col-md-3 mb-2">
                                    <label for="inputStorePrimaryClr" class="form-label">Primary color</label>
                                    <input name="storePrimaryClr" value="<?=$storeSettings['primary_color'] ?? ''?>" type="color" class="form-control" id="inputStorePrimaryClr"  />
                                </div>
                                <div class="col-6 col-md-3 mb-2">
                                    <label for="inputStoreSaleClr" class="form-label">Sale color</label>
                                    <input name="storeSaleClr" value="<?=$storeSettings['sale_color'] ?? ''?>" type="color" class="form-control" id="inputStoreSaleClr"  />
                                </div>
                                <div class="col-6 col-md-3 mb-2">
                                    <label for="inputStorePositiveClr" class="form-label">Positive color</label>
                                    <input name="storePositiveClr" value="<?=$storeSettings['positive_color'] ?? ''?>" type="color" class="form-control" id="inputStorePositiveClr"  />
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="float-end my-3 btn btn-primary w-50">Save settings</button>
                    </form>
                </section>
                <?php
                } else if ($page === 'store_orders') {
                ?>
                <section class="p-4" id="store_orders">
                    <h5 class="mb-4">Store orders</h5>
                    <table class="table table-striped table-bordered text-center">
                        <tr>
                            <th>Number</th>
                            <th class="d-none d-md-table-cell">Date</th>
                            <th class="d-none d-md-table-cell">Total</th>
                            <th class="d-none d-md-table-cell">Client</th>
                            <th>Status</th>
                            <th>View <i class="far fa-eye"></i></th>
                        </tr>
                        <?php
                        if (!empty($orders)) {
                        foreach ($orders as $order) {?>
                            <tr>
                                <td>#<?=$order->id?></td>
                                <td class="d-none d-md-table-cell"><?=$order->getCreatedAt()?></td>
                                <td class="d-none d-md-table-cell"><?=$order->total?> €</td>
                                <td class="d-none d-md-table-cell"><?=$order->getFullName()?></td>
                                <td>
                                    <select data-id="<?=$order->id?>" class="form-select order-status-select" aria-label="Order status selection">
                                        <option <?=($order->status == 'New order') ? 'selected' : ''?> value="New order">New order</option>
                                        <option <?=($order->status == 'Waiting payment') ? 'selected' : ''?> value="Waiting payment">Waiting payment</option>
                                        <option <?=($order->status == 'Delivery') ? 'selected' : ''?> value="Delivery">Delivery</option>
                                        <option <?=($order->status == 'Ready to receive') ? 'selected' : ''?> value="Ready to receive">Ready to receive</option>
                                        <option <?=($order->status == 'Completed') ? 'selected' : ''?> value="Completed">Completed</option>
                                        <option <?=($order->status == 'Failed') ? 'selected' : ''?> value="Failed">Failed</option>
                                        <option <?=($order->status == 'Refunded') ? 'selected' : ''?> value="Refunded">Refunded</option>
                                    </select>
                                </td>
                                <td><a class="btn btn-primary" href="order.php?id=<?=$order->id?>">View</a></td>
                            </tr>
                        <?php
                        }
                        } else {
                            echo '<tr><td colspan="6">No orders placed</td></tr>';
                        }
                        ?>
                    </table>
                    <div class="feedback order_success text-success">Order status updated!</div>
                    <div class="feedback order_error text-danger">Order status failed to update!</div>
                </section>
                <?php
                } else if ($page === 'contact_messages') {
                ?>
                <section class="p-4" id="contact_messages">
                    <h5 class="mb-4">Contact messages</h5>
                    <?php
                    foreach ($messages as $message) {?>
                    <div class="row  border-bottom p-3">
                        <div class="message_email">
                            <i class="align-middle fas fa-user-circle"></i> <a class="text-dark" href="mailto: <?=htmlspecialchars($message['email'])?>"><?=htmlspecialchars($message['email'])?></a>
                            <div class="d-inline-block text-muted fw-light">(<?=date("d.m.Y H:i", strtotime($message['created_at']))?>)</div>
                        </div>
                        <div class="message_text mt-2"><?=nl2br(htmlspecialchars($message['message_text']))?></div>
                    </div>
                    <?php
                    }
                    ?>
                </section>
                <?php
                } else if ($page === 'product_discounts') {
                ?>
                <section class="p-sm-4 p-3" id="product_discounts">
                    <h5 class="mb-4">Current product discounts</h5>
                    <table class="table table-bordered table-striped text-center table-responsive">
                        <tr>
                            <th>Product</th>
                            <th>Discount</th>
                            <th class="d-none d-md-table-cell">Starting</th>
                            <th class="d-none d-md-table-cell">Ending</th>
                            <th>Status</th>
                        </tr>
                        <?php
                        if (!empty($discounts)) {
                            foreach ($discounts as $discount) {?>
                                <tr class="align-middle">
                                <td><?=$discount['name']?></td>
                                <td><?=$discount['discount_percent']?>%</td>
                                <td class="d-none d-md-table-cell"><?=$discount['starting_at']?></td>
                                <td class="d-none d-md-table-cell"><?=$discount['ending_at']?></td>
                                <td>
                                    <div class="fw-bold"><?=$discount['is_active'] ? 'Enabled' : 'Disabled'?></div>
                                    <div class="d-block"><a href="admin/change_discount.php?token=<?=($_SESSION['user_token'] ?? "")?>&id=<?=$discount['id']?>" class="btn btn-primary"><?=$discount['is_active'] ? 'Disable' : 'Enable'?></a></div>
                                </td>
                                </tr>
                            <?php
                            }
                        } else {
                        ?>
                            <tr><td class="text-center py-3" colspan="5">No current discounts</td></tr>
                        <?php
                        }
                        ?>
                    </table>
                    <div class="mt-3 new_discount_container">
                        <h5>Add new discount</h5>
                        <form action="admin/create_discount.php" method="post" class="border p-3">
                            <div class="mb-3">
                                <label for="inputDcProduct" class="form-label">Product</label>
                                <select class="form-select" name="discountProduct" id="inputDcProduct" aria-label="Discount product selection">
                                    <option selected value="">Select product...</option>
                                    <?php
                                    foreach ($products as $product) {
                                        echo '<option value="' . $product['id'] . '">' . $product['name'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3 col-lg-2 col-5">
                                <label for="inputDcPercent" class="form-label">Discount percent</label>
                                <input name="discountPercent" value="" type="number" min="1" max="100" class="form-control" id="inputDcPercent">
                            </div>
                            <div class="mb-3">
                                <label for="inputDcStart" class="form-label">Discount start</label>
                                <input name="discountStart" value="" type="datetime-local" class="form-control" id="inputDcStart">
                            </div>
                            <div class="mb-3">
                                <label for="inputDcEnd" class="form-label">Discount end</label>
                                <input name="discountEnd" value="" type="datetime-local" class="form-control" id="inputDcEnd">
                            </div>
                            <button type="submit" class="btn btn-primary">Add discount</button>
                        </form>
                    </div>
                </section>
                <?php
                }
                ?>
<!--                <section class="p-4" id="about_page">-->
<!--                    <h5 class="mb-4">About us page settings</h5>-->
<!--                    -->
<!--                </section>-->
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'?>
