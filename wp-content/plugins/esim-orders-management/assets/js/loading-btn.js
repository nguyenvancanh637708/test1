function toggleLoading(btn, isLoading) {
    if (isLoading) {
        btn.prop('disabled', true); 
        btn.addClass('disabled'); 
        btn.html('Đang xử lý <span class="custom-spinner" aria-hidden="true"></span>');
        

    } else {
        btn.prop('disabled', false); 
        var text = btn.data("text");
        btn.removeClass('disabled'); 
        btn.html(text); 
    }
}