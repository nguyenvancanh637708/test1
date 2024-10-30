<?php 

function ds_don_hang_page() {
    echo '<div class="wrap"><h1>Danh sách khách hàng đặt SIM</h1>';
    $start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-7 days'));
    $end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
    // Khởi tạo bảng
    $orderListTable = new Custom_Order_List_Table();
    $orderListTable->prepare_items();

    echo '<form method="GET" action="' . esc_url(admin_url('admin.php')) . '">';
    echo '<div class="nav-filter">';
    echo '<input type="hidden" name="page" value="ds-don-hang">';
    echo '<label for="start_date">Từ ngày:</label>';
    echo '<input type="date" name="start_date" value="' . (isset($_GET['start_date']) ? esc_attr($_GET['start_date']) : esc_attr($start_date)) . '">';

    echo '<label for="end_date">Đến ngày:</label>';
    echo '<input type="date" name="end_date" value="' . (isset($_GET['end_date']) ? esc_attr($_GET['end_date']) : esc_attr($end_date)) . '">';
    // Thêm ô input cho mã lô
    // echo '<input type="text" name="lot_code" value="' . (isset($_GET['lot_code']) ? esc_attr($_GET['lot_code']) : '') . '" placeholder="Nhập mã lô">';
    // Thêm ô input cho Loại KH
    echo '<input type="text" name="cus_type" value="' . (isset($_GET['cus_type']) ? esc_attr($_GET['cus_type']) : '') . '" placeholder="Loại KH">';

    // Thêm bộ lọc cho gói cước
    $packages = get_posts([
        'post_type' => 'product', 
        'posts_per_page' => -1, 
        'tax_query' => [
            [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => 'goi-cuoc' 
            ]
        ]
    ]);

    echo '<select name="package_name">';
    echo '<option value="">Gói cước</option>';
    foreach ($packages as $package) {
        $selected = (isset($_GET['package_name']) && $_GET['package_name'] == $package->post_title) ? 'selected' : '';
        echo "<option value=\"{$package->post_title}\" $selected>{$package->post_title}</option>";
    }
    echo '</select>';

    $sales_channels = [
        '' => 'Kênh bán',
        'esimdata' => 'Esimdata',
        'landing' => 'Landing'
    ];

    echo '<select name="channel">';
    foreach ($sales_channels as $value => $label) {
        $selected = (isset($_GET['channel']) && $_GET['channel'] === $value) ? 'selected' : '';
        echo "<option value=\"$value\" $selected>$label</option>";
    }
    echo '</select>';

    // Custom filter for order status
    $statuses = [
        '' => 'Tất cả trạng thái',
        'success' => 'Thành công',
        'waiting_for_delivery' => 'Chờ giao',
        'shipped' => 'Đã giao',
        'failed' => 'Thất bại',
        'received_payment' => 'Đã nhận tiền'
    ];

    echo '<select name="order_status">';
    foreach ($statuses as $value => $label) {
        // Persist the selected status
        $selected = (isset($_GET['order_status']) && $_GET['order_status'] === $value) ? 'selected' : '';
        echo "<option value=\"$value\" $selected>$label</option>";
    }
    echo '</select>';

    // Add search input
    echo '<input type="text" name="s" value="' . (isset($_GET['s']) ? esc_attr($_GET['s']) : '') . '" placeholder="tìm tên, sđt KH">';
    echo '<input type="submit" class="button" value="Lọc">';
    echo '<a href="' . esc_url(admin_url('admin.php?page=ds-don-hang')) . '" class="button">Xóa lọc</a>';
    echo '</div>';
    echo '</form>';
    
    // Hiển thị bảng
    $orderListTable->display();

    echo '</div>';
}



