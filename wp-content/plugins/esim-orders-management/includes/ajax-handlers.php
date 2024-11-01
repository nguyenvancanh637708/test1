<?php
if (!defined('ABSPATH')) {
    exit;
}



add_action('wp_ajax_update_order_esim', 'update_order_esim_callback');
add_action('wp_ajax_nopriv_update_order_esim', 'update_order_esim_callback'); // Nếu cần cho người dùng không đăng nhập

function update_order_esim_callback() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'update_order_esim')) {
        wp_send_json_error(['message' => 'Nonce không hợp lệ.']);
        wp_die();
    }

    if (isset($_POST['orderData'])) {
        global $wpdb; 
        $order_data = $_POST['orderData'];
        $order_id = intval($order_data['id']);
        $created_date = sanitize_text_field($order_data['created_date']);
        $payment_method = sanitize_text_field($order_data['payment_method']);
        $order_status = sanitize_text_field($order_data['order_status']);

        $updated = $wpdb->update(
            $wpdb->prefix . 'esim_order_data', 
            [
                'created_date' => $created_date,
                'payment_method' => $payment_method,
                'status' => $order_status,
            ],
            ['id' => $order_id] 
        );

        if ($updated !== false) {
            wp_send_json_success(['message' => 'Cập nhật thành công!']);
        } else {
            wp_send_json_error(['message' => 'Cập nhật không thành công.']);
        }
    } else {
        wp_send_json_error(['message' => 'Dữ liệu không hợp lệ.']);
    }

    wp_die();
}


// Register AJAX actions
// add_action('wp_ajax_create_order_data', 'create_order_handler');
// add_action('wp_ajax_nopriv_create_order_data', 'create_order_handler');

// đang không sử dụng chức năng tạo đơn hàng trên web mà chỉ lấy đơn hàng qua đồng bộ
function create_order_handler() {
    // Verify nonce
    if (!check_ajax_referer('create_order_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Invalid nonce. Request failed.'));
        wp_die(); // Stop further execution
    }

    // Verify if the orderData is set
    if (!isset($_POST['orderData'])) {
        wp_send_json_error(array('message' => 'No order data received.'));
        exit;
    }
    $orderData = $_POST['orderData'];
    $order_id = intval($orderData['order_id']);
    $cus_phone = sanitize_text_field($orderData['cus_phone']);
    $cus_name = sanitize_text_field($orderData['cus_name']);
    $shipping_address = sanitize_textarea_field($orderData['shipping_address']);
    $phone_number = sanitize_text_field($orderData['phone_number']);
    $package_name = sanitize_text_field($orderData['package_name']);
    $qty = intval($orderData['qty']);
    $payment_method = sanitize_text_field($orderData['payment_method']);
    $sim_price = intval($orderData['sim_price']);
    $goicuoc_price = intval($orderData['goicuoc_price']);
    $sim_priceShip = intval($orderData['sim_priceShip']);
    $channel = sanitize_text_field($orderData['channel']);
    $order_status = sanitize_text_field($orderData['order_status']);
    $created_by = sanitize_email($orderData['created_by']);

    $mvd = getUniqueTrackingNumber($order_id);

    $data = array(
        'ma_van_don' => $mvd,
        'order_id' => $order_id,
        'cus_phone' => $cus_phone,
        'cus_name' => $cus_name,
        'shipping_address' => $shipping_address,
        'phone_number' => $phone_number,
        'package_name' => $package_name,
        'qty' => $qty,
        'payment_method' => $payment_method,
        'sim_price' => $sim_price,
        'goicuoc_price' => $goicuoc_price,
        'feeShip' => $sim_priceShip,
        'total_amount' => $sim_price + $goicuoc_price + $sim_priceShip,
        'channel' => $channel,
        'status' => $order_status,
        'created_by' => $created_by,
    );

    global $wpdb;

    $wpdb->query('START TRANSACTION');
    $inserted = $wpdb->insert($wpdb->prefix . 'esim_order_data', $data);
    if ($inserted) {
        $order_data_id = $wpdb->insert_id; 
        $updated = $wpdb->update(
            $wpdb->prefix . 'esim_orders',
            array('order_data_id' => $order_data_id),
            array('id' => $order_id) 
        );
        if ($updated !== false) {
            $wpdb->query('COMMIT');
            wp_send_json_success(array('message' => 'Order created successfully!', 'data' => $data));
        } else {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(array('message' => 'Failed to update order data ID. Transaction rolled back.'));
        }
    } else {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => 'Failed to create order. Transaction rolled back.'));
    }

    wp_die();
}



// tạo mã vận đơn 
function generateTrackingNumber($number) {
    $timestamp = time(); 
    $randomString = strtoupper(bin2hex(random_bytes(2))); 
    $trackingNumber = date('dmY', $timestamp) . '-' . $randomString.$number;
    return $trackingNumber;
}

function getUniqueTrackingNumber($order_id) {
    global $wpdb;

    do {
        $mvd = generateTrackingNumber($order_id);
        $existing_mvd_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . $wpdb->prefix . "esim_order_data WHERE ma_van_don = %s",
            $mvd
        ));
    } while ($existing_mvd_count > 0);

    return $mvd;
}



