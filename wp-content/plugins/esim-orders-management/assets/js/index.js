(function($) {
    $("#sim_id").on('change', function() {
        let price = $(this).find('option:selected').data('price'); 
        let formattedPrice = Number(price).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
        $('#sim_price').text(formattedPrice);
        $('#sim_price_hidden').val(price);
        calculateTotal(); 
    });

    // Event handler for Goicuoc selection change
    $("#goicuoc_id").on('change', function() {
        let price = $(this).find('option:selected').data('price'); 
        let formattedPrice = Number(price).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
        $('#goicuoc_price').text(formattedPrice);
        $('#goicuoc_price_hidden').val(price);
        calculateTotal(); 
    });

    $('#sim_priceShip').on('change', calculateTotal);

    function calculateTotal() {
        let simPrice = Number($('#sim_price_hidden').val()) || 0;
        let goiCuocPrice = Number($('#goicuoc_price_hidden').val()) || 0;
        let shippingPrice = Number($('#sim_priceShip').val()) || 0;
        let total = simPrice + goiCuocPrice + shippingPrice;
        let formattedPrice = Number(total).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
        $('#total_price').text(formattedPrice);
        $('#total_price_hidden').val(total);
    }
})(jQuery);
