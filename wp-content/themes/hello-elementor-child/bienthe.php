<?php
/**
 * Template Name: Gói Cước Variations
 */

get_header(); ?>

<div class="container">
    <h1>Sản Phẩm Gói Cước</h1>

    <!-- Form Lọc -->
    <div class="filter-container">
        <h3>Chọn chu kỳ tháng:</h3>
        <div class="button-group">
            <a href="?months=1" class="filter-button">1 tháng</a>
            <a href="?months=3" class="filter-button">3 tháng</a>
            <a href="?months=6" class="filter-button">6 tháng</a>
            <a href="?months=12" class="filter-button">12 tháng</a>
        </div>
    </div>

    <?php
    // Lấy giá trị từ form lọc
    $selected_month = isset($_GET['months']) ? intval($_GET['months']) : '';

    // Bắt đầu vòng lặp để lấy sản phẩm trong danh mục "gói cước"
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1, // Lấy tất cả sản phẩm
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => 'goi-cuoc', // Thay đổi thành slug của danh mục bạn muốn
            ),
        ),
    );

    $products = new WP_Query($args);

    if ($products->have_posts()) {
        echo '<div class="product-variations">';
        while ($products->have_posts()) {
            $products->the_post();
            global $product;

            // Kiểm tra xem sản phẩm có biến thể không
            if ($product->is_type('variable')) {
                $available_variations = $product->get_available_variations();
                echo '<h2>' . get_the_title() . '</h2>'; // Tên sản phẩm

                foreach ($available_variations as $variation) {
                    $variation_id = $variation['variation_id'];
                    
                    // Lấy tên biến thể từ thuộc tính
                    $attributes = $variation['attributes'];
                    $variation_name = '';
                    $month_value = 0; // Khởi tạo biến tháng
                    
                    foreach ($attributes as $attribute_name => $attribute_value) {
                        // Thay thế "-thang" thành " tháng"
                        $attribute_value = str_replace('-thang', ' tháng', $attribute_value);
                        
                        // Chuyển đổi tháng sang ngày
                        if (preg_match('/(\d+) tháng/', $attribute_value, $matches)) {
                            $month_value = intval($matches[1]); // Lấy số tháng
                            $days = $month_value * 30; // Quy đổi tháng sang ngày
                            $variation_name .= $days . ' ngày ';
                        } else {
                            $variation_name .= esc_html($attribute_value) . ' '; // Kết hợp các thuộc tính
                        }
                    }

                    // Kiểm tra xem số tháng có phù hợp với lựa chọn không
                    if ($selected_month && $month_value !== $selected_month) {
                        continue; // Bỏ qua nếu không phù hợp
                    }

                    // Hiển thị thông tin biến thể
                    echo '<div class="variation-item">';
                    echo '<h5>Gói: ' . trim($variation_name) . '</h5>'; // Hiển thị tên biến thể
                    echo '<p>Giá: ' . wc_price($variation['display_price']) . '</p>'; // Hiển thị giá
                    echo '<button class="add-to-cart" data-id="' . esc_attr($variation_id) . '">Thêm vào giỏ hàng</button>';
                    echo '</div>';
                }
            }
        }
        echo '</div>';
    } else {
        echo '<p>Không có sản phẩm nào trong danh mục này.</p>';
    }

    // Đặt lại truy vấn
    wp_reset_postdata();
    ?>
</div>

<style>
    .filter-container {
        margin-bottom: 20px;
    }

    .button-group {
        display: flex;
        overflow-x: auto; /* Cho phép cuộn ngang */
        white-space: nowrap; /* Ngăn ngừa xuống dòng */
    }

    .filter-button {
        background-color: #0073aa; /* Màu nền nút */
        color: white; /* Màu chữ */
        padding: 10px 15px; /* Khoảng cách bên trong */
        margin-right: 10px; /* Khoảng cách giữa các nút */
        text-decoration: none; /* Không gạch chân */
        border-radius: 5px; /* Bo góc */
        transition: background-color 0.3s; /* Hiệu ứng chuyển màu */
    }

    .filter-button:hover {
        background-color: #005177; /* Màu nền khi hover */
    }

    .product-variations {
        margin-top: 20px;
    }

    .variation-item {
        margin-bottom: 20px;
        border: 1px solid #ddd; /* Viền cho biến thể */
        padding: 15px; /* Khoảng cách bên trong */
        border-radius: 5px; /* Bo góc */
    }
</style>

<?php get_footer(); ?>
