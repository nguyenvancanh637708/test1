<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Custom_Customer_List_Table extends WP_List_Table {
    
    function __construct() {
        parent::__construct([
            'singular' => 'order',
            'plural'   => 'orders',
            'ajax'     => false
        ]);
    }

    function get_columns() {
        return [
            'code_request'     => 'Mã yêu cầu',
            'created_date'     => 'Thời gian đặt',
            'customer_name'    => 'Tên KH',
            'customer_phone'   => 'SĐT KH',
            'package_name'     => 'Gói cước',
            'phone_number'     => 'SIM đặt',   
            // 'sim_price'        => 'giá SIM',
            // 'goicuoc_price'    => 'Giá gói cước',
            'total_price'      => 'Tổng tiền',
            'sales_channel'    => 'Kênh bán',
            'user_id'          => 'Nhân viên gọi',
            // 'note'             => 'Ghi chú',
            'order_data_id'             => 'Đơn hàng',
            'status'           => 'Trạng thái',
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
        $table_name = $wpdb->prefix . 'esim_orders';
        $query = "SELECT * FROM $table_name WHERE 1=1";
        $params = []; 
    
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-7 days'));
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
        $cus_phone = isset($_GET['cus_phone']) ? sanitize_text_field($_GET['cus_phone']) : '';
        $channel = isset($_GET['channel']) ? sanitize_text_field($_GET['channel']) : '';
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $goicuoc_ids = isset($_GET['goicuoc_ids']) && is_array($_GET['goicuoc_ids'])
            ? array_map('intval', array_filter($_GET['goicuoc_ids'], 'is_numeric'))
            : array();
    
        if (!empty($start_date)) {
            $query .= " AND created_date >= %s";
            $params[] = $start_date;
        }
        if (!empty($end_date)) {
            $query .= " AND created_date <= %s";
            $params[] = $end_date . ' 23:59:59';
        }
    
        if (!empty($channel)) {
            $query .= " AND sales_channel = %s";
            $params[] = $channel;
        }

        $valid_statuses = ['0', '1', '-1',]; 
        if (in_array($status, $valid_statuses, true)) {
            $query .= " AND status = %s";
            $params[] = $status;
        }

        
    
        if (!empty($goicuoc_ids)) {
            $all_ids = [];
    
            foreach ($goicuoc_ids as $parent_id) {
                $parent_id = intval($parent_id);
                $all_ids[] = $parent_id;
                $product = wc_get_product($parent_id);
                if ($product && $product->is_type('variable')) {
                    $variation_ids = $product->get_children();
                    $all_ids = array_merge($all_ids, array_map('intval', $variation_ids));
                }
            }
            $all_ids = array_unique($all_ids);
            if (!empty($all_ids)) {
                $placeholders = implode(',', array_fill(0, count($all_ids), '%d'));
                $query .= " AND goicuoc_id IN ($placeholders)";
                $params = array_merge($params, $all_ids);
            }
        }
    
        if (!empty($cus_phone)) {
            $query .= " AND customer_phone LIKE %s";
            $params[] = '%' . $wpdb->esc_like($cus_phone) . '%';
        }
    
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_date';
        $order = isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc']) ? $_GET['order'] : 'desc';
        $query .= " ORDER BY $orderby $order";
    
        $results = $wpdb->get_results($wpdb->prepare($query, ...$params), ARRAY_A);
        $data = [];
    
        foreach ($results as $row) {
            $status_display = $this->get_status_display($row['status']);
            $user_display = $this->get_user_display($row['user_id']);
            $link = $this->get_link_esim_order_data($row['order_data_id']);
            $data[] = [
                'id'                    => esc_html($row['id']),
                'code_request'          => esc_html($row['code_request']),
                'created_date'          => esc_html($row['created_date']),
                'customer_name'         => esc_html($row['customer_name']),
                'customer_phone'        => esc_html($row['customer_phone']),
                'package_name'          => esc_html($row['package_name']),
                'phone_number'          => esc_html($row['phone_number']),
                'sim_price'             => number_format($row['sim_price'], 0, ',', '.') . ' ₫',
                'goicuoc_price'         => number_format($row['goicuoc_price'], 0, ',', '.') . ' ₫',
                'total_price'           => number_format($row['total_price'], 0, ',', '.') . ' ₫',
                'sales_channel'         => esc_html($row['sales_channel']),
                'user_id'               => $user_display,
                'note'                  => esc_html($row['note']),
                'order_data_id'         => $link,
                'status'                => $status_display,
            ];
        }
    
        return $data;
    }
    

    function get_status_display($status) {
        $status_display = '';
        
        switch ($status) {
            case '1':
                $status_display = '<span class="badge badge-success">Thành công</span>';
                break;
            case '-1':
                $status_display = '<span class="badge badge-danger">Thất bại</span>';
                break;
            default:
                $status_display = '<span class="badge badge-secondary">Chờ xử lý</span>';
                break;
        }
        return $status_display;
    }

    function get_user_display($user_id) {
        $user = get_user_by('ID', $user_id);
        if ($user) {
            return esc_html($user->display_name);
        } else {
            return 'N/A'; 
        }
    }

    function get_link_esim_order_data($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'esim_order_data';
        $order_data = $wpdb->get_row($wpdb->prepare("SELECT id, ma_van_don FROM $table_name WHERE id = %d", $id), ARRAY_A);
    
        if ($order_data) {
            $ma_van_don = esc_html($order_data['ma_van_don']); 
            $link = admin_url('admin.php?page=edit-don-hang&order_id=' . urlencode($id)); 

            return "<a href=\"$link\" class=\"name-item\">$ma_van_don</a>";
        }
    
        return 'N/A'; 
    }
    

    function column_cb($item) {
        return sprintf('<input type="checkbox" name="order[]" value="%s" />', esc_attr($item['code_request']));
    }
    
    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'code_request':
                $order_detail_url = admin_url('admin.php?page=edit-kh-dat-sim&order_id=' . urlencode($item['id']));
                return sprintf('<a href="%s" class="name-item">%s</a>', esc_url($order_detail_url), esc_html($item['code_request']));
            case 'created_date':
            case 'customer_name':
            case 'customer_phone':
            case 'package_name':
            case 'phone_number':
            case 'sim_price':
            case 'goicuoc_price':
            case 'total_price':
            case 'sales_channel':
            case 'note':
                return esc_html($item[$column_name]);
            case 'user_id':
                return $item['user_id'];
            case 'status':
                return $item['status']; 
            case 'order_data_id':
                return $item['order_data_id']; 
            default:
                return print_r($item, true); // Debugging
        }
    }
    
    function get_sortable_columns() {
        return [
            'code_request'     => ['code_request', false],
            'created_date'   => ['created_date', true],
            'total_price'          => ['total_price', false], 
        ];
    }
}
