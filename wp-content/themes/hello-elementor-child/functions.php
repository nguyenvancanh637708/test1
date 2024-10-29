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
    if (!isset($_POST['checkout_nonce']) || !wp_verify_nonce($_POST['checkout_nonce'], 'checkout_payment')) {
        wp_send_json_error('Nonce verification failed');
        wp_die();
    }

    $products = isset($_POST['products']) ? $_POST['products'] : [];
    global $wpdb;

    $province = isset($_POST['province']) ? sanitize_text_field($_POST['province']) : '';
    $district = isset($_POST['district']) ? sanitize_text_field($_POST['district']) : '';
    $ward = isset($_POST['ward']) ? sanitize_text_field($_POST['ward']) : '';
    $detailed_address = isset($_POST['detailed_address']) ? sanitize_text_field($_POST['detailed_address']) : '';
    $sim_type = isset($_POST['sim_type']) ? sanitize_text_field($_POST['sim_type']) : '';
    $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
    $customer_name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
    $customer_phone = isset($_POST['customer_phone']) ? sanitize_text_field($_POST['customer_phone']) : '';

    $table_name = 'wp_esim_orders';
    $order_inserted = false;
    $feeShip = 0;

    $wpdb->query('START TRANSACTION');
    try {
        foreach ($products as $product) {
            $sim_id = sanitize_text_field($product['sim_id']);
            $goicuoc_id = sanitize_text_field($product['goicuoc_id']);
            $chuky = sanitize_text_field($product['chuky']);
            
            $sim_product = wc_get_product($sim_id);
            $goicuoc_product = wc_get_product($goicuoc_id);

            if ($sim_product && $goicuoc_product) {
                // Kiểm tra xem `phone_number` đã tồn tại chưa với status != -1 // thất bại
                $phone_number = $sim_product->get_name();
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE phone_number = %s AND status != %d",
                    $phone_number, -1
                ));

                if ($exists) {
                    $wpdb->query('ROLLBACK');
                    wp_send_json_error('Số điện thoại đã được đặt bởi người khác! Vui lòng chọn số khác!');
                    wp_die();
                }

                // Dữ liệu cần lưu cho mỗi cặp sản phẩm
                $data = [
                    'customer_name' => $customer_name,
                    'customer_phone' => $customer_phone,
                    'customer_add' => $detailed_address . ', ' . $ward . ', ' . $district . ', ' . $province,
                    'sim_id' => $sim_id,
                    'phone_number' => $phone_number,
                    'sim_price' => $sim_product->get_price(),
                    'goicuoc_id' => $goicuoc_id,
                    'package_name' => $goicuoc_product->get_name(),
                    'goicuoc_price' => $goicuoc_product->get_price(),
                    'sim_type' => $sim_type,
                    'package_cycle' => $chuky,
                    'sim_priceShip' => $feeShip,
                    'total_price' => $sim_product->get_price() + $goicuoc_product->get_price() + $feeShip,
                    'sales_channel' => 'Esimdata',
                    'status' => 0,
                ];

                $result = $wpdb->insert($table_name, $data);

                if ($result === false) {
                    throw new Exception('Insert failed.');
                }

                $order_inserted = true;
            }
        }

        $wpdb->query('COMMIT');
        wp_send_json_success('Insert successful.');
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error('Transaction failed: ' . $e->getMessage());
    }
    wp_die();
}


