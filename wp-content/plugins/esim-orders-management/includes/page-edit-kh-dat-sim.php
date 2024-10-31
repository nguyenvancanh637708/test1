<?php
function edit_kh_dat_sim_page() {
    global $wpdb;
    $table_name = 'wp_esim_orders';
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $order_id));

    if (!$order) {
        echo '<div class="wrap"><h1 class="wp-heading-inline">Đơn hàng không tồn tại</h1></div>';
        return;
    }

    $sim = wc_get_product($order->sim_id);
    $afc_nha_mang = $sim ? $sim->get_meta('nha_mang') : '';
    $goi_cuoc = wc_get_product($order->goicuoc_id);
    $disabled_form = $order->order_data_id != NULL;

    if ($afc_nha_mang) {
        if($disabled_form == false){
            $sim_products = get_sim_products($afc_nha_mang, $order->phone_number);
            $goi_cuoc_variations = get_goi_cuoc_variations($afc_nha_mang);
        }
    } else {
        echo '<div class="wrap"><h1 class="wp-heading-inline">Không lấy được nhà mạng của SIM</h1></div>';
        return;
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (update_order($order_id, $_POST, $wpdb, $table_name)) {
            echo '<script>alert("Cập nhật đơn hàng thành công."); window.location.reload();</script>';
            exit;
        } else {
            echo '<script>alert("Cập nhật đơn hàng không thành công."); window.location.reload();</script>';
            exit;
        }
    }

    $users = get_users();
    display_order_form($order, $sim_products, $goi_cuoc_variations, $users, $afc_nha_mang);
}

function get_sim_products($afc_nha_mang, $phone_number) {
    global $wpdb;

    // Set up the query arguments for SIM products
    $args = [
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'tax_query'      => [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => 'sim',
            ],
        ],
        'meta_query'     => [
            [
                'key'     => 'nha_mang',
                'value'   => $afc_nha_mang,
                'compare' => '=',
            ],
        ],
    ];

    $query = new WP_Query($args);
    $sim_products = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $sim = wc_get_product(get_the_ID());
            $sim_name = $sim->get_name();
            $phone_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM wp_esim_orders WHERE phone_number = %s AND status != -1 AND phone_number != %s",
                $sim_name,
                $phone_number
            ));

            if ($phone_exists == 0) {
                $sim_products[] = $sim;
            }
        }
        wp_reset_postdata();
    }

    return $sim_products;
}

function get_goi_cuoc_variations($afc_nha_mang) {
    // Set up the query arguments for GOI CUOC products
    $args = [
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'tax_query'      => [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => 'goi-cuoc',
            ],
        ],
        'meta_query'     => [
            [
                'key'     => 'nha_mang',
                'value'   => $afc_nha_mang,
                'compare' => '=',
            ],
        ],
    ];

    $query = new WP_Query($args);
    $goi_cuoc_variations = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());

            // Check if product is variable
            if ($product->is_type('variable')) {
                $variations = $product->get_children();
                foreach ($variations as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    $goi_cuoc_variations[] = $variation;
                }
            } else {
                // Add main product if meta matches
                if ($product->get_meta('nha_mang') == $afc_nha_mang) {
                    $goi_cuoc_variations[] = $product;
                }
            }
        }
        wp_reset_postdata();
    }

    return $goi_cuoc_variations;
}

function update_order($order_id, $data, $wpdb, $table_name) {
    $updated_order = [
        'customer_add'   => sanitize_text_field($data['customer_add']),
        'goicuoc_id'     => intval($data['goicuoc_id']), 
        'sim_id'         => intval($data['sim_id']), 
        'sim_type'       => sanitize_text_field($data['sim_type']), 
        'user_id'        => intval($data['user_id']), 
        'note'           => sanitize_textarea_field($data['note']),
        'user_note'      => sanitize_textarea_field($data['user_note']),
        'status'         => intval($data['order_status']), 
        'sim_priceShip'  => intval($data['sim_priceShip']), 
        'sim_price'      => intval($data['sim_price']), 
        'goicuoc_price'  => intval($data['goicuoc_price']), 
        'total_price'    => intval($data['total_price']), 
    ];

   
    $sim = wc_get_product($updated_order['sim_id']);
    $goicuoc = wc_get_product($updated_order['goicuoc_id']);

    if (!$sim || !$goicuoc) {
        return false; 
    }else{
        $updated_order['phone_number'] = $sim->name;
        $updated_order['package_name'] = $goicuoc->name;
    }
    return $wpdb->update($table_name, $updated_order, ['id' => $order_id]) !== false;
}


function display_order_form($order, $sim_products, $goi_cuoc_variations, $users, $afc_nha_mang) {
    $disabled = $order->status == 1;
    $disabled_form = $order->order_data_id != NULL;
    $current_user = wp_get_current_user();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Chỉnh sửa yêu cầu đặt SIM <strong>#<?php echo $order->code_request?></strong> </h1>
        <?php 
            if ($order->status == 1) {
            ?>
                <tr>
                    <td>
                        <?php 
                        if ($order->order_data_id == NULL) {
                            echo '<button type="button" id="btn-create_order" data-text="Tạo đơn hàng" class="button button-info btn-submit-cus">Tạo đơn hàng</button>';
                        } else {
                            echo '<a href="' . esc_url($order->order_data_id) . '" class="button button-info">Xem đơn hàng</a>';
                        }
                        ?>
                    </td>
                </tr>
            <?php
            }
        ?>
        <form method="POST" action="" id="form_edit">
            <?php wp_nonce_field('create_order_nonce', 'checkout_nonce'); ?>
            <div style="display:flex;">
                <table class="form-table">
                    <tr>
                        <th><label for="customer_date">Ngày đặt hàng</label></th>
                        <td><?php echo esc_attr($order->created_date);?></td>
                    </tr>
                    <tr>
                        <th><label for="customer_name">Họ tên KH</label></th>
                        <td><input name="customer_name" type="text" id="customer_name" readonly disabled value="<?php echo esc_attr($order->customer_name); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="customer_phone">Số điện thoại</label></th>
                        <td><input name="customer_phone" type="text" id="customer_phone" readonly disabled value="<?php echo esc_attr($order->customer_phone); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="customer_add">Địa chỉ nhận hàng</label></th>
                        <td><textarea rows="3" cols="30" class="regular-text" name="customer_add"><?php echo esc_attr($order->customer_add); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="note">Ghi chú của KH</label></th>
                        <td><textarea rows="3" cols="30" class="regular-text" name="note"><?php echo esc_attr($order->note); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="note">Nhà mạng</label></th>
                        <td class="name-item"><?php echo esc_attr($afc_nha_mang);?></td>
                    </tr>
                    <tr>
                        <th><label for="sim_id">Sim số đã chọn</label></th>
                        <td>
                            <?php 
                            if ($disabled_form) {
                                echo esc_html($order->phone_number); 
                            } else {
                                echo generate_product_select($sim_products, 'sim_id', $order->sim_id);
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="goicuoc_id">Gói cước</label></th>
                        <td>
                            <?php 
                                if($disabled_form){
                                    echo esc_html($order->package_name); 
                                }else{
                                    echo generate_product_select($goi_cuoc_variations, 'goicuoc_id', $order->goicuoc_id); 
                                }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sim_type">Loại hình sim</label></th>
                        <td>
                            <select class="regular-text" name="sim_type">
                                <option value="0" <?php echo $order->sim_type==0 ? "selected" : ""; ?>>Sim vật lý</option>
                                <option value="1" <?php echo $order->sim_type==1 ? "selected" : ""; ?>>Sim eSim</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="sim_price">Giá sim</label></th>
                        <td>
                            <span id="sim_price"><?php echo number_format($order->sim_price, 0, ',', '.'); ?></span>
                            <input type="hidden" name="sim_price" id="sim_price_hidden" class="regular-text" value="<?php echo $order->sim_price?>"> VNĐ
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="goicuoc_price">Giá gói cước</label></th>
                        <td>
                            <span id="goicuoc_price"><?php echo number_format($order->goicuoc_price, 0, ',', '.'); ?></span>
                            <input type="hidden" name="goicuoc_price" id="goicuoc_price_hidden" class="regular-text" value="<?php echo $order->goicuoc_price?>"> VNĐ 
                        </td>
                        
                    </tr>
                    <tr>
                        <th><label for="sim_priceShip">Tiền ship</label></th>
                        <td><input name="sim_priceShip" id="sim_priceShip" type="number" value="<?php echo $order->sim_priceShip ?>" class="regular-text"> VNĐ</td>
                    </tr>
                    <tr>
                        <th><label for="total_price">Tổng thanh toán</label></th>
                        <td class="name-item"><span id="total_price"><?php echo number_format($order->total_price, 0, ',', '.'); ?></span> 
                            <input name="total_price" id="total_price_hidden" type="hidden" value="<?php echo $order->total_price ?>" class="regular-text"> VNĐ</td>
                    </tr>
                    <tr>
                        <th><label for="sales_channel">Kênh bán</label></th>
                        <td class="name-item"><?php echo esc_html($order->sales_channel); ?></td>
                    </tr>
                    <tr>
                        <th><label for="user_id">Nhân viên xử lý</label></th>
                        <td>
                            <select name="user_id" class="regular-text" id="user_id">
                                <option value="">Chọn nhân viên</option>
                                <?php foreach ($users as $user) : ?>
                                    <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($order->user_id, $user->ID); ?>><?php echo esc_html($user->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="user_note">Ghi chú của nhân viên</label></th>
                        <td>
                        <textarea rows="5" cols="30" id="user_note" name="user_note" class="regular-text"><?php echo esc_html($order->user_note); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="order_status">Trạng thái</label></th>
                        <td>
                            <?php if ($order->status != 0) : ?>
                                <input type="hidden" name="order_status" value="<?php echo esc_attr($order->status); ?>" />
                            <?php endif; ?>
                            <select class="regular-text" name="order_status" <?php echo (($order->status != 0) ? "disabled" : ""); ?> >
                                <option value="0" <?php echo ($order->status == 0 ? "selected" : ""); ?>>Chọn trạng thái</option>
                                <option value="-1" <?php echo ($order->status == -1 ? "selected" : ""); ?>>Thất bại</option>
                                <option value="1" <?php echo ($order->status == 1 ? "selected" : ""); ?>>Thành công</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
            <div>
                <?php 
                    if(!$disabled_form){
                        echo '<input type="submit"  class="button button-primary" value="Cập nhật yêu cầu đặt hàng">';
                    }
                ?>
                
            </div>
        </form>
    </div>
    
    <script src="<?php echo (ORDER_URI . 'assets/js/loading-btn.js') ?>"></script>
    <script>
        (function($) {
            var disabledForm = <?php echo json_encode($disabled_form); ?>;

            if(disabledForm){
                $("#form_edit").find("input, select, textarea").prop("disabled", true);
            }

            $('#btn-create_order').on('click', function(e) {
                var btn = $(this);
                toggleLoading(btn, true);
                e.preventDefault();

                let status = '<?php echo esc_js($order->status); ?>';
                if (status == 1) {
                    let confirmMessage = "Sau khi tạo đơn hàng, bạn sẽ không thể chỉnh sửa thông tin của yêu cầu đặt mua này! Bạn chắc chắn muốn tạo đơn không?";
                    if (confirm(confirmMessage)) { // If the user confirms
                        let orderData = {
                            order_id: '<?php echo esc_js($order->id); ?>',
                            cus_phone: '<?php echo esc_js($order->customer_phone); ?>',
                            cus_name: '<?php echo esc_js($order->customer_name); ?>',
                            shipping_address: '<?php echo esc_js($order->customer_add); ?>',
                            phone_number: '<?php echo esc_js($order->phone_number); ?>',
                            package_name: '<?php echo esc_js($order->package_name); ?>',
                            qty: 1,
                            payment_method: '<?php echo esc_js($order->payment_method); ?>',
                            sim_price: '<?php echo esc_js($order->sim_price); ?>',
                            goicuoc_price: '<?php echo esc_js($order->goicuoc_price); ?>',
                            sim_priceShip: '<?php echo esc_js($order->sim_priceShip); ?>',
                            channel: 'Esimdata',
                            order_status: 'waiting_for_delivery',
                            created_by: '<?php echo esc_js($current_user->user_email); ?>'
                        };

                        $.ajax({
                            url: "<?php echo admin_url('admin-ajax.php'); ?>", 
                            method: "POST",
                            data: {
                                action: 'create_order_data', 
                                orderData: orderData,
                                nonce: $("#checkout_nonce").val()
                            },
                            success: function(response) {
                                if(response.success) {
                                    toggleLoading(btn, false);
                                    alert(response.data.message); 
                                } else {
                                    toggleLoading(btn, false);
                                    alert("Error: " + response.data.message);
                                }
                            },
                            error: function(error) {
                                alert("Có lỗi xảy ra!");
                                console.error('Có lỗi xảy ra:', error);
                            }
                        });
                    } else {
                        alert("Bạn đã hủy yêu cầu tạo đơn hàng.");
                        toggleLoading(btn, false);
                    }
                } else {
                    alert("Chỉ được tạo đơn khi yêu cầu được xử lý thành công!");
                }
            });
        })(jQuery);
    </script>



    <?php
}

function generate_product_select($products, $name, $selected_id) {
    $output = '<select class="regular-text" name="' . esc_attr($name) . '" id="' . esc_attr($name) . '">';
    $output .= '<option value="">Chọn sản phẩm</option>';
    foreach ($products as $product) {
        $output .= '<option value="' . esc_attr($product->get_id()) . '" ' . selected($selected_id, $product->get_id(), false) . ' data-price="' . esc_attr($product->get_price()) . '">
            ' . esc_html($product->get_name()) . ' | ' . wp_kses_post(wc_price($product->get_price())) . '
        </option>';
    }
    $output .= '</select>';
    return $output;
}

