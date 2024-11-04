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

    $params = [];

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
    $results = $wpdb->get_results($wpdb->prepare($query, $params));

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

    // Excel Export
    if (isset($_GET['export_excel'])) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="doanh_thu_theo_goi_cuoc.xls"');
        echo "Tháng\tLoại sim\tSố lượng bán\tDoanh thu BH\n";

        foreach ($summary as $month => $data) {
            // Total row for each month
            echo "$month\tTổng\t{$data['total_orders']}\t" . number_format($data['total_revenue'], 0, ',', '.') . "\n";
            
            // Detail rows for each package within the month
            foreach ($data['items'] as $item) {
                echo "\t{$item->package_name}\t{$item->total_orders}\t" . number_format($item->total_revenue, 0, ',', '.') . "\n";
            }
        }

        exit;
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
                    <select name="channel[]" multiple>
                        <option value="">Kênh bán</option>
                        <option <?php echo in_array('Esimdata', $_GET['channel'] ?? []) ? 'selected' : ''; ?> value="Esimdata">Esimdata</option>
                        <option <?php echo in_array('Landing', $_GET['channel'] ?? []) ? 'selected' : ''; ?> value="Landing">Landing</option>
                    </select>
                </div>
                
                <div class="alignleft actions">
                    <input type="submit" name="filter_action" id="order-query-submit" class="button" value="Tìm kiếm">
                    <input type="submit" name="export_excel" class="button" value="Xuất Excel">
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
