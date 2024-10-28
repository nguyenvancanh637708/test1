<?php
// On your thank-you.php template or similar
/* Template Name: thanh toán custom */
session_start();
get_header();
?>
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/assets/css/page-thankyou.css" type="text/css">

<div class="page-thankyou">
    <div class="content">
        <div class="ct-layer">
            <img src="<?php echo get_stylesheet_directory_uri();?>/assets/image/Group-left.svg" alt="">
            <img src="<?php echo get_stylesheet_directory_uri();?>/assets/image/Group-right.svg" alt="">
        </div>
        <div class="ct-head">
            <div class="title">Quý khách đã thanh toán thành công!</div>
            <div class="sub-title">Bạn đã thanh toán <?php echo wc_price(100000); ?> </div>
        </div>
        <div class="ct-body">
            <div class="row-flex">
                <span class="label">Mã đơn hàng</span>
                <span class="value">292929292922929</span>
            </div>
            <div class="row-flex">
                <span class="label">Trạng thái đơn hàng</span>
                <span class="value tag-content">Đã thanh toán</span>
            </div>
            <div class="row-flex">
                <span class="label">Người mua</span>
                <span class="value">Hồng Ngọc</span>
            </div>
            <div class="row-flex">
                <span class="label">Địa chỉ nhận hàng</span>
                <span class="value">33 ngyễn an ninh, tương mai, Hà Nội</span>
            </div>
            <div class="row-flex">
                <span class="label">Số đã đặt</span>
                <span class="value name-item">0988 213 411</span>
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
                <span class="value money"><?php echo wc_price(100000); ?></span>
            </div>
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