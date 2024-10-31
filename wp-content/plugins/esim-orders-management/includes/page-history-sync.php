<?php 

function esim_sync_history_page() {
    echo '<div class="wrap">';
    echo '<h1>Lịch sử đồng bộ</h1>';

    // Form lọc dữ liệu
    echo '<form method="GET" action="">';
    echo '<input type="hidden" name="page" value="history-sync" />'; // Thay 'your_page_slug' bằng slug của trang của bạn
    echo '<label for="landing_id">Landing ID:</label>';
    echo '<input type="text" name="landing_id" id="landing_id" value="' . esc_attr(isset($_GET['landing_id']) ? $_GET['landing_id'] : '') . '" />';
    echo '<label for="wp_order_id">WP Order ID:</label>';
    echo '<input type="text" name="wp_order_id" id="wp_order_id" value="' . esc_attr(isset($_GET['wp_order_id']) ? $_GET['wp_order_id'] : '') . '" />';
    echo '<input type="submit" class="button" value="Lọc" />';
    echo '</form>';

    // Khởi tạo bảng lịch sử đồng bộ
    $list_table = new Custom_History_Sync_List_Table();
    // Truyền các giá trị lọc vào phương thức prepare_items() nếu có
    $list_table->prepare_items(isset($_GET['landing_id']) ? sanitize_text_field($_GET['landing_id']) : '', isset($_GET['wp_order_id']) ? sanitize_text_field($_GET['wp_order_id']) : '');
    $list_table->display();
    echo '</div>';
}
