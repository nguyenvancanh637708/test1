<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Custom_History_Sync_List_Table extends WP_List_Table {
    
    function __construct() {
        parent::__construct([
            'singular' => 'sync_log',
            'plural'   => 'sync_logs',
            'ajax'     => false
        ]);
    }

    function get_columns() {
        return [
            'stt'        => 'STT',
            'landing_id' => 'Landing ID',
            'wp_order_id' => 'Id Đơn Hàng',
            'synced_at'  => 'Thời Gian',
            'action'     => 'Hành Động',
            'status'     => 'Trạng Thái',
            'response'   => 'Nội Dung',
        ];
    }

    function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];
        $data = $this->get_order_data();

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $this->items = array_slice($data, (($current_page - 1) * $per_page), $per_page);
        
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }

    function get_order_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'esim_order_data_sync';

        $landing_id = isset($_GET['landing_id']) ? sanitize_text_field($_GET['landing_id']) : '';
        $wp_order_id = isset($_GET['wp_order_id']) ? sanitize_text_field($_GET['wp_order_id']) : '';

    
        $query = "SELECT * FROM $table_name WHERE 1=1"; 



        if (!empty($landing_id)) {
            $query .= $wpdb->prepare(" AND landing_id = %s", $landing_id);
        }

        if (!empty($wp_order_id)) {
            $query .= $wpdb->prepare(" AND wp_order_id = %d", $wp_order_id);
        }

        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'synced_at';
        $order = isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc']) ? $_GET['order'] : 'desc';
        
        $query .= " ORDER BY $orderby $order"; 
        $results = $wpdb->get_results($query, ARRAY_A);
        $data = [];
        
        foreach ($results as $index => $row) {
            $response = $row['response'];
            
            // Kiểm tra nếu response là JSON hợp lệ
            $response_data = json_decode($response);
            if (json_last_error() === JSON_ERROR_NONE) {
                $response_display = $response_data; // Nếu là JSON hợp lệ
            } else {
                $response_display = $response; // Nếu không, hiển thị dưới dạng chuỗi
            }
    
            $status_display = $this->get_status_display($row['status']);
            $data[] = [
                'stt'         => $index + 1,
                'landing_id'  => esc_html($row['landing_id']),
                'wp_order_id' => esc_html($row['wp_order_id']),
                'synced_at'   => esc_html($row['synced_at']),
                'action'   => esc_html($row['action']),
                'status'      => $status_display,
                'response'    => $response_display, // Thay đổi ở đây
            ];
        }
        
        return $data;
    }
    

    function column_cb($item) {
        return sprintf('<input type="checkbox" name="order[]" value="%s" />', esc_attr($item['wp_order_id'])); // Thay đổi giá trị checkbox
    }
    
    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'stt':
            case 'landing_id':
            case 'wp_order_id':
            case 'synced_at':
            case 'action':
                return esc_html($item[$column_name]);
            case 'status':
                return $item['status']; 
            case 'response':
                return sprintf('<button class="view-response" data-response="%s">Xem chi tiết</button>', esc_attr(json_encode($item['response'])));
                
            default:
                return print_r($item, true); 
        }
    }
    
    function get_sortable_columns() {
        return [
            'landing_id' => ['landing_id', false],
            'wp_order_id' => ['wp_order_id', false],
            'synced_at'  => ['synced_at', false],
            'status'     => ['status', false],
        ];
    }

    function get_status_display($status) {
        $status_display = '';
        switch ($status) {
            case 'success': 
                $status_display = '<span class="baguette baguette-active">Thành công</span>';
                break;
            // case 'error': 
            //     $status_display = '<span class="badge badge-danger">Thất bại</span>';
            default: 
                // $status_display = '<span class="badge badge-secondary">'.$status.'</span>';
                $status_display = '<span class="baguette baguette-inactive">Thất bại</span>';

                break;
        }
    
        return $status_display;
    }
}

// Thêm JavaScript để mở popup hiển thị nội dung JSON
add_action('admin_footer', 'esim_sync_history_popup_script');
function esim_sync_history_popup_script() {
    ?>
    <div class="modal-overlay">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <pre id="modal-json-content"></pre>
        </div>
    </div>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.view-response').on('click', function() {
                var responseData = $(this).data('response');
                $('#modal-json-content').text(JSON.stringify(responseData, null, 2));
                $('.modal-overlay').fadeIn();
            });
            $('.modal-close, .modal-overlay').on('click', function() {
                $('.modal-overlay').fadeOut();
            });
            $('.modal-content').on('click', function(event) {
                event.stopPropagation();
            });
        });
    </script>
    <?php
}

