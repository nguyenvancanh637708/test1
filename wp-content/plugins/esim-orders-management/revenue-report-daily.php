<?php
function render_daily_revenue_report_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'esim_order_data';
    
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    $package_name = $_GET['filter_package'] ?? [];
    
    $params = [];
   
    // Tạo câu truy vấn SQL cho doanh thu theo ngày
    $query = "SELECT package_name, DATE(delivery_date) AS order_date, COUNT(*) AS total_orders, SUM(total_amount) AS total_revenue 
              FROM $table_name WHERE 1=1";
   
    // Điều kiện thời gian
    if ($from_date) {
        $query .= $wpdb->prepare(" AND delivery_date >= %s", $from_date);
    }
    if ($to_date) {
        $query .= $wpdb->prepare(" AND delivery_date <= %s", $to_date . ' 23:59:59');
    }
   
    // Điều kiện lọc cho package_name
    if (!empty($package_name)) {
        $all_ids = [];
        
        foreach ($package_name as $parent_id) {
            $all_ids[] = $parent_id;
            $product = wc_get_product($parent_id);
            if ($product && $product->is_type('variable')) {
                $variation_ids = $product->get_children();
                $all_ids = array_merge($all_ids, $variation_ids);
            }
        }
   
        $all_ids = array_map('intval', array_unique($all_ids));
   
        if ($all_ids) {
            $placeholders = implode(',', array_fill(0, count($all_ids), '%d'));
            $query .= " AND package_name IN ($placeholders)";
            $params = array_merge($params, $all_ids);
        }
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
    ?>
    <div class="wrap">
        <h1>Báo cáo doanh thu theo ngày</h1>
        <form id="revenue-filter" method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <input type="hidden" name="page" value="bao-cao-doanh-thu-theo-ngay">
            <div class="tablenav top">
                <div class="alignleft actions filter-order">
                    <span>Thời gian đặt hàng:</span>
                    <span>từ ngày </span><input type="date" name="from_date" value="<?php echo esc_attr($_GET['from_date'] ?? ''); ?>">
                    <span> đến ngày </span><input type="date" name="to_date" value="<?php echo esc_attr($_GET['to_date'] ?? ''); ?>">
                </div>
                
                <div class="alignleft actions filter-order">
                    <select name="filter_package[]" id="goicuoc_ids" multiple>
                        <option value="">--Tất cả--</option>
                        <?php
                        $args = array(
                            'post_type' => 'product',   
                            'posts_per_page' => -1,
                            'tax_query' => array(
                                array(
                                    'taxonomy' => 'product_cat',
                                    'field'    => 'slug',
                                    'terms'    => 'goi-cuoc',
                                ),
                            ),
                        );
                        $products = get_posts($args);
                        foreach ($products as $product) {
                            $selected = isset($_GET['filter_package']) && in_array($product->ID, $_GET['filter_package']) ? 'selected' : '';
                            echo '<option value="' . esc_attr($product->ID) . '" ' . $selected . '>';
                            echo esc_html($product->post_title);
                            echo '</option>';
                        }
                        ?>
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
