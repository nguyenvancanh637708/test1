<?php
// Hàm để tạo bảng
function create_esim_tables() {
    global $wpdb;

    // Tên bảng
    $orders_table = $wpdb->prefix . 'esim_orders'; // bảng KH đặt sim
    $order_data_table = $wpdb->prefix . 'esim_order_data'; // Bảng đơn hàng
    $log_sync_table = $wpdb->prefix . 'esim_order_data_sync'; // bảng lịch sử đồng bộ
    $api_keys_table = $wpdb->prefix . 'esim_api_keys'; // lưu key api

    $sql_orders = "CREATE TABLE IF NOT EXISTS $orders_table (
        id int(20) NOT NULL AUTO_INCREMENT,
        code_request varchar(20) NOT NULL,
        created_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        customer_name varchar(255) NOT NULL,
        customer_phone varchar(10) NOT NULL,
        customer_add text NOT NULL,
        sim_id int(50) DEFAULT NULL,
        goicuoc_id int(11) DEFAULT NULL,
        phone_number varchar(11) NOT NULL,
        package_name varchar(50) NOT NULL,
        sim_type tinyint(1) DEFAULT 0,
        package_cycle int(5) DEFAULT 1,
        sim_price int(11) DEFAULT NULL,
        goicuoc_price int(11) DEFAULT NULL,
        sim_priceShip float DEFAULT NULL,
        total_price float DEFAULT NULL,
        sales_channel varchar(20) NOT NULL DEFAULT 'esimdata',
        user_id int(20) DEFAULT NULL,
        note text DEFAULT NULL,
        call_date datetime DEFAULT CURRENT_TIMESTAMP,
        status int(2) NOT NULL DEFAULT 0,
        user_note text DEFAULT NULL,
        payment_method varchar(50) NOT NULL DEFAULT 'cod',
        order_data_id int(20) DEFAULT NULL COMMENT 'Id đơn hàng được tạo thành',
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $sql_order_data = "CREATE TABLE IF NOT EXISTS $order_data_table (
        id int(20) NOT NULL AUTO_INCREMENT,
        ma_van_don varchar(255) NOT NULL,
        created_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        delivery_date datetime DEFAULT NULL,
        cus_phone varchar(11) NOT NULL,
        cus_name varchar(255) NOT NULL,
        shipping_address text NOT NULL,
        cus_type varchar(255) DEFAULT NULL,
        phone_number varchar(11) NOT NULL,
        package_name varchar(50) NOT NULL,
        qty int(11) NOT NULL DEFAULT 1,
        payment_method varchar(50) NOT NULL DEFAULT 'cod',
        sim_price int(11) NOT NULL,
        goicuoc_price int(11) NOT NULL,
        feeShip int(11) NOT NULL DEFAULT 0,
        total_amount int(11) NOT NULL,
        channel varchar(50) NOT NULL,
        status int(2) NOT NULL COMMENT '0: Chờ giao, 1: Thành công, 2: Thất bại, 3: Đã ship, 4:Đã nhận tiền',
        created_by varchar(255) DEFAULT 'API',
        -- order_id int(20) NOT NULL COMMENT 'id của yêu cầu đặt mua',
        landing_id int(20) NULL COMMENT 'id đơn hàng từ landing',
        serial_number varchar(24) NULL,

        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $sql_log_sync = "CREATE TABLE IF NOT EXISTS $log_sync_table (
        id INT(20) NOT NULL AUTO_INCREMENT,
        landing_id INT(20) NOT NULL,
        wp_order_id INT(20) DEFAULT NULL,  
        synced_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        action TEXT NOT NULL,
        status VARCHAR(50) NOT NULL,
        response TEXT DEFAULT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $sql_api_key = "CREATE TABLE IF NOT EXISTS $api_keys_table (
        id INT(20) NOT NULL AUTO_INCREMENT,
        api_key VARCHAR(64) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL,
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    // Thực thi câu lệnh SQL để tạo bảng
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_orders);
    dbDelta($sql_order_data);
    dbDelta($sql_log_sync);
    dbDelta($sql_api_key);
}
