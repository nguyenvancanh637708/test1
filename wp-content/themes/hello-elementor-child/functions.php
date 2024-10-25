<?php
function hello_elementor_child_enqueue_styles() {
    wp_enqueue_style( 'hello-elementor-parent', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'hello-elementor-child', get_stylesheet_directory_uri() . '/style.css', array( 'hello-elementor-parent' ) );

    wp_enqueue_script( 'jquery' ); 
    if (!is_admin()) {
        wp_deregister_script('jquery');
        wp_enqueue_script('jquery', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js', array(), null, true);
    }
    wp_enqueue_script('custom-ajax', get_stylesheet_directory_uri() . '/assets/js/custom-ajax.js', array('jquery'), null, true);
    
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_enqueue_styles' );


add_action('admin_post_process_checkout', 'handle_checkout_payment');
add_action('admin_post_nopriv_process_checkout', 'handle_checkout_payment');

function handle_checkout_payment() {
    // Kiểm tra nonce để đảm bảo tính bảo mật
    if (!isset($_POST['checkout_nonce']) || !wp_verify_nonce($_POST['checkout_nonce'], 'checkout_payment')) {
        wp_send_json_error('Nonce verification failed');
        wp_die();
    }

    // Lấy dữ liệu từ form
    $products = isset($_POST['products']) ? $_POST['products'] : [];
    $product_data = []; // Mảng để lưu thông tin sản phẩm

    foreach ($products as $product) {
        $sim_id = sanitize_text_field($product['sim_id']);
        $goicuoc_id = sanitize_text_field($product['goicuoc_id']);
        
        // Lấy thông tin sản phẩm SIM từ DB
        $sim_product = wc_get_product($sim_id);
        $goicuoc_product = wc_get_product($goicuoc_id);

        if ($sim_product && $goicuoc_product) {
            // Lưu thông tin sản phẩm vào mảng
            $product_data[] = [
                'sim_id' => $sim_id,
                'sim_name' => $sim_product->get_name(), // Tên sản phẩm SIM
                'sim_price' => $sim_product->get_price(), // Giá sản phẩm SIM
                'goicuoc_id' => $goicuoc_id,
                'goicuoc_name' => $goicuoc_product->get_name(), // Tên sản phẩm Gói cước
                'goicuoc_price' => $goicuoc_product->get_price() // Giá sản phẩm Gói cước
            ];
        }
    }

    // Lấy dữ liệu từ form và kiểm tra xem thông tin đã nhận đúng chưa
    $province = isset($_POST['province']) ? sanitize_text_field($_POST['province']) : '';
    $district = isset($_POST['district']) ? sanitize_text_field($_POST['district']) : '';
    $ward = isset($_POST['ward']) ? sanitize_text_field($_POST['ward']) : '';
    $detailed_address = isset($_POST['detailed_address']) ? sanitize_text_field($_POST['detailed_address']) : '';
    $sim_type = isset($_POST['sim_type']) ? sanitize_text_field($_POST['sim_type']) : '';
    $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
    $customer_name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
    $customer_phone = isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '';

    // Tạo bản ghi lưu vào bảng wp_esim_orders
    global $wpdb;

    // Dữ liệu cần lưu
    $data = [
        'created_date' => current_time('mysql'),
        'customer_name' => $customer_name,
        'customer_phone' => $customer_phone,
        'customer_add' => $detailed_address . ', ' . $ward . ', ' . $district . ', ' . $province,
        'sim_id' => $sim_id,
        'sim_price' => $sim_product->get_price(),
        'goicuoc_id' => $goicuoc_id,
        'goicuoc_price' => $goicuoc_product->get_price(),
        'sim_type' => $sim_type,
        'package_cycle' => 1, 
        'total_price' => $sim_product->get_price() + $goicuoc_product->get_price(),
        'sales_channel' => 'website',
    ];

    // Tên bảng
    $table_name = 'wp_esim_orders';

    $result = $wpdb->insert($table_name, $data);

    if ($result === false) {
        $error = $wpdb->last_error;
        wp_send_json_error('Insert failed: ' . $error);
    } else {
        // Insert thành công
        // todo: Xoá giỏ hàng và giảm số lượng Sim trong kho đi thành hết hàng
        wp_send_json_success('Insert successful. Row ID: ' . $wpdb->insert_id);
    }
    wp_die(); // Kết thúc function
}









