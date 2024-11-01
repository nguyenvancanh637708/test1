<?php
// dashboard.php
if (!defined('ABSPATH')) {
    exit; // Ngăn truy cập trái phép
}

function random_color() {
    // Hàm để tạo màu ngẫu nhiên cho biểu đồ
    return '#' . str_pad(dechex(rand(0x000000, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

function esim_dashboard_page_old() {
    global $wpdb;

    // Khởi tạo mảng dữ liệu
    $data = [];
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : (new DateTime('-7 days'))->format('Y-m-d');
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : (new DateTime())->format('Y-m-d');
    $start_month = isset($_GET['start_month']) ? sanitize_text_field($_GET['start_month']) : null;
    $end_month = isset($_GET['end_month']) ? sanitize_text_field($_GET['end_month']) : null;
    $packages = isset($_GET['packages']) ? array_map('sanitize_text_field', $_GET['packages']) : []; // Sửa đổi để xử lý nhiều gói cước

    // Xây dựng câu truy vấn dựa trên khoảng thời gian
    $where_clause = '';

    if ($start_month && $end_month) {
        $start_year = date('Y', strtotime($start_month));
        $end_year = date('Y', strtotime($end_month));
        $where_clause = "WHERE (YEAR(created_date) BETWEEN '{$start_year}' AND '{$end_year}') AND (MONTH(created_date) BETWEEN MONTH('{$start_month}-01') AND MONTH('{$end_month}-01'))";
    } elseif ($start_date && $end_date) {
        $where_clause = "WHERE created_date BETWEEN '{$start_date}' AND '{$end_date}'";
    } elseif ($start_date) {
        $where_clause = "WHERE created_date >= '{$start_date}'";
    } elseif ($end_date) {
        $where_clause = "WHERE created_date <= '{$end_date}'";
    }

    if (!empty($packages)) {
        // Tạo placeholders cho câu lệnh SQL
        if (count($packages) === 1) {
            $where_clause .= " AND package_name = %s";
            $query_params[] = $packages[0]; // Thêm phần tử đầu tiên vào params
        } else {
            $packages_placeholder = implode(',', array_fill(0, count($packages), '%s'));
            $where_clause .= " AND package_name IN ($packages_placeholder)";
            $query_params = array_merge($packages, [$start_date, $end_date, $start_month, $end_month]);
        }
    }

    $results = $wpdb->get_results($wpdb->prepare("
        SELECT package_name, SUM(total_amount) as total_revenue, DATE(created_date) as order_date
        FROM {$wpdb->prefix}esim_order_data
        $where_clause
        GROUP BY package_name, order_date
        ORDER BY order_date ASC
    ", $query_params));

    // Kiểm tra kết quả truy vấn
    if (empty($results)) {
        echo '<p>Không có dữ liệu cho khoảng thời gian đã chọn.</p>';
    } else {
        foreach ($results as $row) {
            $data[$row->package_name][$row->order_date] = (int)$row->total_revenue;
        }
    }

    $labels = [];
    $datasets = [];
    
    // Tạo danh sách tất cả ngày trong khoảng thời gian
    if ($start_month && $end_month) {
        $start_date_obj = new DateTime($start_month . '-01');
        $end_date_obj = new DateTime($end_month . '-01');
        $end_date_obj->modify('first day of next month');

        while ($start_date_obj < $end_date_obj) {
            $labels[] = $start_date_obj->format('Y-m-d');
            $start_date_obj->modify('+1 day');
        }
    } elseif ($start_date && $end_date) {
        $start_date_obj = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);
        $end_date_obj->modify('+1 day');

        while ($start_date_obj < $end_date_obj) {
            $labels[] = $start_date_obj->format('Y-m-d');
            $start_date_obj->modify('+1 day');
        }
    }

    foreach ($data as $package => $dates) {
        $dataset = ['label' => $package, 'data' => [], 'backgroundColor' => random_color()];

        foreach ($labels as $label) {
            $dataset['data'][] = isset($dates[$label]) ? $dates[$label] : 0;
        }

        $datasets[] = $dataset;
    }

    $labels_json = json_encode($labels);
    $datasets_json = json_encode($datasets);

    $packages = $wpdb->get_col("SELECT DISTINCT package_name FROM {$wpdb->prefix}esim_order_data");
    ?>
    <div class="wrap">
        <h1>Dashboard Doanh Thu</h1>
        <!-- Biểu mẫu lọc -->
        <form method="GET" id="dateFilterForm" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <input type="hidden" name="page" value="esim-dashboard">
            
            <!-- Lọc theo tháng -->
            <label for="start_month">Từ Tháng:</label>
            <input type="month" id="start_month" name="start_month" value="<?php echo esc_attr($start_month); ?>">
            <label for="end_month">Đến Tháng:</label>
            <input type="month" id="end_month" name="end_month" value="<?php echo esc_attr($end_month); ?>">
            <span> ------------- </span>

            <!-- Lọc theo ngày -->
            <label for="start_date">Từ Ngày:</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
            <label for="end_date">Đến Ngày:</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_date); ?>">

            <!-- Gói cước -->
            <select name="packages[]" id="goicuoc_ids" multiple style="width: 300px;">
                <?php 
                foreach ($packages as $package) {
                    $selected = (isset($_GET['packages']) && is_array($_GET['packages']) && in_array($package, $_GET['packages'])) ? 'selected' : '';
                    echo "<option value=\"{$package}\" $selected>{$package}</option>";
                }
                ?>
            </select>
            
            <input type="submit" value="Lọc" class="button button-primary">
        </form>

        <canvas id="revenueChart" width="400" height="200"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo $labels_json; ?>,
                datasets: <?php echo $datasets_json; ?>
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Biểu đồ doanh thu'
                    }
                },
            }
        });
    </script>
    <?php
}

