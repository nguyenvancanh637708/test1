<?php
// dashboard.php
if (!defined('ABSPATH')) {
    exit; // Ngăn truy cập trái phép
}

function random_color() {
    return '#' . str_pad(dechex(rand(0x000000, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

function esim_dashboard_page() {
    global $wpdb;

    $all_package = $wpdb->get_col("SELECT DISTINCT package_name FROM {$wpdb->prefix}esim_order_data");
    $package_colors = [];
    foreach ($all_package as $package) {
        $package_colors[$package] = random_color(); 
    }

    // Khởi tạo mảng dữ liệu
    $data = [];
    $total_revenue_data = [];
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : (new DateTime('-7 days'))->format('Y-m-d');
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : (new DateTime())->format('Y-m-d');
    $packages = isset($_GET['packages']) ? array_map('sanitize_text_field', $_GET['packages']) : [];

    // Xây dựng câu truy vấn
    $where_clause = "WHERE created_date BETWEEN '{$start_date}' AND '{$end_date}'";
    if (!empty($packages)) {
        $packages_placeholder = implode(',', array_fill(0, count($packages), '%s'));
        $where_clause .= " AND package_name IN ($packages_placeholder)";
        $query_params = $packages;
    } else {
        $query_params = [];
    }

    // Lấy dữ liệu từ cơ sở dữ liệu
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT package_name, SUM(total_amount) as total_revenue, DATE(created_date) as order_date
        FROM {$wpdb->prefix}esim_order_data
        $where_clause
        GROUP BY package_name, order_date
        ORDER BY order_date ASC
    ", ...$query_params));

    // Kiểm tra kết quả truy vấn
    if (empty($results)) {
        echo '<p>Không có dữ liệu cho khoảng thời gian đã chọn.</p>';
        return;
    }

    $all_dates = [];
    // chỉ hiển thị ngày có doanh thu
    // foreach ($results as $row) {
    //     // Định dạng ngày tháng theo d/m/Y
    //     $formatted_date = date('d/m/Y', strtotime($row->order_date));
        
    //     $data[$row->package_name][$formatted_date] = (int)$row->total_revenue;
    //     $total_revenue_data[$formatted_date] = ($total_revenue_data[$formatted_date] ?? 0) + (int)$row->total_revenue;
    // }

    // Tất cả các ngày đc hiển thị
    if ($start_month && $end_month) {
        $start_date_obj = new DateTime($start_month . '-01');
        $end_date_obj = new DateTime($end_month . '-01');
        $end_date_obj->modify('first day of next month');

        while ($start_date_obj < $end_date_obj) {
            $all_dates[] = $start_date_obj->format('d/m/Y');
            $start_date_obj->modify('+1 day');
        }
    } elseif ($start_date && $end_date) {
        $start_date_obj = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);
        $end_date_obj->modify('+1 day');

        while ($start_date_obj < $end_date_obj) {
            $all_dates[] = $start_date_obj->format('d/m/Y');
            $start_date_obj->modify('+1 day');
        }
    }

    foreach ($all_dates as $formatted_date) {
        foreach ($results as $row) {
            $order_date = date('d/m/Y', strtotime($row->order_date));
            if ($formatted_date === $order_date) {
                $data[$row->package_name][$formatted_date] = (int)$row->total_revenue;
                $total_revenue_data[$formatted_date] = ($total_revenue_data[$formatted_date] ?? 0) + (int)$row->total_revenue;
                break; 
            }
        }

        if (!isset($data[$row->package_name][$formatted_date])) {
            foreach ($all_package as $package) {
                $data[$package][$formatted_date] = 0; 
            }
            $total_revenue_data[$formatted_date] = 0; 
        }
    }

    

    $labels = array_keys($total_revenue_data);
    $datasets = [];

    foreach ($data as $package => $dates) {
        $color = $package_colors[$package] ?? random_color();
        $dataset = [
            'label' => $package,
            'data' => [],
            'backgroundColor' => $color,
            'borderColor' => $color
        ];
        foreach ($labels as $label) {
            $dataset['data'][] = $dates[$label] ?? 0;
        }
        $datasets[] = $dataset;
    }

    // Tổng doanh thu theo ngày
    $total_revenue_chart_data = array_values($total_revenue_data);

    // Doanh thu tổng theo từng gói cho biểu đồ tròn
    $package_revenue_percent = array_map('array_sum', $data);

    // Tính tổng doanh thu cho tất cả các gói
    $total_revenue = array_sum($package_revenue_percent);

    // Tính phần trăm cho mỗi gói
    $package_revenue_percentage = [];
    foreach ($package_revenue_percent as $package => $revenue) {
        $package_revenue_percentage[$package] = ($total_revenue > 0) ? ($revenue / $total_revenue) * 100 : 0;
    }


    $labels_json = json_encode($labels);
    $datasets_json = json_encode($datasets);
    $total_revenue_chart_data_json = json_encode($total_revenue_chart_data);
    $package_labels_json = json_encode(array_keys($package_revenue_percent));
    // $package_revenue_percent_json = json_encode(array_values($package_revenue_percent));
    $package_revenue_percent_json = json_encode(array_values($package_revenue_percentage));

    $colors = array_values($package_colors); 
    $colors_json = json_encode($colors);
    ?>
    <div class="wrap">
        <h1>Dashboard Doanh Thu</h1>
        
        <!-- Biểu mẫu lọc -->
        <form method="GET" id="dateFilterForm" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <div class="nav-filter1">
                <input type="hidden" name="page" value="esim-dashboard">
                <!-- Lọc theo ngày -->
                <label for="start_date">Từ Ngày:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                <label for="end_date">Đến Ngày:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
                <!-- Gói cước -->
                <select name="packages[]" id="goicuoc_ids" multiple style="width: 300px;">
                    <?php 
                    foreach ($all_package as $package) {
                        $selected = (isset($_GET['packages']) && is_array($_GET['packages']) && in_array($package, $_GET['packages'])) ? 'selected' : '';
                        echo "<option value=\"{$package}\" $selected>{$package}</option>";
                    }
                    ?>
                </select>
                <input type="submit" value="Lọc" class="button button-primary">
            </div>
        </form>

       <div class="charts">
            <div class="totalChart">
                <canvas id="totalChart"></canvas>
            </div>
            <div class="revenueChart">
                <canvas id="revenueChart"></canvas>
            </div>
            <div class="legendChart">
                <canvas id="legendChart"></canvas>
            </div>
       </div>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const labels = <?php echo $labels_json; ?>;
        // Tổng doanh thu
        const totalChart = new Chart(document.getElementById('totalChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Tổng doanh thu',
                    data: <?php echo $total_revenue_chart_data_json; ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Tổng doanh thu' }
                    }
                },
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Tổng Doanh Thu So Với Cùng Kỳ Tháng Trước' }
                }
            }
        });

        // Doanh thu theo từng gói
        const revenueChart = new Chart(document.getElementById('revenueChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: <?php echo $datasets_json; ?>
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Doanh thu' }
                    }
                },
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Doanh Thu Theo Ngày' }
                }
            }
        });

        // Tỷ lệ phần trăm doanh thu theo gói
        const legendChart = new Chart(document.getElementById('legendChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?php echo $package_labels_json; ?>,
                datasets: [{
                    label: 'Tỷ lệ doanh thu theo gói',
                    data: <?php echo $package_revenue_percent_json; ?>,
                    backgroundColor: <?php echo $colors_json; ?>, // Sử dụng mã màu từ PHP
                    borderColor: <?php echo $colors_json; ?>, // Sử dụng mã màu từ PHP
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: { display: true, text: 'Tỷ Lệ Doanh Thu Theo Từng Gói' },
                    legend: { position: 'right' }
                }
            }
        });
    </script>
    <?php
}
?>
