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

    global $wpdb;

    $products = isset($_POST['products']) ? $_POST['products'] : [];
    $province = sanitize_text_field($_POST['province'] ?? '');
    $district = sanitize_text_field($_POST['district'] ?? '');
    $ward = sanitize_text_field($_POST['ward'] ?? '');
    $detailed_address = sanitize_text_field($_POST['detailed_address'] ?? '');
    $sim_type = sanitize_text_field($_POST['sim_type'] ?? '');
    $payment_method = sanitize_text_field($_POST['payment_method'] ?? '');
    $customer_name = sanitize_text_field($_POST['customer_name'] ?? '');
    $customer_phone = sanitize_text_field($_POST['customer_phone'] ?? '');
    $notes = sanitize_text_field($_POST['notes'] ?? '');

    // Validate phone number format (simple regex example)
    if (!preg_match('/^[0-9]{10,15}$/', $customer_phone)) {
        wp_send_json_error('Invalid phone number format.');
        wp_die();
    }

    $table_name = 'wp_esim_orders';
    $order_inserted = false;
    $feeShip = 0;
    $codes=[];

    $wpdb->query('START TRANSACTION');
    try {
        foreach ($products as $product) {
            $sim_id = sanitize_text_field($product['sim_id'] ?? '');
            $goicuoc_id = sanitize_text_field($product['goicuoc_id'] ?? '');
            $chuky = sanitize_text_field($product['chuky'] ?? '');
            
            $sim_product = wc_get_product($sim_id);
            $goicuoc_product = wc_get_product($goicuoc_id);

            if ($sim_product && $goicuoc_product) {
                // Check if `phone_number` already exists with status != -1
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

                 $code_request = generate_code_request();
                 while ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE code_request = %s", $code_request)) > 0) {
                     $code_request = generate_code_request();
                 }

                $codes[] = $code_request;

                $data = [
                    'code_request' => $code_request, 
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
                    'note' => $notes,
                ];

                $result = $wpdb->insert($table_name, $data);

                if ($result === false) {
                    throw new Exception('Insert failed.');
                }
                $order_inserted = true;
            }
        }
        $wpdb->query('COMMIT');
        // wp_send_json_success('Insert successful.');
        wp_send_json_success($codes); 
    } catch (Exception $e) {
        // Rollback transaction in case of error
        $wpdb->query('ROLLBACK');
        wp_send_json_error('Transaction failed: ' . $e->getMessage());
    }
    wp_die();
}

function generate_code_request() {
    $date = date('dmY');

    $random_string = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3);
    return $date . $random_string;
}




