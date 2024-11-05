<?php
function render_revenue_report_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'esim_order_data';

    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    $packages = isset($_GET['package']) && is_array($_GET['package'])
        ? array_filter($_GET['package'], 'strlen') // Filters out empty strings
        : [];
    $channels = isset($_GET['channel']) && is_array($_GET['channel'])
        ? array_filter($_GET['channel'], 'strlen') // Filters out empty strings
        : [];

    // Construct SQL query
    $query = "SELECT package_name, DATE_FORMAT(delivery_date, '%m/%Y') AS month, COUNT(*) AS total_orders, SUM(total_amount) AS total_revenue 
              FROM $table_name WHERE 1=1";

    // Date filter
    if ($from_date) {
        $query .= $wpdb->prepare(" AND delivery_date >= %s", $from_date . '-01');
    }
    if ($to_date) {
        $query .= $wpdb->prepare(" AND delivery_date <= %s", date("Y-m-t", strtotime($to_date . '-01')));
    }

    // Package filter
    if (!empty($packages)) {
        $packages_placeholder = implode(',', array_fill(0, count($packages), '%s'));
        $query .= $wpdb->prepare(" AND package_name IN ($packages_placeholder)", $packages);
    }

    // Channel filter
    if (!empty($channels)) {
        $channels_placeholder = implode(',', array_fill(0, count($channels), '%s'));
        $query .= $wpdb->prepare(" AND channel IN ($channels_placeholder)", $channels);
    }

    $query .= " GROUP BY month, package_name ORDER BY month, package_name";
    $results = $wpdb->get_results($query);

    // Summarize data for each month
    $summary = [];
    foreach ($results as $result) {
        $month = $result->month;
        if (!isset($summary[$month])) {
            $summary[$month] = [
                'total_orders' => 0,
                'total_revenue' => 0,
                'items' => []
            ];
        }
        $summary[$month]['total_orders'] += $result->total_orders;
        $summary[$month]['total_revenue'] += $result->total_revenue;
        $summary[$month]['items'][] = $result;
    }

    // CSV Export
    if (isset($_GET['export_csv'])) {
        // Prevent any output before setting headers
        if (ob_get_length()) ob_end_clean(); // Clean the output buffer if there's any output

        // Set headers for CSV file download
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="bao_cao_doanh_thu_thang' . date('Y-m-d') . '.csv"');

        // Open output stream for writing
        $output = fopen('php://output', 'w');

        // Output a BOM (Byte Order Mark) for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Set column headers with formatting to indicate separation
        fputcsv($output, ['STT', 'Tháng', 'Loại sim', 'Số lượng bán', 'Doanh thu BH']);

        $stt = 1; // Initialize counter for STT
        foreach ($summary as $month => $data) {
            // Add total row for the month
            fputcsv($output, [
                $stt++,
                $month,
                'Tổng',
                $data['total_orders'],
                number_format($data['total_revenue'], 0, ',', '.') . ' VNĐ'
            ]);

            // Add each package type for the month
            foreach ($data['items'] as $item) {
                fputcsv($output, [
                    '',
                    '',
                    $item->package_name,
                    $item->total_orders,
                    number_format($item->total_revenue, 0, ',', '.') . ' VNĐ'
                ]);
            }
        }

        // Close output stream
        fclose($output);
        exit(); // Stop script execution
    }

    ?>
    <div class="wrap">
        <h1>Báo cáo doanh thu theo gói cước</h1>
        <form id="revenue-filter" method="get">
            <input type="hidden" name="page" value="bao-cao-doanh-thu">
            <div class="tablenav top">
                <div class="alignleft actions filter-order">
                    <span>Thời gian đặt hàng:</span>
                    <span>từ tháng </span><input type="month" name="from_date" value="<?php echo esc_attr($_GET['from_date'] ?? ''); ?>">
                    <span> đến tháng </span><input type="month" name="to_date" value="<?php echo esc_attr($_GET['to_date'] ?? ''); ?>">
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
                    <select name="channel[]">
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
                    <th>Tháng</th>
                    <th>Loại sim</th>
                    <th>Số lượng bán</th>
                    <th>Doanh thu BH</th>
                </tr>
            </thead>
            <tbody>
               <?php 
                foreach ($summary as $month => $data) {
                    // Display total for the month
                    echo '<tr style="font-weight: bold;">';
                    echo '<td>' . esc_html($month) . '</td>';
                    echo '<td>Tổng</td>';
                    echo '<td>' . esc_html($data['total_orders']) . '</td>';
                    echo '<td>' . number_format($data['total_revenue'], 0, ',', '.') . ' VNĐ</td>';
                    echo '</tr>';

                    // Display each package type for the month
                    foreach ($data['items'] as $item) {
                        echo '<tr>';
                        echo '<td></td>'; // Empty cell for month column
                        echo '<td>' . esc_html($item->package_name) . '</td>';
                        echo '<td>' . esc_html($item->total_orders) . '</td>';
                        echo '<td>' . number_format($item->total_revenue, 0, ',', '.') . ' VNĐ</td>';
                        echo '</tr>';
                    }
                }
               ?>
            </tbody>
        </table>
    </div>
    <?php
}
?>
