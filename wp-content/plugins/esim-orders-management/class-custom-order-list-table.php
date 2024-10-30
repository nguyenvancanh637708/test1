<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Custom_Order_List_Table extends WP_List_Table {
    
    function __construct() {
        parent::__construct([
            'singular' => 'order',
            'plural'   => 'orders',
            'ajax'     => false
        ]);
    }

    function get_columns() {
        return [
            'ma_van_don'       => 'Mã Vận Đơn',
            'created_date'     => 'Ngày Tạo Đơn',
            'delivery_date'    => 'Ngày Giao',
            'cus_phone'        => 'SĐT KH',
            'cus_name'         => 'Tên KH',
            // 'shipping_address' => 'Địa Chỉ Nhận Hàng',   
            'phone_number'     => 'SIM đặt',
            'package_name'     => 'Loại SIM',
            'qty'              => 'Số Lượng',
            'total_amount'     => 'Tổng tiền',
            'channel'          => 'Kênh bán',
            'status'           => 'Trạng Thái',
        ];
    }

    function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];
        
        // Lấy dữ liệu đơn hàng
        $data = $this->get_order_data();

        $per_page = 10;
        $current_page = $this->get_pagenum();
        $total_items = count($data);

        // Chia trang
        $this->items = array_slice($data, (($current_page - 1) * $per_page), $per_page);
        
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }

    function get_order_data() {
        global $wpdb;
        $table_name = 'wp_esim_order_data'; 
        $query = "SELECT * FROM $table_name WHERE 1=1"; 
    
        // Filter by order status
        if (isset($_GET['order_status']) && $_GET['order_status'] !== '') {
            $status = sanitize_text_field($_GET['order_status']);
            $query .= $wpdb->prepare(" AND status = %s", $status);
        }
    
        // Filter by date range
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-7 days')); 
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d'); 
        $lot_code = isset($_GET['lot_code']) ? sanitize_text_field($_GET['lot_code']) : '';
        $cus_type = isset($_GET['cus_type']) ? sanitize_text_field($_GET['cus_type']) : '';
        $package_name = isset($_GET['package_name']) ? sanitize_text_field($_GET['package_name']) : '';
        $channel = isset($_GET['channel']) ? sanitize_text_field($_GET['channel']) : '';
        $lot_code = isset($_GET['lot_code']) ? sanitize_text_field($_GET['lot_code']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    
        if (!empty($start_date)) {
            $query .= $wpdb->prepare(" AND created_date >= %s", $start_date);
        }
    
        if (!empty($end_date)) {
            $to_date_end = $end_date . ' 23:59:59';
            $query .= $wpdb->prepare(" AND created_date <= %s", $to_date_end);
        }

        if (!empty($search)) {
            $query .= $wpdb->prepare(" AND (cus_phone LIKE %s OR cus_name LIKE %s)", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
        }
    
        if (!empty($lot_code)) {
            $query .= $wpdb->prepare(" AND lot_code = %s", $lot_code);
        }

        if (!empty($cus_type)) {
            $query .= $wpdb->prepare(" AND customer_type = %s", $cus_type);
        }

        if (!empty($package_name)) {
            $query .= $wpdb->prepare(" AND package_name LIKE %s", '%' . $wpdb->esc_like($package_name) . '%');
        }

        if (!empty($channel)) {
            $query .= $wpdb->prepare(" AND channel = %s", $channel);
        }

        // Handle sorting
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_date';
        $order = isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc']) ? $_GET['order'] : 'desc';
        
        $query .= " ORDER BY $orderby $order"; // Add sorting
        // Fetch results
        $results = $wpdb->get_results($query, ARRAY_A);
        $data = [];
        
        foreach ($results as $row) {
            $status_display = $this->get_status_display($row['status']);
            $data[] = [
                'id'                => esc_html($row['id']),
                'ma_van_don'       => esc_html($row['ma_van_don']),
                'created_date'     => esc_html($row['created_date']),
                'delivery_date'    => esc_html($row['delivery_date']),
                'cus_phone'        => esc_html($row['cus_phone']),
                'cus_name'         => esc_html($row['cus_name']),
                'shipping_address' => esc_html($row['shipping_address']),
                'phone_number'     => esc_html($row['phone_number']),
                'package_name'     => esc_html($row['package_name']),
                'qty'              => esc_html($row['qty']),
                'channel'          => esc_html($row['channel']),
                'total_amount'     => number_format($row['total_amount'], 0, ',', '.') . ' ₫',
                'status'           => $status_display,
            ];
        }
    
        return $data;
    }
    
    
    

    function get_status_display($status) {
        $status_display = '';
        
        switch ($status) {
            case 'success':
                $status_display = '<span class="badge badge-success">Thành công</span>';
                break;
            case 'waiting_for_delivery':
                $status_display = '<span class="badge badge-warning">Chờ giao</span>';
                break;
            case 'shipped':
                $status_display = '<span class="badge badge-info">Đã giao</span>';
                break;
            case 'failed':
                $status_display = '<span class="badge badge-danger">Thất bại</span>';
                break;
            case 'received_payment':
                $status_display = '<span class="badge badge-primary">Đã nhận tiền</span>';
                break;
            default:
                $status_display = '<span class="badge badge-secondary">Không xác định</span>';
                break;
        }
    
        return $status_display;
    }

    function column_cb($item) {
        return sprintf('<input type="checkbox" name="order[]" value="%s" />', esc_attr($item['ma_van_don']));
    }
    
    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'ma_van_don':
                $order_detail_url = admin_url('admin.php?page=edit-don-hang&order_id=' . urlencode($item['id']));
                return sprintf('<a href="%s" class="name-item">%s</a>', esc_url($order_detail_url), esc_html($item['ma_van_don']));
            case 'created_date':
            case 'delivery_date':
            case 'cus_phone':
            case 'cus_name':
            case 'shipping_address':
            case 'phone_number':
            case 'package_name':
            case 'qty':
            case 'channel':

                return esc_html($item[$column_name]); // Trả về giá trị cho từng cột
            case 'total_amount':
                return esc_html($item['total_amount']); // Trả về tổng tiền đã tính
            case 'status':
                return $item['status']; // Đã lấy trạng thái từ get_order_data
            default:
                return print_r($item, true); // Debugging
        }
    }
    
    function get_sortable_columns() {
        return [
            'ma_van_don'     => ['ma_van_don', false],
            'created_date'   => ['created_date', false],
            'total_amount'          => ['total_amount', false], // Cột tổng tiền có thể sắp xếp
        ];
    }
}
