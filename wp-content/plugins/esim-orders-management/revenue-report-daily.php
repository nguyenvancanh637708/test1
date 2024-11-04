<?php
function render_daily_revenue_report_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'esim_order_data';

    // Lấy dữ liệu từ các tham số GET
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    $packages = isset($_GET['package']) && is_array($_GET['package'])
        ? array_filter($_GET['package'], 'strlen') // Lọc bỏ các chuỗi rỗng
        : [];
    $channels = isset($_GET['channel']) && is_array($_GET['channel'])
        ? array_filter($_GET['channel'], 'strlen') // Lọc bỏ các chuỗi rỗng
        : [];

    $params = [];
    
    // Tạo câu truy vấn SQL cho doanh thu theo ngày
    $query = "SELECT package_name, DATE(created_date) AS order_date, COUNT(*) AS total_orders, SUM(total_amount) AS total_revenue 
              FROM $table_name WHERE 1=1";

    // Điều kiện thời gian
    if ($from_date) {
        $query .= $wpdb->prepare(" AND created_date >= %s", $from_date);
    }
    if ($to_date) {
        $query .= $wpdb->prepare(" AND created_date <= %s", $to_date . ' 23:59:59');
    }
    if (!empty($packages)) {
        $packages_placeholder = implode(',', array_fill(0, count($packages), '%s'));
        $query .= $wpdb->prepare(" AND package_name IN ($packages_placeholder)", $packages);
    }
    if (!empty($channels)) {
        $channels_placeholder = implode(',', array_fill(0, count($channels), '%s'));
        $query .= $wpdb->prepare(" AND channel IN ($channels_placeholder)", $channels);
    }

    $query .= " GROUP BY order_date, package_name ORDER BY order_date, package_name";
    $results = $wpdb->get_results($wpdb->prepare($query, ...$params));

    // Tính tổng doanh thu cho toàn bộ báo cáo
    $grand_total_revenue = 0;

    // Group results by order_date
    $summary = [];
    foreach ($results as $result) {
        $grand_total_revenue += $result->total_revenue;

        if (!isset($summary[$result->order_date])) {
            $summary[$result->order_date] = [
                'packages' => [],
                'total_revenue' => 0,
            ];
        }

        $summary[$result->order_date]['packages'][$result->package_name] = [
            'total_revenue' => $result->total_revenue,
        ];

        $summary[$result->order_date]['total_revenue'] += $result->total_revenue;
    }

    // Kiểm tra xem có yêu cầu xuất CSV không
    if (isset($_GET['export_csv'])) {
        // Đặt header cho file CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bao_cao_doanh_thu_' . date('Y-m-d') . '.csv"');
        
        // Mở file output cho ghi
        $output = fopen('php://output', 'w');

        // // Thêm BOM vào đầu file CSV để Excel nhận diện đúng mã hóa
        // fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Đặt tiêu đề cho các cột
        fputcsv($output, ['STT', 'Ngày giao', 'Loại sim', 'Doanh thu BH']);

        $stt = 1;
        foreach ($summary as $order_date => $data) {
            foreach ($data['packages'] as $package_name => $package_data) {
                fputcsv($output, [
                    $stt++,
                    $order_date,
                    $package_name,
                    number_format($package_data['total_revenue'], 0, ',', '.') . ' VNĐ'
                ]);
            }
        }

        // Tổng doanh thu
        fputcsv($output, ['Tổng', '', '', number_format($grand_total_revenue, 0, ',', '.') . ' VNĐ']);

        fclose($output);
        exit(); // Dừng thực thi script
    }

    // Hiển thị HTML nếu không xuất CSV
    ?>
    <div class="wrap">
        <h1>Báo cáo doanh thu theo ngày</h1>
        <form id="revenue-filter" method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <input type="hidden" name="page" value="bao-cao-doanh-thu-theo-ngay">
            <div class="tablenav top">
                <div class="alignleft actions filter-order">
                    <span>Thời gian đặt hàng:</span>
                    <span>từ ngày </span><input type="date" name="from_date" value="<?php echo esc_attr($from_date); ?>">
                    <span> đến ngày </span><input type="date" name="to_date" value="<?php echo esc_attr($to_date); ?>">
                </div>
                
                <div class="alignleft actions filter-order">
                    <select name="package[]" id="goicuoc_ids" multiple>
                        <option value="">--Tất cả--</option>
                        <?php
                        $all_package = $wpdb->get_col("SELECT DISTINCT package_name FROM {$wpdb->prefix}esim_order_data");
                        foreach ($all_package as $package) {
                            $selected = isset($_GET['package']) && in_array($package, $_GET['package']) ? 'selected' : '';
                            echo '<option value="' . esc_attr($package) . '" ' . $selected . '>';
                            echo esc_html($package);
                            echo '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="alignleft actions filter-order">
                    <select name="channel[]" multiple>
                        <option value="">Kênh bán</option>
                        <option <?php echo in_array('Esimdata', $_GET['channel'] ?? []) ? 'selected' : ''; ?> value="Esimdata">Esimdata</option>
                        <option <?php echo in_array('Landing', $_GET['channel'] ?? []) ? 'selected' : ''; ?> value="Landing">Landing</option>
                    </select>
                </div>
                <div class="alignleft actions">
                    <input type="submit" name="filter_action" id="order-query-submit" class="button" value="Tìm kiếm">
                    <input type="submit" name="export_csv" class="button" value="Xuất CSV">
                </div>
            </div>
        </form>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Ngày giao</th>
                    <th>Loại sim</th>
                    <th>Doanh thu BH</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $stt = 1;
                foreach ($summary as $order_date => $data) {
                    $date_displayed = false;
                    foreach ($data['packages'] as $package_name => $package_data) {
                        echo '<tr>';
                        echo '<td>' . esc_html($stt++) . '</td>';
                        echo '<td>' . (!$date_displayed ? esc_html($order_date) : '') . '</td>';
                        echo '<td>' . esc_html($package_name) . '</td>';
                        echo '<td>' . number_format($package_data['total_revenue'], 0, ',', '.') . ' VNĐ</td>';
                        echo '</tr>';
                        $date_displayed = true;
                    }
                }
                ?>
                <!-- Tổng toàn bộ báo cáo -->
                <tr style="font-weight: bold;">
                    <td colspan="3" style="text-align: center;">Tổng</td>
                    <td><?php echo number_format($grand_total_revenue, 0, ',', '.'); ?> VNĐ</td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
}


?>
