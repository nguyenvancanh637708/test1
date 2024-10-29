<?php
/* Template Name: Custom Checkout esim */
get_header();

?>
<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/assets/css/page-checkout.css" type="text/css">

<div class="page-checkout">
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('checkout_payment', 'checkout_nonce'); ?>
        <input type="hidden" id="action-form" name="action" value="process_checkout">
    
        <h1 class="header-title">Thanh toán đơn hàng</h1>
        <div class="row content">
            <div class="left">
                <div class="sim-type box-content">
                    <h2 class="title" >Loại hình SIM</h2>
                    <div class="sim-options">
                        <label class="sim-option">
                            <input type="radio" name="sim_type" value="0" checked> <!--0: sim vật lý-->
                            <span class="sim-label">
                                <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/image/svl.svg" alt="SIM Vật lý">
                                Sử dụng SIM Vật lý
                            </span>

                        </label>
                        <label class="sim-option">
                            <input type="radio" name="sim_type" value="1"> <!--1: esim -->
                            <span class="sim-label">
                                <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/image/esim.svg" alt="eSIM">
                                Sử dụng eSIM
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Thông tin người nhận hàng -->
                <?php
                // Thông tin khách hàng nếu đã đăng nhập
                $logged_in_user_name = '';
                $logged_in_user_phone = '';
                if (is_user_logged_in()) {
                    $current_user = wp_get_current_user();
                    
                    // Nối họ và tên người dùng
                    $logged_in_user_name = esc_html($current_user->first_name . ' ' . $current_user->last_name);
                    $logged_in_user_phone = esc_html($current_user->billing_phone);
                }
                ?>
                <div class="customer-info box-content">
                    <h2 class="title">Thông tin người nhận hàng</h2>
                    <div class="customer-fields">
                        <input type="text" name="customer_name" id="customer_name" placeholder="Họ và tên" value="<?php echo $logged_in_user_name; ?>" required>
                        <input type="text" name="customer_phone" id="customer_phone" placeholder="Số điện thoại" value="<?php echo $logged_in_user_phone; ?>" required>
                    </div>
                </div>

                <!-- Địa chỉ nhận SIM -->
                <div class="sim-address box-content">
                    <h2 class="title">Địa chỉ nhận SIM</h2>
                    <div class="address-fields">
                        <select id="province" name="province" >
                            <option value="">Tỉnh</option>
                        </select>
                        <select id="district" name="district" disabled>
                            <option value="">Quận/Huyện</option>
                        </select>
                        <select id="ward" name="ward" disabled>
                            <option value="">Phường/Xã</option>
                        </select>
                    </div>
                    <input type="text" id="customer-address" placeholder="Địa chỉ chi tiết*" >
                    <textarea id="customer-note" placeholder="Ghi chú"></textarea>
                </div>

                <!-- Phương thức thanh toán -->
                <div class="payment-methods box-content">
                    <h2 class="title">Phương thức thanh toán</h2>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cod" checked>
                            <div class="d-flex align-items-start payment-box">
                                <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/image/payment-cod.svg" width="32px" height="32px" style="margin-right:8px"/>
                                    <div>
                                        <p class="payment-label">Thanh toán khi nhận hàng</p>
                                        <p class="payment-des">Thanh toán bằng tiền mặt khi nhận hàng tại nhà hoặc cửa hàng</p>
                                    </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
            <div class="right">
                <div class="cart-list box-content">
                    <h3 class="title">
                        Đơn hàng
                        <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/image/trash.svg" alt="xoá" class="icon-trash">
                    </h3>
                    <div class="list-item">
                        <?php 
                            $totalAmount = 0;
                            $feeShip = 0;
                            if ( WC()->cart->is_empty() ) {
                                echo '<p>Giỏ hàng của bạn đang trống.</p>';
                            } else {
                                $items = WC()->cart->get_cart();
                                $items = array_values($items); 
                                // Lặp qua giỏ hàng và lấy từng cặp 2 sản phẩm liên tiếp
                                for ( $i = 0; $i < count($items); $i += 2 ) {
                                    if ( isset($items[$i]) && isset($items[$i + 1]) ) {
                                        $product1 = $items[$i]['data'];
                                        $product2 = $items[$i + 1]['data'];

                                        // Kiểm tra xem sản phẩm có phải là biến thể không
                                        $parent_id1 = $product1->get_type() === 'variation' ? $product1->get_parent_id() : $product1->get_id();
                                        $parent_id2 = $product2->get_type() === 'variation' ? $product2->get_parent_id() : $product2->get_id();

                                        // Lấy danh mục từ sản phẩm cha
                                        $product1_categories = wp_get_post_terms($parent_id1, 'product_cat', array('fields' => 'names'));
                                        $product2_categories = wp_get_post_terms($parent_id2, 'product_cat', array('fields' => 'names'));
                                        
                                        $sim = null; 
                                        $goicuoc = null; 
                                        
                                        // Xác định sản phẩm SIM và Gói cước
                                        if ( in_array( 'Sim', $product1_categories ) ) {
                                            $sim = $product1;
                                        } elseif ( in_array( 'Gói cước', $product1_categories ) ) {
                                            $goicuoc = $product1;
                                        }
                                
                                        if ( in_array( 'Sim', $product2_categories ) ) {
                                            $sim = $product2;
                                        } elseif ( in_array( 'Gói cước', $product2_categories ) ) {
                                            $goicuoc = $product2;
                                        }

                                        // Lấy chu kỳ
                                        $chu_ky = 1;
                                        if (isset($goicuoc->get_attributes()['pa_chon-chu-ky'])) {
                                            $chu_ky_full = $goicuoc->get_attributes()['pa_chon-chu-ky'];
                                            $chu_ky_parts = explode('-thang', $chu_ky_full);
                                            $chu_ky = $chu_ky_parts[0]; // Lấy phần trước "-thang"
                                        }

                                        // In ra thông tin sản phẩm nếu đã xác định được
                                        if ( $sim && $goicuoc ) {
                                            // Cộng dồn tổng tiền
                                            $totalAmount += $sim->get_price() + $goicuoc->get_price(); // Cộng giá của SIM và Gói cước

                                            echo '
                                                <div class="combo-item">
                                                    <div>
                                                        <span class="sub-title">Số đã chọn</span>
                                                        <span class="name-product phone">' . esc_html($sim->get_name()) . '</span>
                                                        <input type="hidden" class="sim-id" value="' . esc_attr($sim->get_id()) . '">
                                                    </div>
                                                    <div>
                                                        <span class="sub-title">Loại sim</span>
                                                        <span class="name-product">SIM vật lý</span>
                                                        <span class="price">' . wc_price($sim->get_price()) . '</span>
                                                    </div>
                                                    <div>
                                                        <span class="sub-title">Gói cước</span>
                                                        <span class="name-product">' . esc_html($goicuoc->get_name()) . '</span>
                                                        <input type="hidden" class="goicuoc-id" value="' . esc_attr($goicuoc->get_id()) . '"> 
                                                    </div>
                                                    <div>
                                                        <span class="sub-title">Chu kỳ</span>
                                                        <span class="name-product ">' . $chu_ky . ' tháng</span>
                                                        <span class="price">' . wc_price($goicuoc->get_price()) . '</span>
                                                        <input type="hidden" class="chu_ky_goi_cuoc" value="' . esc_attr($chu_ky) . '"> 
                                                    </div>
                                                </div>
                                            ';
                                        } else {
                                            // Xoá khỏi giỏ hàng nếu không đủ thông tin
                                            // WC()->cart->remove_cart_item($items[$i]['key']);
                                            // WC()->cart->remove_cart_item($items[$i + 1]['key']);
                                        }
                                    }
                                }
                            }
                        ?>
                    </div>
                </div>
                <button class="buy-more">
                    Mua thêm 
                    <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/image/plus.svg" alt="xoá" class="icon-trash">
                </button>

                <div class="box-content use-voucher">
                    <h3 class="title">Nhập mã giảm giá</h3>
                    <div style="margin-top:16px">
                        <input type="text" placeholder="Nhập mã của bạn">
                        <button>Áp dụng</button>
                    </div>
                </div>

                <div class="box-content checking">
                    <h3 class="title">Thanh toán</h3>
                    <div class="checking-item">
                        <span class="sub-title">Tạm tính</span>
                        <span class="price"><?php echo wc_price($totalAmount); ?></span> 
                    </div>
                    <div class="checking-item">
                        <span class="sub-title">Phí vận chuyển</span>
                        <span class="price"><?php echo wc_price($feeShip); ?></span> 
                    </div>
                    <div class="checking-item">
                        <span class="sub-title">Mã giảm giá</span>
                        <span class="price">-<?php echo wc_price(0); ?></span> 
                    </div>
                    <div class="checking-item">
                        <span class="sub-title">Thành tiền</span>
                        <span class="price total"><?php echo wc_price($totalAmount + $feeShip); ?></span>
                    </div>
                    <p class="cus-vat">(Đã bao gồm VAT)</p>
                    <!-- <div class="d-flex">
                        <input type="checkbox" checked />
                        <span>Tôi đã kiểm tra và xác định thông tin thanh toán là chính xác</span>
                    </div> -->
                    <div>
                        <?php 
                            echo '<button id="btn-buy-now" name="submit_payment" type="button" >Thanh toán ngay</button>';
                        ?>
                    </div>
                </div>

            </div>
        </div> 
    </form>
</div>
<script>

    function toggleLoading(btn, isLoading) {
        if (isLoading) {
            btn.prop('disabled', true); 
            btn.addClass('disabled'); 
            btn.html('Đang xử lý <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
            $("input, select, textarea, button").prop('disabled', true).addClass('disabled');
        } else {
            btn.prop('disabled', false); 
            btn.removeClass('disabled'); 
            btn.html('Thanh toán ngay'); 
            $("input, select, textarea, button").prop('disabled', false).removeClass('disabled');
        }
    }

    $("#btn-buy-now").on("click", function(e) {
        var btn = $(this);
        toggleLoading(btn, true); 
        e.preventDefault();
        let products = [];

        // Collect product details
        $('.combo-item').each(function() {
            let sim_id = $(this).find('.sim-id').val();
            let goicuoc_id = $(this).find('.goicuoc-id').val();
            let chu_ky = $(this).find('.chu_ky_goi_cuoc').val();

            products.push({
                sim_id: sim_id,
                goicuoc_id: goicuoc_id,
                chuky: chu_ky,
            });
        });

        if (products.length === 0) {
            alert('Không có sản phẩm nào trong giỏ hàng!');
            toggleLoading(btn, false); 
            return;
        }

        // Gather form inputs
        let customerName = $('#customer_name').val().trim(); 
        let customerPhone = $('#customer_phone').val().trim(); 
        let provinceText = $('#province option:selected').text();
        let districtText = $('#district option:selected').text();
        let wardText = $('#ward option:selected').text();
        let detailedAddress = $('#customer-address').val().trim();
        let notes = $('#customer-note').val().trim();
        let paymentMethod = $('input[name="payment_method"]:checked').val();

        if (!customerName) {
            alert('Vui lòng nhập họ và tên.');
            $('#customer_name').focus();  
            toggleLoading(btn, false); 
            return;
        }

        if (!/^[\p{L} ]+$/u.test(customerName)) {
            alert('Họ và tên không hợp lệ. Chỉ cho phép chữ cái.');
            $('#customer_name').focus(); 
            toggleLoading(btn, false); 
            return;
        }

        if (!customerPhone) {
            alert('Vui lòng nhập số điện thoại.');
            $('#customer_phone').focus();  
            toggleLoading(btn, false); 
            return;
        }

        if (!/^\d{10}$/.test(customerPhone)) {
            alert('Số điện thoại không hợp lệ. Vui lòng nhập 10 chữ số.');
            $('#customer_phone').focus();
            toggleLoading(btn, false); 
            return;
        }

        if (!provinceText || provinceText === "Tỉnh") {
            alert('Vui lòng chọn tỉnh.');
            $('#province').focus().trigger('click');  
            toggleLoading(btn, false); 
            return;
        }

        if (!districtText || districtText === "Quận/Huyện") {
            alert('Vui lòng chọn quận/huyện.');
            $('#district').focus().trigger('click'); 
            toggleLoading(btn, false);  
            return;
        }

        if (!wardText || wardText === "Phường/Xã") {
            alert('Vui lòng chọn phường/xã.');
            $('#ward').focus().trigger('click'); 
            toggleLoading(btn, false); 
            return;
        }

        if (!detailedAddress) {
            alert('Vui lòng nhập địa chỉ chi tiết.');
            $('#customer-address').focus();  
            toggleLoading(btn, false); 
            return;
        }

        let formData = {
            action: $("#action-form").val(),
            checkout_nonce: $("#checkout_nonce").val(),
            products: products,
            customer_name: customerName,
            customer_phone: customerPhone,
            province: provinceText,
            district: districtText,
            ward: wardText,
            detailed_address: detailedAddress,
            notes: notes,
            payment_method: paymentMethod
        };

        $.ajax({
            url: "<?php echo admin_url('admin-post.php'); ?>",
            method: "POST",
            data: formData,
            success: function(response) {
                if(response.success) {
                    alert(response.data); 
                    // todo: redirect page to thankyou or the same
                } else {
                    alert(response.data);
                }
                toggleLoading(btn, false);
            },
            error: function(error) {
                alert("Có lỗi xảy ra!");
                toggleLoading(btn, false);
                console.error('Có lỗi xảy ra:', error);
            }
        });
    });
</script>


<?php
add_action('template_redirect', 'handle_payment_submission');
get_footer();
