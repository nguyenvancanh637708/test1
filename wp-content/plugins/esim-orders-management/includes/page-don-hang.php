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
        1 => 'Thành công',            
        0 => 'Chờ giao',              
        3 => 'Đã giao',               
        2 => 'Thất bại',              
        4 => 'Đã nhận tiền'           
    ];
    

    echo '<select name="order_status">';
    foreach ($statuses as $value => $label) {
        // Persist the selected status
        $selected = (isset($_GET['order_status']) && $_GET['order_status'] === $value) ? 'selected' : '';
        echo "<option value=\"$value\" $selected>$label</option>";
    }
    echo '</select>';
    echo '<input type="text" name="s" value="' . (isset($_GET['s']) ? esc_attr($_GET['s']) : '') . '" placeholder="tìm tên, sđt KH">';
    echo '<input type="submit" class="button" value="Lọc">';
    echo '<a href="' . esc_url(admin_url('admin.php?page=ds-don-hang')) . '" class="button">Xóa lọc</a>';
  // Form bộ lọc và nút xuất Excel
    echo '<form method="GET" action="' . esc_url(admin_url('admin.php')) . '">';
    echo '<div class="nav-filter">';
    echo '<input type="hidden" name="page" value="ds-don-hang">';

    // Các bộ lọc đã có sẵn của bạn ở đây (start_date, end_date, package_name...)
    // Thêm nút "Xuất Excel"
    // echo '<input type="submit" name="export_excel" class="button button_export" value="Xuất Excel">';
    echo '<input type="submit" name="export_excel" class="button" value="Xuất execl">';
    echo '</div>';
    echo '</form>';

    // Kiểm tra nếu người dùng bấm "Xuất Excel"
    if (isset($_GET['export_excel'])) {
        export_orders_to_excel($orderListTable->get_order_data());
    }

    echo '</div>';
    echo '</form>';
    $orderListTable->display();

    echo '</div>';
}



function export_orders_to_excel($orders) {
    // Kiểm tra xem có dữ liệu để xuất không
    if (empty($orders)) {
        echo 'Không có dữ liệu để xuất.';
        return;
    }

    // Đặt tên file xuất
    $filename = "don_hang_" . date('Ymd_His') . ".csv";
    
    // Xóa bộ đệm đầu ra (nếu có) để đảm bảo không có bất kỳ nội dung nào được gửi trước
    if (ob_get_length()) ob_end_clean();

    // Thiết lập tiêu đề HTTP cho file CSV
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename={$filename}");
    header("Content-Type: text/csv; charset=UTF-8");
    header('Pragma: public');
    header('Expires: 0');
    
    // Mở output để ghi vào file CSV
    $output = fopen("php://output", "w");

    // Output a BOM (Byte Order Mark) for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Ghi dòng tiêu đề cho file CSV (các trường trong đơn hàng)
    fputcsv($output, ['Mã vận đơn', 'Ngày tạo đơn', 'Ngày giao', 'SĐT KH', 'Tên KH', 'Sim đặt', 'Loại sim', 'Số lượng', 'Tổng tiền', 'Kênh bán', 'Trạng thái']);

    // Ghi dữ liệu từng đơn hàng vào CSV
    foreach ($orders as $order) {
        fputcsv($output, [
            $order['ma_van_don'],
            $order['created_date'],
            $order['delivery_date'],
            $order['cus_phone'],
            $order['cus_name'],
            $order['phone_number'],
            $order['package_name'],
            $order['qty'],
            str_replace(',', '', $order['total_amount']), // Chuyển đổi định dạng tiền tệ
            $order['channel'],
            strip_tags($order['status']) // Loại bỏ thẻ HTML trong trạng thái
        ]);
    }

    // Đóng output và dừng thực thi để tải file về
    fclose($output);
    exit();
}

// Thêm vào chỗ gọi hàm xuất
if (isset($_GET['export_csv'])) {
    // Lấy dữ liệu đơn hàng từ bảng
    $orders = $orderListTable->get_order_data(); // Đảm bảo bạn gọi đúng phương thức lấy dữ liệu

    // Gọi hàm xuất
    export_orders_to_excel($orders);
}
