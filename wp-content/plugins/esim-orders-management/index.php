<?php
/*
Plugin Name: Order Management Esim
Description: Plugin quản lý danh sách đơn hàng
Version: 1.5
Author: MaiATech
Author URI: https://www.maiatech.com.vn/
*/

if ( !defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
}

define( 'ORDER_URI', plugin_dir_url( __FILE__ ) );
define( 'ORDER', plugin_dir_path( __FILE__ ) );
define( 'ORDER_VERSION', '1.0' );

function my_theme_scripts() {
    if (!is_admin()) {
        wp_deregister_script('jquery');
        wp_enqueue_script('jquery', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js', array(), null, true);
    }
    wp_enqueue_style( 'order', ORDER_URI . 'assets/css/index.css' );
    wp_enqueue_script( 'order', ORDER_URI . 'assets/js/index.js', array( 'jquery' ), ORDER_VERSION, true );
    wp_localize_script( 'order', 'order_obj', array(
        'ajax_url'   => admin_url( 'admin-ajax.php' ),
        'order_nonce' => wp_create_nonce( 'order-nonce' )
    ));
}
add_action('wp_enqueue_scripts', 'my_theme_scripts');

function my_custom_menu_page() {
    add_menu_page(
        'Danh sách đơn hàng',
        'Đơn hàng Esim',
        'manage_options',
        'danh-sach-don-hang',
        'list_custom_order_html',
        'dashicons-admin-generic',
        60
    );
}
add_action( 'admin_menu', 'my_custom_menu_page' );

function list_custom_order_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    global $wpdb;
    $table_name = 'wp_esim_orders';

    $from_date = isset($_GET['from_date']) && !empty($_GET['from_date']) ? sanitize_text_field($_GET['from_date']) : date('Y-m-d', strtotime('-7 days'));
    $to_date = isset($_GET['to_date']) && !empty($_GET['to_date']) ? sanitize_text_field($_GET['to_date']) : date('Y-m-d');
    
    $sales_channel = isset($_GET['sales_channel']) ? sanitize_text_field($_GET['sales_channel']) : '';
    $customer_phone = isset($_GET['customer_phone']) ? sanitize_text_field($_GET['customer_phone']) : '';
    $user_id = isset($_GET['user_id']) ? sanitize_text_field($_GET['user_id']) : '';

    // Xây dựng câu truy vấn dựa trên filter
    $query = "SELECT * FROM $table_name WHERE 1=1";

    if ($from_date) {
        $query .= " AND created_date >= '$from_date'";
    }
    if ($to_date) {
        $to_date_end = $to_date . ' 23:59:59';
        $query .= " AND created_date <= '$to_date_end'";
    }
    
    if ($sales_channel) {
        $query .= " AND sales_channel = '$sales_channel'";
    }
    if ($customer_phone) {
        $query .= " AND customer_phone LIKE '%$customer_phone%'";
    }
    if ($user_id) {
        $query .= " AND user_id LIKE '%$user_id%'";
    }

    $orders = $wpdb->get_results($query);
    ?>

    <div class="wrap">
        <h1 class="wp-heading-inline">Danh sách đơn hàng</h1>
        <form id="orders-filter" method="get">
            <!-- Thêm hidden field để giữ giá trị 'page' -->
            <input type="hidden" name="page" value="danh-sach-don-hang">
            <div class="tablenav top">
                <div class="alignleft actions">
                    <span>Thời gian đặt hàng:</span>
                    <span>từ ngày </span><input type="date" name="from_date" value="<?php echo esc_attr($from_date); ?>">
                    <span> đến ngày </span><input type="date" name="to_date" value="<?php echo esc_attr($to_date); ?>">
                </div>
                <div class="alignleft actions">
                    <select name="sales_channel">
                        <option value="">Kênh bán</option>
                        <option <?php selected($sales_channel, 'Esimdata'); ?> value="Esimdata">Esimdata</option>
                        <option <?php selected($sales_channel, 'Landing'); ?> value="Landing">Landing</option>
                    </select>
                    <input type="text" name="customer_phone" placeholder="Số điện thoại" value="<?php echo esc_attr($customer_phone); ?>">
                    <input type="text" name="user_id" placeholder="Nhân viên gọi" value="<?php echo esc_attr($user_id); ?>">
                    <input type="submit" name="filter_action" id="order-query-submit" class="button" value="Lọc">
                </div>
            </div>
            <table class="wp-list-table widefat fixed striped table-view-list orders wc-orders-list-table wc-orders-list-table-shop_order">
                <thead>
                    <tr>
                        <th class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox"></th>
                        <th>Thời gian đặt</th>
                        <th>Họ tên KH</th>
                        <th>Số điện thoại</th>
                        <th>Gói cước</th>
                        <th>Sim số chọn</th>
                        <th>Giá sim</th>
                        <th>Giá gói cước</th>
                        <th>Tổng thanh toán</th>
                        <th>Kênh bán</th>
                        <th>Nhân viên gọi</th>
                        <th>Ghi chú</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody id="the-list" data-wp-lists="list:order">
                    <?php
                    if ($orders) {
                        foreach ($orders as $order) { ?>
                            <tr>
                                <td><input type="checkbox"></td>
                                <td><?php echo esc_html($order->created_date); ?></td>
                                <td><?php echo esc_html($order->customer_name); ?></td>
                                <td><?php echo esc_html($order->customer_phone); ?></td>
                                <td><?php echo esc_html(wc_get_product($order->goicuoc_id)->get_name()); ?></td>
                                <td><?php echo esc_html(wc_get_product($order->sim_id)->get_name()); ?></td>
                                <td><?php echo number_format($order->sim_price, 0, ',', '.'); ?></td>
                                <td><?php echo number_format($order->goicuoc_price, 0, ',', '.'); ?></td>
                                <td><strong><?php echo number_format($order->total_price, 0, ',', '.'); ?></strong></td>
                                <td><?php echo esc_html($order->sales_channel); ?></td>
                                <td><?php echo esc_html($order->user_id); ?></td>
                                <td><?php echo esc_html($order->note); ?></td>
                                <td><?php echo esc_html($order->status); ?></td>
                                <td>
                                    <button type="button" class="button">Xem</button>
                                    <button type="button" class="button">Sửa</button>
                                </td>
                            </tr>
                        <?php } 
                    } else { ?>
                        <tr><td colspan="13">Không tìm thấy đơn hàng nào.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </form>
    </div>
<?php } ?>
