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

require_once plugin_dir_path(__FILE__) . 'wp-tables/create-table-db.php';
register_activation_hook(__FILE__, 'create_esim_tables');


define( 'ORDER_URI', plugin_dir_url( __FILE__ ) );
define( 'ORDER', plugin_dir_path( __FILE__ ) );
define( 'ORDER_VERSION', '1.1' );



function my_theme_scripts() {
    wp_enqueue_script('jquery', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js', array(), null, true);
  // Thêm CSS và JS của Select2
  wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
  wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), null, true);

    wp_enqueue_style( 'order', ORDER_URI . 'assets/css/index.css');
    wp_enqueue_script( 'order', ORDER_URI . 'assets/js/index.js', array( 'jquery' ), ORDER_VERSION, true );
    wp_localize_script( 'order', 'order_obj', array(
        'ajax_url'   => admin_url( 'admin-ajax.php' ),
        'order_nonce' => wp_create_nonce( 'order-nonce' )
    ));
     // Khởi tạo Select2 cho #sales_channel
     wp_add_inline_script('select2-js', "
     jQuery(document).ready(function($) {
         $('#goicuoc_ids').select2({
             allowClear: true,
             placeholder: 'Loại gói cước',
         });

         $('#user_ids').select2({
             allowClear: true,
             placeholder: 'Nhân viên tư vấn',
         });

         $('#sim_id').select2({
             placeholder: 'chọn số điện thoại',
         });
         
     });
 ");
    
}
add_action('wp_enqueue_scripts', 'my_theme_scripts');
add_action('admin_enqueue_scripts', 'my_theme_scripts'); 

require_once plugin_dir_path(__FILE__) . 'api-sync.php';

require_once plugin_dir_path(__FILE__) . 'wp-tables/class-custom-order-list-table.php';
require_once plugin_dir_path(__FILE__) . 'wp-tables/class-custom-customer-list-table.php';
require_once plugin_dir_path(__FILE__) . 'wp-tables/class-custom-history-sync-table.php';


require_once plugin_dir_path(__FILE__) . 'includes/dashboard.php';

require_once plugin_dir_path(__FILE__) . 'includes/page-kh-dat-sim.php';
require_once plugin_dir_path(__FILE__) . 'includes/page-edit-kh-dat-sim.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';

require_once plugin_dir_path(__FILE__) . 'includes/page-don-hang.php';
require_once plugin_dir_path(__FILE__) . 'includes/page-edit-don-hang.php';

require_once plugin_dir_path(__FILE__) . 'includes/page-api-keys.php';
require_once plugin_dir_path(__FILE__) . 'includes/page-history-sync.php';

require_once plugin_dir_path(__FILE__) . 'revenue-report.php';
require_once plugin_dir_path(__FILE__) . 'revenue-report-daily.php';





function my_custom_menu_page() {
    // Tạo menu chính "QL ESIM"
    add_menu_page(
        'QL ESIM',                           // Tên menu
        'QL ESIM',                           // Tiêu đề hiển thị trên trang admin
        'manage_options',                    // Quyền truy cập
        'esim-dashboard',                    // Định danh menu (trỏ đến submenu đầu tiên)
        'esim_dashboard_page',                // Hàm để hiển thị nội dung submenu
        'dashicons-cart',                    // Biểu tượng menu
        2                                    // Vị trí trong menu
    );
    // Tạo submenu "dashboard"
    add_submenu_page(
        'esim-dashboard',                     // Định danh menu chính
        'Dashboard',                     // Tên submenu
        'Dashboard',                     // Tiêu đề hiển thị trên trang admin
        'manage_options',                     // Quyền truy cập
        'esim-dashboard',                     // Định danh submenu
        'esim_dashboard_page'                 // Hàm để hiển thị nội dung
    );

    // Tạo submenu ""
    add_submenu_page(
        'esim-dashboard',                     // Định danh menu chính
        'DS KH đặt sim',                     // Tên submenu
        'DS KH đặt sim',                     // Tiêu đề hiển thị trên trang admin
        'manage_options',                     // Quyền truy cập
        'ds-kh-dat-sim',                     // Định danh submenu
        'ds_kh_dat_sim_page'                 // Hàm để hiển thị nội dung
    );

    // Tạo submenu "DS đơn hàng"
    add_submenu_page(
        'esim-dashboard',                     // Định danh menu chính
        'DS đơn hàng',                       // Tên submenu
        'DS đơn hàng',                       // Tiêu đề hiển thị trên trang admin
        'manage_options',                     // Quyền truy cập
        'ds-don-hang',                       // Định danh submenu
        'ds_don_hang_page'                  // Hàm để hiển thị nội dung submenu
    );

    
    add_submenu_page(
        'esim-dashboard',                     // Định danh menu chính
        'Quản lý API KEY',              // Tên submenu
        'Quản lý API KEY',                         // Tiêu đề hiển thị trên trang admin
        'manage_options',      
        'esim-api-keys',     // Slug menu
        'esim_api_keys_page' // Hàm gọi để hiển thị nội dung
    );

    add_submenu_page(
        'esim-dashboard',                     // Định danh menu chính
        'Lịch sử đồng bộ',              // Tên submenu
        'Lịch sử đồng bộ',                         // Tiêu đề hiển thị trên trang admin
        'manage_options',      
        'history-sync',     // Slug menu
        'esim_sync_history_page' // Hàm gọi để hiển thị nội dung
    );

    add_submenu_page(
        'esim-dashboard',
        'Báo cáo doanh thu',
        'Doanh thu theo tháng',
        'manage_options',
        'bao-cao-doanh-thu',
        'render_revenue_report_page'
    );

    add_submenu_page(
        'esim-dashboard',
        'Báo cáo doanh thu theo ngày',
        'Doanh thu theo ngày',
        'manage_options',
        'bao-cao-doanh-thu-theo-ngay',
        'render_daily_revenue_report_page'
    );

    add_submenu_page(
        'esim-dashboard',                     // Định danh menu chính
        'Chỉnh sửa khách hàng',              // Tên submenu
        'Chỉnh sửa KH',                         // Tiêu đề hiển thị trên trang admin
        'manage_options',                     // Quyền truy cập
        'edit-kh-dat-sim',                   // Định danh submenu
        'edit_kh_dat_sim_page'               // Hàm để hiển thị nội dung chỉnh sửa
    );
    add_submenu_page(
        'esim-dashboard',                     // Định danh menu chính
        'Chỉnh sửa đơn hàng',              // Tên submenu
        'Chỉnh sửa đơn hàng',                         // Tiêu đề hiển thị trên trang admin
        'manage_options',                     // Quyền truy cập
        'edit-don-hang',                   // Định danh submenu
        'edit_don_hang_page'               // Hàm để hiển thị nội dung chỉnh sửa
    );
}
add_action('admin_menu', 'my_custom_menu_page');


// Hàm hiển thị nội dung cho "DS đơn hàng"
function update_customer_order_info_html1() {
    echo '<h1>Danh sách đơn hàng</h1>';
}

