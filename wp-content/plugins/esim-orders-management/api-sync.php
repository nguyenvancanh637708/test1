<?php

// API endpoint kiểm tra API key
add_action('rest_api_init', function () {
    register_rest_route('esim/v1', '/sync', array(
        'methods' => 'POST',
        'callback' => 'esim_sync_orders',
        'permission_callback' => '__return_true',
    ));
});

// Hàm kiểm tra API key
function esim_check_api_key($request) {
    global $wpdb;
    $api_key = $request->get_header('x-api-key'); 
    $table_name = $wpdb->prefix . 'esim_api_keys'; 

    $valid_api_key = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE api_key = %s AND status = 'active' AND expires_at > NOW()",
        $api_key
    ));

    // Ghi log API key hợp lệ (nếu có)
    if ($valid_api_key) {
        error_log('Valid API key: ' . $valid_api_key->api_key);
        return true; 
    } else {
        error_log('Invalid API key: ' . $api_key);
        return false; 
    }
}

function esim_sync_orders($request) {
    // Kiểm tra tính hợp lệ của API key
    if (!esim_check_api_key($request)) {
        log_sync_action('', '', 'Invalid API key', $request->get_json_params(), 'error');
        return new WP_REST_Response(['error' => 'Invalid API key'], 403);
    }

    // Lấy dữ liệu từ yêu cầu
    $data = $request->get_json_params();
    
    // Lấy landing_id và kiểm tra tính hợp lệ
    $landing_id = isset($data['landing_id']) ? sanitize_text_field($data['landing_id']) : '';
    if (empty($landing_id)) {
        log_sync_action($landing_id, null, 'Landing ID is required', $data, 'error');
        return new WP_REST_Response(['error' => 'Landing ID is required'], 400);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'esim_order_data';
    $log_table = $wpdb->prefix . 'esim_order_data_sync'; // Bảng ghi log

    // Kiểm tra xem có bản ghi nào với landing_id
    $existing_record = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE landing_id = %s", $landing_id));

    // Nếu bản ghi tồn tại, cập nhật bản ghi
    if ($existing_record) {
        $update_data = [
            'ma_van_don' => sanitize_text_field($data['ma_van_don']),
            'delivery_date' => sanitize_text_field($data['delivery_date']),
            'cus_phone' => sanitize_text_field($data['cus_phone']),
            'cus_name' => sanitize_text_field($data['cus_name']),
            'shipping_address' => sanitize_text_field($data['shipping_address']),
            'cus_type' => sanitize_text_field($data['cus_type']),
            'phone_number' => sanitize_text_field($data['phone_number']),
            'package_name' => sanitize_text_field($data['package_name']),
            'payment_method' => sanitize_text_field($data['payment_method']),
            'sim_price' => floatval($data['sim_price']),
            'feeShip' => floatval($data['feeShip']),
            'total_amount' => floatval($data['total_amount']),
            'status' => sanitize_text_field($data['status']),
        ];

        // Cập nhật bản ghi
        $updated = $wpdb->update($table_name, $update_data, ['landing_id' => $landing_id]);
        if ($updated !== false) {
            log_sync_action($landing_id, $existing_record->id, 'success', $data, 'Cập nhật đơn hàng có ID: '.$existing_record->id); // Ghi log hành động
            return new WP_REST_Response(['success' => true, 'message' => 'Record updated successfully'], 200);
        } else {
            log_sync_action($landing_id, $existing_record->id, 'error', $data, 'Cập nhật đơn hàng có ID: '.$existing_record->id);
            return new WP_REST_Response(['error' => 'Failed to update record'], 500);
        }
    } else {
        // Nếu không có bản ghi, thêm mới
        $insert_data = [
            'landing_id' => $landing_id,
            'ma_van_don' => sanitize_text_field($data['ma_van_don']),
            'created_date' => sanitize_text_field($data['created_date']),
            'delivery_date' => sanitize_text_field($data['delivery_date']),
            'cus_phone' => sanitize_text_field($data['cus_phone']),
            'cus_name' => sanitize_text_field($data['cus_name']),
            'shipping_address' => sanitize_text_field($data['shipping_address']),
            'cus_type' => sanitize_text_field($data['cus_type']),
            'phone_number' => sanitize_text_field($data['phone_number']),
            'package_name' => sanitize_text_field($data['package_name']),
            'qty' => intval($data['qty']), // Chuyển đổi sang số nguyên
            'payment_method' => sanitize_text_field($data['payment_method']),
            'sim_price' => floatval($data['sim_price']),
            'feeShip' => floatval($data['feeShip']),
            'total_amount' => floatval($data['total_amount']),
            'status' => sanitize_text_field($data['status']),
            'created_by' => "API landing",
            'channel' => "landing",
        ];

        $inserted = $wpdb->insert($table_name, $insert_data);
        if ($inserted) {
            $wp_order_id = $wpdb->insert_id; // Lấy ID của bản ghi mới
            log_sync_action($landing_id, $wp_order_id, 'success', $data, 'Tạo mới đơn hàng có ID: '.$wp_order_id); // Ghi log hành động
            return new WP_REST_Response(['success' => true, 'message' => 'Record added successfully'], 201);
        } else {
            log_sync_action($landing_id, null, 'error', $data, 'Tạo mới đơn hàng có ID: '.$wp_order_id);
            return new WP_REST_Response(['error' => 'Failed to add record'], 500);
        }
    }
}

// Hàm ghi log
function log_sync_action($landing_id, $wp_order_id, $status, $data, $action) {
    global $wpdb;
    $log_table = $wpdb->prefix . 'esim_order_data_sync'; // Bảng ghi log

    // Chuyển đổi dữ liệu thành JSON
    $json_data = json_encode($data);

    $wpdb->insert($log_table, [
        'landing_id' => intval($landing_id),
        'wp_order_id' => $wp_order_id ? intval($wp_order_id) : null,
        'status' => sanitize_text_field($status),
        'response' => $json_data, // Lưu dữ liệu JSON
        'synced_at' => current_time('mysql'), // Lưu thời gian hiện tại
        'action' => sanitize_text_field($action), // Lưu hành động
    ]);
}
