<?php
/*
Plugin Name: SIM Package Selector
Plugin URI: https://yourwebsite.com
Description: Chọn gói cước khi thêm SIM vào giỏ hàng
Version: 1.0
Author: Your Name
Author URI: https://yourwebsite.com
*/

// Chặn truy cập trực tiếp vào tệp
if (!defined('ABSPATH')) {
    exit;
}

// Thêm script và style cho popup
function sim_package_selector_enqueue_scripts()
{
    wp_enqueue_script('jquery');
    wp_enqueue_script('sim-package-selector', plugin_dir_url(__FILE__) . 'js/sim-package-selector.js', array('jquery'), '1.0', true);
    wp_enqueue_style('sim-package-selector', plugin_dir_url(__FILE__) . 'css/sim-package-selector.css');
}
add_action('wp_enqueue_scripts', 'sim_package_selector_enqueue_scripts');

// Thêm HTML popup vào footer
function sim_package_selector_popup_html()
{
?>
<div id="package-popup" class="popup" style="display:none;">
    <div class="popup-overlay"></div>
    <div class="popup-content">
        <button class="close-popup" aria-label="Close Popup" title="Đóng">✖</button>
        <h2>Chọn gói cước</h2>
        <div id="package-carousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner" id="carousel-items"></div>
            <button class="carousel-control-prev" type="button" data-bs-target="#package-carousel" data-bs-slide="prev">
            <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/image/prev.svg" alt="prev">
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#package-carousel" data-bs-slide="next">
            <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/image/next.svg" alt="next">
                <span class="visually-hidden">Next</span>
            </button>
        </div>
        <div class="payment-group">
        <button class="button add-package">Thêm vào giỏ hàng</button>
        <button class="button proceed-to-checkout">Tiến hành thanh toán</button>
        </div>
    </div>
</div>
<?php
}
add_action('wp_footer', 'sim_package_selector_popup_html');

// Xử lý AJAX lấy gói cước theo nhà mạng
add_action('wp_ajax_get_packages_by_network', 'get_packages_by_network');
add_action('wp_ajax_nopriv_get_packages_by_network', 'get_packages_by_network');

function get_packages_by_network()
{
    $network_provider = isset($_POST['network_provider']) ? sanitize_text_field($_POST['network_provider']) : '';

    // Truy vấn các sản phẩm gói cước
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => 'goi-cuoc',
            ),
        ),
        'meta_query' => array(
            array(
                'key' => 'nha_mang',
                'value' => $network_provider,
                'compare' => '='
            ),
        ),
    );

    $query = new WP_Query($args);
    $carousel_items = '';
    $items_per_slide = 3; // Số gói cước hiển thị cùng lúc
    $counter = 0;

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            $description = $product->get_description();

            // Kiểm tra xem sản phẩm có biến thể không
            if ($product->is_type('variable')) {
                $available_variations = $product->get_available_variations();
                foreach ($available_variations as $variation) {
                    $variation_id = $variation['variation_id'];
                    $variation_name = implode(', ', array_map(function ($attr) {
                        return $attr;
                    }, $variation['attributes']));
                    $price = wc_price($variation['display_price']); // Lấy giá của biến thể
                    // Chuyển đổi "-thang" thành " tháng" và quy đổi tháng sang ngày
                    $variation_name = str_replace('-thang', ' tháng', $variation_name);
                    if (preg_match('/(\d+) tháng/', $variation_name, $matches)) {
                        $month_value = intval($matches[1]); // Lấy số tháng
                        $days = $month_value * 30; // Quy đổi tháng sang ngày
                        $variation_name = "$days ngày"; // Cập nhật tên biến thể
                    }

                    // Tạo HTML cho gói cước
                    if ($counter % $items_per_slide === 0) {
                        if ($counter > 0) {
                            $carousel_items .= '</div></div>'; // Kết thúc item carousel trước đó
                        }
                        $carousel_items .= '<div class="carousel-item ' . ($counter === 0 ? 'active' : '') . '">
                            <div class="d-flex">'; // Sử dụng flexbox để sắp xếp gói cước
                    }

                    $carousel_items .= '<div class="package-item" data-id="' . $variation_id . '">
                        <h5>Gói ' . $product->get_name() . ' </h5>
                        <h5>'  . $price . '/' . $variation_name . '</h5>
                        <p>' . wp_kses_post($description) . '</p>
                        <img class="check-icon" src="' . get_stylesheet_directory_uri() . '/assets/image/check-checkout.svg" alt="check-checkout" style="display:none;">
                    </div>';

                    $counter++;
                }
            } else {
                $price = wc_price($product->get_price()); // Lấy giá của sản phẩm
                if ($counter % $items_per_slide === 0) {
                    if ($counter > 0) {
                        $carousel_items .= '</div></div>'; // Kết thúc item carousel trước đó
                    }
                    $carousel_items .= '<div class="carousel-item ' . ($counter === 0 ? 'active' : '') . '">
                        <div class="d-flex justify-content-around">'; // Sử dụng flexbox để sắp xếp gói cước
                }

                $carousel_items .= '<div class="package-item" data-id="' . get_the_ID() . '">
                    <h5>' . get_the_title() . ' (' . $price . ')</h5>
                    <p>' . wp_kses_post($description) . '</p>
                </div>';

                $counter++;
            }
        }

        // Kết thúc item carousel cuối cùng
        if ($counter > 0) {
            $carousel_items .= '</div></div>'; 
        }

        $carousel_items .= '</div>'; // Kết thúc carousel-inner
        wp_reset_postdata();
    } else {
        $carousel_items = '<div class="carousel-item active"><h5>Không có gói cước nào</h5></div>';
    }

    echo $carousel_items;
    wp_die();
}

// Xử lý AJAX thêm gói cước vào giỏ hàng
add_action('wp_ajax_add_to_cart', 'ajax_add_to_cart');
add_action('wp_ajax_nopriv_add_to_cart', 'ajax_add_to_cart');

function ajax_add_to_cart()
{
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if ($product_id > 0) {
        WC()->cart->add_to_cart($product_id);
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }

    wp_die();
}