<?php 

function edit_don_hang_page() {
    global $wpdb;

    // Lấy ID đơn hàng từ URL
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    
    // Nếu không có ID, thông báo lỗi
    if ($order_id <= 0) {
        echo '<div class="error"><p>Đơn hàng không hợp lệ.</p></div>';
        return;
    }

    // Lấy thông tin đơn hàng từ database
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_esim_order_data WHERE id = %d", $order_id));

    // Nếu không tìm thấy đơn hàng, thông báo lỗi
    if (!$order) {
        echo '<div class="error"><p>Không tìm thấy đơn hàng.</p></div>';
        return;
    }


    // Tạo form chỉnh sửa đơn hàng
    echo '<div class="wrap"><h1>Chỉnh sửa đơn hàng #' . esc_html($order->ma_van_don) . '</h1>';
    echo '<div id="message-box"></div>';
    echo '<form method="POST">';
    echo wp_nonce_field('update_order_esim', 'checkout_nonce');
    ?>
        <table class="form-table">
            <tr>
                <th><label for="created_date">Ngày tạo đơn</label></th>
                <td>
                    <input 
                        name="created_date" 
                        type="date" 
                        id="created_date" 
                        value="<?php echo esc_attr(date('Y-m-d', strtotime($order->created_date))); ?>" 
                        class="regular-text"
                    >
                </td>
            </tr>


            <tr>
                <th><label for="cus_name">Họ tên KH</label></th>
                <td><?php echo esc_attr($order->cus_name);?></td>
            </tr>
            <tr>
                <th><label for="customer_phone">Số điện thoại</label></th>
                <td><?php echo esc_attr($order->cus_phone);?></td>
            </tr>
            <tr>
                <th><label for="shipping_address">Địa chỉ nhận hàng</label></th>
                <!-- <td><textarea rows="3" cols="30" class="regular-text" name="shipping_address"><?php echo esc_attr($order->shipping_address); ?></textarea></td> -->
                <td><?php echo esc_attr($order->shipping_address);?></td>
            </tr>
            <tr>
                <th><label for="shipping_address">Kênh bán</label></th>
                <td><?php echo esc_attr($order->channel);?></td>
            </tr>
            <tr>
                <th><label for="customer_phone">SIM đặt</label></th>
                <td class="name-item">
                    <?php 
                        echo esc_html($order->phone_number); 
                        echo " | Giá: ";
                        echo number_format($order->sim_price, 0, ',', '.');
                    ?>
                </td>

            </tr>
            <tr>
                <th><label for="customer_phone">Loại SIM</label></th>
                <td class="name-item">
                    <?php 
                        echo esc_html($order->package_name); 
                        echo " | Giá: ";
                        echo number_format($order->goicuoc_price, 0, ',', '.');
                    ?>
                </td>
            </tr>
            <tr>
                <th><label for="customer_phone">Tiền Ship</label></th>
                <td class="name-item">
                    <?php 
                        echo number_format($order->feeShip, 0, ',', '.');
                        echo " đ";
                    ?>
                </td>
            </tr>
            <tr>
                <th><label for="customer_phone">Tổng thanh toán</label></th>
                <td class="name-item">
                    <?php 
                        echo number_format($order->total_amount, 0, ',', '.');
                        echo " đ";
                    ?>
                </td>
            </tr>
            <tr>
                <th><label for="payment_method">Hình thức thanh toán</label></th>
                <td><input name="payment_method" type="text" id="payment_method" value="<?php echo esc_attr($order->payment_method); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="payment_method">Trạng thái đơn hàng</label></th>
                <td>
                    <select name="order_status" id="order_status" class="regular-text">
                        <?php 
                             $statuses = [
                                 'waiting_for_delivery' => 'Chờ giao',
                                 'shipped' => 'Đã giao',
                                 'success' => 'Thành công',
                                 'failed' => 'Thất bại',
                                'received_payment' => 'Đã nhận tiền'
                            ];
                            foreach ($statuses as $value => $label) {
                                $selected = ($order->status === $value) ? 'selected' : '';
                                echo "<option value=\"$value\" $selected>$label</option>";
                            }
                        ?>
                    </select>
                </td>
            </tr>
        </table>

    <?php
    echo '<button type="submit"  class="button button-primary btn-submit-cus" data-text="Cập nhật đơn hàng" id="btn-submit">Cập nhật đơn hàng</button> ';
    echo '</form>';
    echo '</div>';
    ?>
    <button id="btn-back" style="margin-top: 12px;" class="button" onclick="window.location.href='<?php echo esc_url(admin_url('admin.php?page=ds-don-hang')); ?>'">Trở về trang danh sách</button>

    <script src="<?php echo (ORDER_URI . 'assets/js/loading-btn.js') ?>"></script>
    <script>
        (function($) {
            $('#btn-submit').on('click', function(e) {
                var btn = $(this);
                toggleLoading(btn, true);
                e.preventDefault();
                let created_date = $("#created_date").val();
                let payment_method = $("#payment_method").val();
                let order_status = $("#order_status").val();
                let orderData = {
                    id: '<?php echo esc_js($order->id); ?>',
                    created_date:created_date,
                    payment_method:payment_method,
                    order_status:order_status,
                };

                $.ajax({
                    url: "<?php echo admin_url('admin-ajax.php'); ?>", 
                    method: "POST",
                    data: {
                        action: 'update_order_esim', 
                        orderData: orderData,
                        nonce: $("#checkout_nonce").val()
                    },
                    success: function(response) {
                        if(response.success) {
                            toggleLoading(btn, false);
                            $("#message-box").append(`<div class="notice notice-success"><p>${response.data.message}</p></div>`);
                        } else {
                            toggleLoading(btn, false);
                            $("#message-box").append(`<div class="error"><p>${response.data.message}</p></div>`);

                        }
                    },
                    error: function(error) {
                        alert("Có lỗi xảy ra!");
                        console.error('Có lỗi xảy ra:', error);
                    }
                });
            });
        })(jQuery);
    </script>
    <?php
}
