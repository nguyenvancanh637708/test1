<?php
function update_customer_order_info_html(){
    global $wpdb;
    $table_name = 'wp_esim_orders';
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    //Lấy ra đơn hàng tương ứng với order_id
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $order_id));
 
    // Kiểm tra nếu không tìm thấy đơn hàng
    if (!$order) {
        echo '<div class="wrap"><h1 class="wp-heading-inline">Đơn hàng không tồn tại</h1></div>';
        return;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        //Cập nhật danh sách đơn hàng khách hàng đặt
        $updated_order = array(
            'customer_add'   => sanitize_text_field($_POST['customer_add']),
            'goicuoc_id' => sanitize_textarea_field($_POST['goicuoc_id']),
            'sim_id' => sanitize_textarea_field($_POST['sim_id']),
            'sim_type' => sanitize_textarea_field($_POST['sim_type']),
            'user_id' => sanitize_textarea_field($_POST['user_id']),
            'note' => sanitize_textarea_field($_POST['note']),
            'status' => sanitize_textarea_field($_POST['order_status']),


        ); 
        var_dump($updated_order);
        $where = array('id' => $order_id);
        // Thực hiện cập nhật dữ liệu
        $wpdb->update($table_name, $updated_order, $where);
        // Chuyển hướng về trang danh sách sau khi cập nhật thành công
        wp_redirect(admin_url('admin.php?page=danh-sach-don-hang'));
        exit;
    }
    $users = get_users();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Chỉnh sửa danh sách đặt hàng</h1>
        <form method="POST" action="">
            <table class="form-table">
                <tr>
                    <th><label for="customer_name">Họ tên KH</label></th>
                    <td><input name="customer_name" type="text" id="customer_name" readonly value="<?php echo esc_attr($order->customer_name); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="customer_phone">Số điện thoại</label></th>
                    <td><input name="customer_phone" type="text" id="customer_phone" readonly value="<?php echo esc_attr($order->customer_phone); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="customer_add">Địa chỉ nhận hàng</label></th>
                    <td><textarea rows="5" cols="30" class="regular-text" name="customer_add"><?php echo esc_attr($order->customer_add); ?></textarea>
                </tr>
                <tr>
                    <th><label for="customer_date">Ngày đặt hàng</label></th>
                    <td><?php echo esc_attr($order->created_date);?></td>
                </tr>
                <tr>
                    <th><label for="sim_id">Sim số đã chọn</label></th>
                    <td>
                        <select name="sim_id" class="regular-text">
                            <option value="<?php echo esc_html($order->sim_id)?>"><?php echo esc_html(wc_get_product($order->sim_id)->get_name()); ?> - <?php echo get_post_meta($order->sim_id, 'nha_mang', true) ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="goicuoc_id">Gói cước</label></th>
                    <td>
                        <select  class="regular-text" name="goicuoc_id">
                            <option value="<?php echo esc_html($order->goicuoc_id)?>"><?php echo esc_html(wc_get_product($order->goicuoc_id)->get_name()); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th><label for="sim_type">Loại hình sim</label></th>
                    <td>
                        <select class="regular-text" name="sim_type">
                            <option><?php echo $order->sim_type==0?"Sim vật lý":"Esim"?></option>
                            <option value="0">Sim vật lý</option>
                            <option value="1">Esim</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="sim_price">Giá sim</label></th>
                    <td><?php echo number_format($order->sim_price, 0, ',', '.'); ?> VNĐ</td>
                </tr>
                
                <tr>
                    <th><label for="goicuoc_price">Giá gói cước</label></th>
                    <td><?php echo number_format($order->goicuoc_price, 0, ',', '.'); ?> VNĐ</td>
                </tr>
                <tr>
                    <th><label for="sim_priceShip">Tiền ship</label></th>
                    <td><?php echo number_format($order->sim_priceShip, 0, ',', '.'); ?> VNĐ</td>
                </tr>
                <tr>
                    <th><label for="total_price">Tổng thanh toán</label></th>
                    <td><strong><?php echo number_format($order->total_price, 0, ',', '.'); ?> VNĐ</strong></td>
                </tr>
                <tr>
                    <th><label for="sales_channel">Kênh bán</label></th>
                    <td><?php echo esc_html($order->sales_channel); ?></td>
                </tr>
                <tr>
                    <th><label for="user_id">Nhân viên gọi</label></th>
                    <td>
                        <select name="user_id" class="regular-text">
                            <option value="<?php echo esc_html($order->user_id==null || $order->user_id==0 ? "0": $order->user_id ); ?>"><?php echo get_user_by('ID', $order->user_id)->display_name;?></option>
                            <?php
                                foreach($users as $user){?>
                                    <option value="<?php echo esc_html($user->ID); ?>"><?php echo $user->display_name;?></option>
                                <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="note">Ghi chú</label></th>
                    <td>
                    <textarea rows="5" cols="30" id="order_note" name="note" class="regular-text"><?php echo esc_html($order->note); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="order_status">Trạng thái</label></th>
                    <td>
                        <select class="regular-text" name="order_status">
                            <?php 
                                if($order->status==0){
                                    echo "<option value='0'>Chọn trạng thái</option>";
                                }else if($order->status==1){
                                    echo "<option value='1'>Thành công</option>";
                                }else{
                                    echo "<option value='-1'>Thất bại</option>";
                                }
                                ?>
                            <option value="-1">Thất bại</option>
                            <option value="1">Thành công</option>
                        </select>
                    </td>
                </tr>
            </table>
            <p class="submit"><button type="submit" class="button button-primary">Lưu</button></p>
        </form>
    </div>
<?php }
?>