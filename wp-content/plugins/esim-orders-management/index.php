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
// register_uninstall_hook(__FILE__, 'customer_plugin_uninstall');

// function customer_plugin_uninstall() {
//     // Xóa tất cả các comment liên quan đến plugin này
//     global $wpdb;

//     // Giả sử rằng các comment liên quan đến plugin này có một meta key đặc biệt hoặc một điều kiện xác định khác
//     $wpdb->query("DELETE FROM {$wpdb->wp_esim_orders} WHERE comment_type = 'comment_post'");

// }

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

	) );
}
add_action('wp_enqueue_scripts', 'my_theme_scripts');

// Thêm mục menu mới vào khu vực quản trị
function my_custom_menu_page() {
    add_menu_page(
        'Danh sách đơn hàng',  // Tiêu đề trang
        'Đơn hàng Esim',  // Tên menu
        'manage_options',          // Khả năng người dùng cần có (quyền)
        'danh-sach-don-hang',     // Slug của menu
        'list_custom_order_html',// Hàm gọi nội dung của trang
        'dashicons-admin-generic',// Icon menu (sử dụng Dashicons)
        60                        // Vị trí menu (số càng nhỏ càng lên trên)
    );
}
// Kết nối hàm thêm menu vào WordPress
add_action( 'admin_menu', 'my_custom_menu_page' );

// Hàm xuất nội dung cho trang quản trị
function list_custom_order_html() {
    // Kiểm tra quyền truy cập của người dùng
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>

   <!-- Nội dung trang quản trị -->
    <div class="wrap">
        <h1 class="wp-heading-inline">Danh sách đơn hàng</h1>
        <!-- <ul class="subsubsub">
            <li class="all"><a href="">Tất cả <span class="count">(0)</span></a> |</li>
        </ul> -->
        <form id="orders-filter" method="get">
            <div class="tablenav top">
                <div class="alignleft actions">
                    <span>Thời gian đặt hàng:</span>
                    <span>từ ngày </span><input type="date">
                    <span> đến ngày </span><input type="date"> 
                </div>
                <div class="alignleft actions">
                    <select name="simType">
                        <option value="">Chọn loại sim</option>
                    </select>
                    <select name="simType">
                        <option value="">Kênh bán</option>
                        <option>Esimdata</option>
                        <option>Landing</option>
                    </select>
                    <input type="text" placeholder="Số điện thoại">
                    <input type="text" placeholder="Nhân viên gọi">
                    <input type="submit" name="filter_action" id="order-query-submit" class="button" value="Lọc">
                </div>
            </div>
            <table class="wp-list-table widefat fixed striped table-view-list orders wc-orders-list-table wc-orders-list-table-shop_order">
			    <thead>
	                <tr>
		                <th id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox"></th>
                        <th scope="col" class="manage-column column-order_number column-primary sortable desc">
                            <a>
                                <span>Thời gian đặt</span>
                            </a>
                        </th>
                        <th>
                            <a>
                                <span>Họ tên KH</span>
                            </a>    
                        </th>
                        <th>
                            <a>
                                <span>Số điện thoại</span>
                            </a>
                        </th>
                        <th>
                            <a>
                                <span>Gói cước</span>
                            </a>
                            </th>
                        <th>
                        <a>
                                <span>Sim số chọn</span>
                            </a></th>
                        <th>
                            <a>
                                <span>Giá sim</span>
                            </a>
                        </th>
                        <th>
                            <a>
                                <span>Giá gói cước</span>
                            </a>
                        </th>
                        
                        <th>
                        <a>
                                <span>Tổng thanh toán</span>
                            </a></th>
                        <th>
                        <a>
                                <span>Kênh bán</span>
                            </a></th>
                        <th>
                        <a>
                                <span>Nhân viên gọi</span>
                            </a></th>
                        <th>
                        <a>
                                <span>Ghi chú</span>
                            </a></th>
                        <th>
                        <a>
                                <span>Trạng thái</span>
                            </a></th>

                        <th><a><span></span></a></th>
                            
                    </tr>
	            </thead>
	            <tbody id="the-list" data-wp-lists="list:order">
                    <?php
                        global $wpdb;
                        $table_name = 'wp_esim_orders';
                        $orders = $wpdb->get_results("SELECT * FROM $table_name");
                        if ($orders) {
                            foreach ($orders as $order) {?>
                                <tr>
                                    <td></td>
                                    <td><?php echo $order->created_date?></td>
                                    <td><?php echo $order->customer_name?></td>
                                    <td><?php echo $order->customer_phone?></td>
                                    <td><?php echo wc_get_product($order->goicuoc_id)->name?></td>
                                    <td><?php echo wc_get_product($order->sim_id)->name?></td>
                                    <td><?php echo number_format($order->sim_price, 0, ',', '.')?></td>
                                    <td><?php echo number_format($order->goicuoc_price, 0, ',', '.')?></td>
                                    <td><strong><?php echo number_format($order->total_price, 0, ',', '.')?></strong></td>
                                    <td><?php echo $order->sales_channel?></td>
                                    <td>
                                        <?php echo $order->user_id?>
                                    </td>
                                    <td>
                                    <?php echo $order->note?>

                                    </td>
                                    <td>
                                    <?php echo $order->status?>
                                    </td>
                                    <td>
                                        <button type="button" class="button">Xem</button>
                                        <button type="button" class="button">Sửa</button>
                                    </td>
                                </tr>
                            <?php } 
                            }?>
                        
                </tbody>
	            <tfoot>
	
	            </tfoot>

            </table>
        </form>

    </div>
   
<?php } ?>




      
