jQuery(document).ready(function($) {
    // Khi nhấn nút "Chọn số"
    $('.select-number').click(function() {
        var productId = $(this).data('product-id');
        var phoneNumber = $(this).data('phone-number');

        // Gọi AJAX để lấy các gói cước
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_packages_by_network',
                network_provider: $(this).data('network-provider') // Lấy nhà mạng từ dữ liệu của SIM
            },
            success: function(response) {
                $('#carousel-items').html(response); // Cập nhật danh sách các gói cước trong carousel
                $('#package-popup').fadeIn();
                $('#package-popup').data('product-id', productId);
                $('#package-popup').data('phone-number', phoneNumber);
            }
        });
    });

    // Đóng popup
    $('.close-popup').click(function() {
        $('#package-popup').fadeOut();
    });

    // Thêm biến thể vào giỏ hàng
    $('.add-package').click(function() {
        var selectedVariationId = $('#carousel-items .carousel-item.active .package-item').data('id');
        var productId = $('#package-popup').data('product-id');
        var phoneNumber = $('#package-popup').data('phone-number');

        if (selectedVariationId) {
            // Thêm biến thể vào giỏ hàng
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'add_to_cart',
                    product_id: selectedVariationId,
                    sim_package_selector_nonce_field: $('#package-popup').find('input[name="sim_package_selector_nonce_field"]').val() // Gửi nonce
                },
                success: function() {
                    // Thêm sản phẩm SIM vào giỏ hàng với số điện thoại
                    $.ajax({
                        url: wc_add_to_cart_params.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'add_to_cart',
                            product_id: productId,
                            phone_number: phoneNumber
                        },
                        success: function() {
                            alert('Biến thể và SIM đã được thêm vào giỏ hàng.');
                            $('#package-popup').fadeOut();
                        }
                    });
                },
                error: function() {
                    alert('Có lỗi xảy ra khi thêm vào giỏ hàng.');
                }
            });
        } else {
            alert('Vui lòng chọn một gói cước.');
        }
    });
});
