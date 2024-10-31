<?php 

function ds_kh_dat_sim_page() {
    echo '<div class="wrap"><h1>Danh sách khách hàng đặt SIM</h1>';
    $start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-7 days'));
    $end_date = isset($_GET['end_date']) && !empty($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
    // Khởi tạo bảng
    $orderListTable = new Custom_Customer_List_Table();
    $orderListTable->prepare_items();

    echo '<form method="GET" action="' . esc_url(admin_url('admin.php')) . '">';
    echo '<div class="nav-filter">';
    echo '<input type="hidden" name="page" value="ds-kh-dat-sim">';
    echo '<label for="start_date">Từ ngày:</label>';
    echo '<input type="date" name="start_date" value="' . (isset($_GET['start_date']) ? esc_attr($_GET['start_date']) : esc_attr($start_date)) . '">';

    echo '<label for="end_date">Đến ngày:</label>';
    echo '<input type="date" name="end_date" value="' . (isset($_GET['end_date']) ? esc_attr($_GET['end_date']) : esc_attr($end_date)) . '">';
    echo '<input type="text" name="cus_phone" value="' . (isset($_GET['cus_phone']) ? esc_attr($_GET['cus_phone']) : '') . '" placeholder="SĐT KH">';

    //kênh bán
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

    echo '<select name="goicuoc_ids[]" id="goicuoc_ids" multiple>';
    foreach ($packages as $package) {
        $selected = (isset($_GET['goicuoc_ids']) && is_array($_GET['goicuoc_ids']) && in_array($package->ID, $_GET['goicuoc_ids'])) ? 'selected' : '';
        echo "<option value=\"{$package->ID}\" $selected>{$package->post_title}</option>";
    }
    echo '</select>';

    // Thêm bộ lọc cho người tư vấn
    $users = get_users();
    echo '<select name="user_ids[]" id="user_ids" multiple>';
    foreach ($users as $user) {
        $selected = (isset($_GET['user_ids']) && is_array($_GET['user_ids']) && in_array($user->ID, $_GET['user_ids'])) ? 'selected' : '';
        echo "<option value=\"{$user->ID}\" $selected>{$user->display_name}</option>";
    }
    echo '</select>';


    
    $statuses = [
        '' => 'Tất cả trạng thái',
        0 => 'Chờ xử lý',
        1 => 'Thành công',
        -1 => 'Thất bại',
    ];
    
    echo '<select name="status">';
    foreach ($statuses as $value => $label) {
        $selected = (isset($_GET['status']) && $_GET['status'] == $value) ? 'selected' : '';
        echo "<option value=\"$value\" $selected>$label</option>";
    }
    echo '</select>';
    

    // Add search input
    // echo '<input type="text" name="s" value="' . (isset($_GET['s']) ? esc_attr($_GET['s']) : '') . '" placeholder="Tìm mã, sim đặt">';
    echo '<input type="submit" class="button" value="Lọc">';
    echo '<a href="' . esc_url(admin_url('admin.php?page=ds-kh-dat-sim')) . '" class="button">Xóa lọc</a>';
    echo '</div>';
    echo '</form>';
    
    // Hiển thị bảng
    $orderListTable->display();

    echo '</div>';
}



