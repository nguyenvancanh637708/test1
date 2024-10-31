<?php
// On your thank-you.php template or similar
/* Template Name: thanh toán custom */
get_header();
global $wpdb; // Đảm bảo global $wpdb được khai báo
$codes = isset($_GET['code']) ? (array) $_GET['code'] : [];

// Kiểm tra nếu có mã và thực hiện truy vấn
if (!empty($codes)) {
    $placeholders = implode(',', array_fill(0, count($codes), '%s')); 
    // Mã hóa code để không bị hack
    $query = $wpdb->prepare("SELECT * FROM wp_esim_orders WHERE code_request IN ($placeholders)", $codes);
    $results = $wpdb->get_results($query, ARRAY_A);

     if (count($results) < count($codes)) {
        echo '<div class="error-message">Không tồn tại yêu cầu đặt hàng nào với mã đã cung cấp.</div>';
        die(); // Dừng thực thi
    }

} else {
    echo '<div class="error-message">Không có mã yêu cầu nào được cung cấp.</div>';
    die();
}

// Khởi tạo biến trạng thái
$status_class = '';
$status_text = '';

if ($results) {
    foreach ($results as $result) {
        $status = $result['status']; // Lấy trạng thái từ từng đơn hàng
        switch ($status) {
            case 0:
                $status_text .= 'Chờ xử lý, '; // Thêm trạng thái cho đơn hàng
                $status_class .= 'status-pending '; // Thêm lớp cho trạng thái "Chờ xử lý"
                break;
            case 1:
                $status_text .= 'Thành công, '; // Thêm trạng thái cho đơn hàng
                $status_class .= 'status-success '; // Thêm lớp cho trạng thái "Thành công"
                break;
            case -1:
                $status_text .= 'Thất bại, '; // Thêm trạng thái cho đơn hàng
                $status_class .= 'status-failed '; // Thêm lớp cho trạng thái "Thất bại"
                break;
            default:
                $status_text .= 'Không xác định, '; // Thêm trạng thái cho đơn hàng
                $status_class .= 'status-unknown '; // Thêm lớp cho trạng thái không xác định
        }
    }

    // Xóa dấu phẩy và khoảng trắng thừa ở cuối
    $status_text = rtrim($status_text, ', ');
    $status_class = rtrim($status_class);

} 
?>

<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/assets/css/page-thankyou.css" type="text/css">

<div class="page-thankyou">
    <div class="content">
        <div class="ct-layer">
            <img src="<?php echo get_stylesheet_directory_uri();?>/assets/image/Group-left.svg" alt="">
            <img src="<?php echo get_stylesheet_directory_uri();?>/assets/image/Group-right.svg" alt="">
        </div>
        <div class="ct-head">
            <div class="title">Quý khách đã đặt hàng thành công!</div>
            <!-- <div class="sub-title">Bạn đã thanh toán <?php echo wc_price(100000); // Bạn có thể thay đổi số tiền này ?></div> -->
        </div>
        <div class="ct-body">
            <?php if ($results): // Kiểm tra nếu có kết quả ?>
                <div class="row-flex">
                    <span class="label">Mã đơn hàng</span>
                    <span class="value bold">
                        <?php 
                        $codes_array = array_column($results, 'code_request');
                        echo esc_html(implode(', ', $codes_array)); 
                        ?>
                    </span>
                </div>
                <div class="row-flex">
                    <span class="label">Trạng thái đơn hàng</span>
                    <span class="value tag-content <?php echo esc_attr($status_class); ?>">
                        <?php echo esc_html($status_text); ?>
                    </span>
                </div>
                <div class="row-flex">
                    <span class="label">Người mua</span>
                    <span class="value"><?php echo esc_html($results[0]['customer_name']); ?></span>
                </div>
                <div class="row-flex">
                    <span class="label">Địa chỉ nhận hàng</span>
                    <span class="value"><?php echo esc_html($results[0]['customer_add']); ?></span>
                </div>
                <div class="row-flex">
                    <span class="label">Số đã đặt</span>
                    <span class="value name-item">
                        <?php 
                        $phone_numbers = array_column($results, 'phone_number');
                        echo esc_html(implode(', ', $phone_numbers)); 
                        ?>
                    </span>
                </div>
                <div class="row-flex">
                    <span class="label">Phương thức thanh toán</span>
                    <span class="value payment-method">
                        <img src="<?php echo get_stylesheet_directory_uri();?>/assets/image/payment-cod.svg" alt="">
                        COD
                    </span>
                </div>
                <div class="row-flex">
                    <span class="label">Số tiền</span>
                    <span class="value money">
                        <?php 
                        // Tính tổng số tiền
                        $total_price = array_sum(array_column($results, 'total_price'));
                        echo wc_price($total_price); 
                        ?>
                    </span>
                </div>
            <?php else: ?>
                <div class="row-flex">
                    <span class="label">Thông báo</span>
                    <span class="value">Không tìm thấy thông tin đơn hàng.</span>
                </div>
            <?php endif; ?>
            <div class="row-flex">
                <a href="#" class="btn-custom">Liên hệ</a>
                <a href="/" class="btn-custom btn-primary">Trang chủ</a>
            </div>
        </div>
    </div>
</div>

<?php
get_footer();
?>
