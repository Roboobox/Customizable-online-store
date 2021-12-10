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
// Get which tab/page is opened, default 'create_product'
$page = $_GET['p'] ?? 'create_product';
// Set available pages
$availablePages = ['create_product', 'delete_product', 'store_orders', 'store_settings', 'contact_messages', 'product_discounts'];
// If current page is not one from the available pages then open create_product page
if (!in_array($page, $availablePages, false)) {
    $page = 'create_product';
}

if ($page === 'create_product' || $page === 'delete_product') {
    // If page opened is product creation then include product creation file
    if ($page === 'create_product') {
        include_once "admin/create_new_product.php";
    }
    // Select all products for use in there pages
    $productQuery = $conn->query("
        SELECT P.name, P.id, P.price, D.discount_percent, C.name AS category FROM `product` P
        LEFT JOIN product_category C ON P.category_id = C.id
        LEFT JOIN product_discount D ON D.id = (SELECT MAX(PD.id) FROM product_discount PD WHERE PD.product_id = P.id AND PD.is_active = 1 AND (NOW() between PD.starting_at AND PD.ending_at))
        WHERE P.is_deleted = (0)
    ");
    $products = $productQuery->fetchAll();
} else if ($page === 'contact_messages') {
    // If page is contact messages then select all contact messages from database
    $messageQuery = $conn->query("SELECT * FROM contact_message ORDER BY ID DESC");
    $messages = $messageQuery->fetchAll();
} else if ($page === 'store_settings') {
    // If page is store settings then select all store settings
    $settingsQuery = $conn->query("SELECT * FROM store_setting");
    $storeSettings = $settingsQuery->fetch();
} else if ($page === 'store_orders') {
    // If page is store orders then select all store orders
    $orderQuery = $conn->query("SELECT * FROM `order` ORDER BY id DESC");
    $orderRows = $orderQuery->fetchAll();

    // Save order objects
    $orders = [];
    foreach ($orderRows as $row) {
        $order = new Order();
        $order->getOrderFromRow($row);
        $orders[] = $order;
    }
} else if ($page === 'product_discounts') {
    // If page is product discounts then include create discount file and select all active discounts and products for new discount creation
    include_once "admin/create_discount.php";
    $discountQuery = $conn->query("SELECT PD.*, P.name FROM `product_discount` PD LEFT JOIN `product` P ON P.id = PD.product_id  WHERE (NOW() between starting_at AND ending_at) ORDER BY PD.id DESC LIMIT 1");
    $discounts = $discountQuery->fetchAll();
    $productQuery = $conn->query("SELECT id, name FROM product WHERE is_deleted = (0)");
    $products = $productQuery->fetchAll();
}

// Save errors or success messages from session and clear the session
$formErrors = $_SESSION['formErrors'] ?? array();
unset($_SESSION['formErrors']);

if (isset($_SESSION['formSuccess'])) {
    $formSuccessMsg = $_SESSION['formSuccess'];
    unset($_SESSION['formSuccess']);
    $_POST = array();
}

?>
<script type="text/javascript" src="./js/admin.js?v=3"></script>
<link href="css/admin.css?<?=time()?>" rel="stylesheet">
<div class="container mb-5">
    <div class="row">
        <h2 class="w-100 mt-5 mb-4">Admin dashboard</h2>
    </div>
    <div class="admin-container">
        <div class="row">
            <div class="col-md-3 col-12 mb-3">
                <div class="list-group">
                    <a href="admin_dash.php?p=create_product" id="btn_product_create" class="list-group-item list-group-item-action <?=($page==='create_product') ? 'active' : ''?>"><i class="fas fa-plus-square align-middle"></i> Create a product</a>
                    <a href="admin_dash.php?p=delete_product" id="btn_product_delete" class="list-group-item list-group-item-action <?=($page==='delete_product') ? 'active' : ''?>"><i class="fas fa-trash-alt align-middle"></i> Delete a product</a>
                    <a href="admin_dash.php?p=store_orders" id="btn_store_orders" class="list-group-item list-group-item-action <?=($page==='store_orders') ? 'active' : ''?>"><i class="fas fa-shopping-cart align-middle"></i> Store orders</a>
                    <a href="admin_dash.php?p=store_settings" id="btn_store_settings" class="list-group-item list-group-item-action <?=($page==='store_settings') ? 'active' : ''?>"><i class="fas fa-cog align-middle"></i> Store settings</a>
                    <a href="admin_dash.php?p=contact_messages" id="btn_contact_messages" class="list-group-item list-group-item-action <?=($page==='contact_messages') ? 'active' : ''?>"><i class="fas fa-paper-plane align-middle"></i> Contact messages</a>
                    <a href="admin_dash.php?p=product_discounts" id="btn_product_discounts" class="list-group-item list-group-item-action <?=($page==='product_discounts') ? 'active' : ''?>"><i class="fas fa-tags align-middle"></i> Product discounts</a>
                </div>
            </div>
            <div class="col border rounded">
                <?php
                if ($page === 'create_product') {
                ?>
                <section class="p-4" id="product_create">
                    <h5>Create a new product</h5>
                    <div class="px-3 py-1 mb-3 text-white text-update-success <?=(isset($formSuccessMsg) ? 'feedback-fade-in' : 'd-none')?>"><?=$formSuccessMsg ?? ''?></div>
                    <form action="" method="post" enctype="multipart/form-data" novalidate>
                        <div class="mb-3">
                            <label for="inputProductName" class="form-label">Product name *</label>
                            <input name="prodName" value="<?=htmlspecialchars($_POST['prodName'] ?? '')?>" type="text" class="form-control <?=isset($formErrors['prodName']) ? 'is-invalid' : ''?>" id="inputProductName" required>
                            <div class="invalid-feedback">
                                <?=$formErrors['prodName'] ?? ''?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="inputProductCategory" class="form-label">Product category *</label>
                            <input name="prodCat" value="<?=htmlspecialchars($_POST['prodCat'] ?? '')?>" type="text" class="form-control <?=isset($formErrors['prodCat']) ? 'is-invalid' : ''?>" id="inputProductCategory" required>
                            <div class="invalid-feedback">
                                <?=$formErrors['prodCat'] ?? ''?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="inputProductPrice" class="form-label">Product retail price *</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">€</span>
                                <input name="prodPrice" value="<?=htmlspecialchars($_POST['prodPrice'] ?? '')?>" type="number" class="form-control <?=isset($formErrors['prodPrice']) ? 'is-invalid' : ''?>" id="inputProductPrice" placeholder="0.01" min="0.01" step="0.01" required />
                                <div class="invalid-feedback">
                                    <?=$formErrors['prodPrice'] ?? ''?>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="inputProductDescription" class="form-label">Product description</label>
                            <textarea name="prodDesc" class="form-control <?=isset($formErrors['prodDesc']) ? 'is-invalid' : ''?>" id="inputProductDescription" rows="3"><?=htmlspecialchars($_POST['prodDesc'] ?? '')?></textarea>
                            <div class="invalid-feedback">
                                <?=$formErrors['prodDesc'] ?? ''?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="inputProductSpecifications" class="form-label">Product specifications</label>
                            <div class="row" id="inputProductSpecifications">
                                <?php
                                if (isset($_POST['specsLabel'], $_POST['specsValue'])) {
                                    echo '<div class="col-6" id="inputProductSpecificationsLabel"><label for="inputProductSpecificationsLabel" class="form-label text-muted">Label</label>';
                                    //<input name="labelCategory" type="text" value="Category" class="form-control my-2" disabled/>
                                    $specCount = count($_POST['specsLabel']);
                                    $specValueHtml = '<div class="col-6" id="inputProductSpecificationsValue"><label for="inputProductSpecificationsValue" class="form-label text-muted">Value</label>';
                                    //<input name="valueCategory" type="text" value="'.htmlspecialchars($_POST['prodCat'] ?? '').'" class="form-control my-2" disabled/>
                                    for ($i = 0; $i < $specCount; $i++) {
                                        if (!empty($_POST['specsLabel'][$i]) || !empty($_POST['specsValue'][$i])) {
                                            echo '<input name="specsLabel[]" value="'.htmlspecialchars($_POST['specsLabel'][$i]).'" type="text" class="form-control my-2"/>';
                                            $specValueHtml .= '<input name="specsValue[]" value="'.htmlspecialchars($_POST['specsValue'][$i]).'" type="text" class="form-control my-2"/>';
                                        }
                                    }
                                    $specValueHtml .= '</div>';
                                    echo '</div>';
                                    echo $specValueHtml;
                                } else {
                                ?>
                                <div class="col-6" id="inputProductSpecificationsLabel">
                                    <label for="inputProductSpecificationsLabel" class="form-label text-muted">Label</label>
<!--                                    <input name="labelCategory" type="text" value="Category" class="form-control my-2" disabled/>-->
                                    <input name="specsLabel[]" type="text" class="form-control my-2"/>    
                                </div>
                                <div class="col-6" id="inputProductSpecificationsValue">
                                    <label for="inputProductSpecificationsValue" class="form-label text-muted">Value</label>
<!--                                    <input name="valueCategory" type="text" value="--><?//=htmlspecialchars($_POST['prodCat'] ?? '')?><!--" class="form-control my-2" disabled/>-->
                                    <input name="specsValue[]" type="text" class="form-control my-2"/>
                                </div>
                                <?php
                                }
                                ?>
                                <div class="col-12">
                                    <a id="add_new_spec" class="btn mt-2 float-end btn-success">Add new</a>
                                </div>
                            </div>
                            <div class="row mt-2">
                            <div class="text-danger text-center <?=isset($formErrors['specs']) ? 'd-inline-block' : 'd-none'?>">
                                <?=$formErrors['specs'] ?? ''?>
                            </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="inputProductInventory" class="form-label">Product quantity in inventory *</label>
                            <input name="prodInventory" value="<?=$_POST['prodInventory'] ?? '1'?>" type="number" class="form-control <?=isset($formErrors['prodInventory']) ? 'is-invalid' : ''?>" id="inputProductInventory" min="1" max="10000" required/>
                            <div class="invalid-feedback">
                                <?=$formErrors['prodInventory'] ?? ''?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="inputProductImages" class="form-label">Product image/s *</label>
                            <input name="prodImg[]" type="file" class="form-control <?=isset($formErrors['prodImg']) ? 'is-invalid' : ''?>" id="inputProductImages" multiple accept="image/png, image/jpeg" aria-labelledby="imageInfo" required/>
                            <div id="imageInfo" class="form-text">Allowed image formats: jpg, png</div>
                            <div class="invalid-feedback">
                                <?=$formErrors['prodImg'] ?? ''?>
                            </div>
                        </div>
                        <div class="text-danger text-center <?=(isset($formErrors['general']) ? 'd-inline-block' : 'd-none')?>"><?=$formErrors['general'] ?? ''?></div>
                        <button type="submit" class="btn btn-primary w-25">Create</button>
                    </form>
                </section>
                <?php
                } else if ($page === 'delete_product') {
                ?>
                <section class="p-4" id="product_delete">
                    <h5>Delete a product</h5>
                    <div class="px-3 py-1 mb-3 text-white text-update-success <?=(isset($formSuccessMsg) ? 'feedback-fade-in' : 'd-none')?>"><?=$formSuccessMsg ?? ''?></div>
                    <div style="width: fit-content" class="bg-danger px-3 py-1 mb-3 text-white <?=(isset($formErrors['general']) ? 'feedback-fade-in' : 'd-none')?>"><?=$formErrors['general'] ?? ''?></div>
                    <table class="table table-bordered table-striped">
                        <tr>
                            <th>Product name</th>
                            <th>Product category</th>
                            <th>Product price</th>
                            <th>Remove</th>
                        </tr>
                        <?php
                        if (!empty($products)) {
                            foreach ($products as $row) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                                echo "<td>" . htmlspecialchars(number_format(($row['price'] - ($row['discount_percent'] * $row['price'] / 100)), 2, '.', '')) . " €</td>";
                                echo "<td class='text-center'>";
                                echo '<a onclick="return confirm(\'Are you sure you want to delete this product\')" class="btn btn-primary" href="admin/remove_product.php?token=' . ($_SESSION['user_token'] ?? "") . '&id=' . htmlspecialchars($row['id']) . '">Remove</a>';
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo '<tr><td colspan="4" class="text-center">No products available</td></tr>';
                        }
                        ?>
                    </table>
                </section>
                <?php
                } else if ($page === 'store_settings') {
                ?>
                <section class="p-4" id="store_settings">
                    <h5>Store settings</h5>
                    <div class="px-3 py-1 mb-3 text-white text-update-success <?=(isset($formSuccessMsg) ? 'feedback-fade-in' : 'd-none')?>"><?=$formSuccessMsg ?? ''?></div>
                    <div style="width: fit-content" class="bg-danger px-3 py-1 mb-3 text-white <?=(isset($formErrors['general']) ? 'feedback-fade-in' : 'd-none')?>"><?=$formErrors['general'] ?? ''?></div>
                    <form action="admin/edit_store_settings.php" method="post" enctype="multipart/form-data" novalidate>
                        <div class="mb-3">
                            <label for="inputStoreName" class="form-label">Store name</label>
                            <input name="storeName" value="<?=$_POST['storeName'] ?? $storeSettings['store_name'] ?? ''?>" type="text" class="form-control <?=isset($formErrors['storeName']) ? 'is-invalid' : ''?>" id="inputStoreName">
                            <div class="invalid-feedback">
                                <?=$formErrors['storeName'] ?? ''?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="inputStoreEmail" class="form-label">Store email address</label>
                            <input name="storeEmail" value="<?=$_POST['storeEmail'] ?? $storeSettings['store_email'] ?? ''?>" type="text" class="form-control <?=isset($formErrors['storeEmail']) ? 'is-invalid' : ''?>" id="inputStoreEmail">
                            <div class="invalid-feedback">
                                <?=$formErrors['storeEmail'] ?? ''?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="inputStoreAddress" class="form-label">Store physical address</label>
                            <input name="storeAddress" value="<?=$_POST['storeAddress'] ?? $storeSettings['store_address'] ?? ''?>" type="text" class="form-control <?=isset($formErrors['storeAddress']) ? 'is-invalid' : ''?>" id="inputStoreAddress" >
                            <div class="invalid-feedback">
                                <?=$formErrors['storeAddress'] ?? ''?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="inputStorePhone" class="form-label">Store phone number</label>
                            <input name="storePhone" value="<?=$_POST['storePhone'] ?? $storeSettings['store_phonenr'] ?? ''?>" type="text" class="form-control <?=isset($formErrors['storePhone']) ? 'is-invalid' : ''?>" id="inputStorePhone"  />
                            <div class="invalid-feedback">
                                <?=$formErrors['storePhone'] ?? ''?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="inputStoreLogo" class="form-label">Store logo</label>
                            <br>
                            <img class="mb-2" src="images/<?=$storeSettings['logo_path']?>" height="60" width="100%" alt="Store logo"/>
                            <input name="storeLogo" type="file" class="form-control <?=isset($formErrors['storeLogo']) ? 'is-invalid' : ''?>" id="inputStoreLogo" accept="image/png, image/jpeg" aria-labelledby="imageInfo" />
                            <div id="imageInfo" class="form-text">Allowed image formats: jpg, png</div>
                            <div class="invalid-feedback">
                                <?=$formErrors['storeLogo'] ?? ''?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="inputAbout" class="form-label">About us information</label>
                            <textarea name="storeAbout" class="form-control <?=isset($formErrors['storeAbout']) ? 'is-invalid' : ''?>" id="inputAbout" rows="5" aria-labelledby="aboutInfo"><?=$_POST['storeAbout'] ?? $storeSettings['about_text'] ?? ''?></textarea>
                            <div id="aboutInfo" class="form-text">Use **Title** format to mark a new title. For example: **Development** Lorem ipsum dolor sit amet...</div>
                            <div class="invalid-feedback">
                                <?=$formErrors['storeAbout'] ?? ''?>
                            </div>
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
                    <div class="feedback feedback-hide text-white px-3 py-1 mb-3"></div>
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
                </section>
                <?php
                } else if ($page === 'contact_messages') {
                ?>
                <section class="p-4" id="contact_messages">
                    <h5 class="mb-4">Contact messages</h5>
                    <?php
                    if (!empty($messages)) {
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
                    } else {
                    ?>
                        <div class="row"><div class="col-12 border text-center py-2">No messages sent</div></div>
                    <?php
                    }
                    ?>
                </section>
                <?php
                } else if ($page === 'product_discounts') {
                ?>
                <section class="p-sm-4 p-3" id="product_discounts">
                    <h5 class="mb-4">Current product discounts</h5>
                    <div class="px-3 py-1 mb-3 text-white text-update-success <?=(isset($formSuccessMsg) ? 'feedback-fade-in' : 'd-none')?>"><?=$formSuccessMsg ?? ''?></div>
                    <div style="width: fit-content" class="bg-danger px-3 py-1 mb-3 text-white <?=(isset($formErrors['general']) ? 'feedback-fade-in' : 'd-none')?>"><?=$formErrors['general'] ?? ''?></div>
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
                        <form action="" method="post" class="border p-3">
                            <div class="mb-3">
                                <label for="inputDcProduct" class="form-label">Product *</label>
                                <select class="form-select <?=isset($formErrors['discountProduct']) ? 'is-invalid' : ''?>" name="discountProduct" id="inputDcProduct" aria-label="Discount product selection">
                                    <option <?=(isset($_POST['discountProduct']) ? '' : 'selected')?> value="">Select product...</option>
                                    <?php
                                    foreach ($products as $product) {
                                        echo '<option ';
                                        if (isset($_POST['discountProduct']) && $_POST['discountProduct'] == $product['id']) {
                                            echo 'selected ';
                                        }
                                        echo 'value="' . htmlspecialchars($product['id']) . '">' . htmlspecialchars($product['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                                <div class="invalid-feedback">
                                    <?=$formErrors['discountProduct'] ?? ''?>
                                </div>
                            </div>
                            <div class="mb-3 col-lg-3 col-5">
                                <label for="inputDcPercent" class="form-label">Discount percent *</label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text">%</span>
                                    <input name="discountPercent" value="<?=$_POST['discountPercent'] ?? ''?>" type="number" min="1" max="100" class="form-control <?=isset($formErrors['discountPercent']) ? 'is-invalid' : ''?>" id="inputDcPercent">
                                    <div class="invalid-feedback">
                                        <?=$formErrors['discountPercent'] ?? ''?>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="inputDcStart" class="form-label">Discount start *</label>
                                <input name="discountStart" value="<?=$_POST['discountStart'] ?? ''?>" type="datetime-local" class="form-control <?=isset($formErrors['discountStart']) ? 'is-invalid' : ''?>" id="inputDcStart">
                                <div class="invalid-feedback">
                                    <?=$formErrors['discountStart'] ?? ''?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="inputDcEnd" class="form-label">Discount end *</label>
                                <input name="discountEnd" value="<?=$_POST['discountEnd'] ?? ''?>" type="datetime-local" class="form-control <?=isset($formErrors['discountEnd']) ? 'is-invalid' : ''?>" id="inputDcEnd">
                                <div class="invalid-feedback">
                                    <?=$formErrors['discountEnd'] ?? ''?>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Add discount</button>
                        </form>
                    </div>
                </section>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'?>
