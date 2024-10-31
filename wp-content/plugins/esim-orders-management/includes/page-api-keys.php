<?php

function esim_api_keys_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'esim_api_keys';
     // Xử lý thêm API key mới
     if (isset($_POST['add_api_key'])) {
        $expires_at = sanitize_text_field($_POST['expires_at']);
    
        // Chuyển đổi expires_at sang định dạng phù hợp
        $expires_at = str_replace('T', ' ', $expires_at); // Thay T bằng khoảng trắng
        $current_date = date('Y-m-d H:i:s');
        //Kiểm tra xem ngày hết hạn có lớn hơn ngày hiện tại không
        if ($expires_at <= $current_date) {
            echo '<div class="error-message" style="color:red;">Ngày hết hạn phải lớn hơn ngày hiện tại.</div>';
        } else {
            // Đánh dấu tất cả các API key hiện có là inactive
            $wpdb->update($table_name, ['status' => 'inactive'], ['status' => 'active']);
    
            // Tạo API key duy nhất
            $api_key = generate_unique_api_key();
    
            // Thêm API key mới vào bảng
            $wpdb->insert($table_name, [
                'api_key' => $api_key,
                'expires_at' => $expires_at,
                'status' => 'active',
            ]);
            echo '<div class="updated message">Đã thêm API Key thành công!</div>';
        }
    }
    
    
    // Lấy danh sách API keys
    // $api_keys = $wpdb->get_results("SELECT * FROM $table_name");
    $api_keys = $wpdb->get_results("SELECT * FROM $table_name ORDER BY 
    CASE 
        WHEN status = 'active' THEN 0 
        ELSE 1 
    END");


    $default_date_time = (new DateTime())->modify('+7 days')->setTime(0, 0)->format('Y-m-d\TH:i');

    ?>
    <div class="wrap">
        <h1>Quản lý API Keys</h1>

        <form method="post">
            <h2>Thêm API Key</h2>
            <label for="expires_at">Ngày hết hạn:</label>
           <input type="datetime-local" name="expires_at" id="expires_at" value="<?php echo $default_date_time; ?>" required />
            <input type="submit" name="add_api_key" value="Thêm API Key" class="button button-primary" />
        </form>

        <h2>Danh sách API Keys</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>API Key</th>
                    <th>Ngày tạo</th>
                    <th>Ngày hết hạn</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($api_keys as $key) : ?>
                    <tr>
                        <td><?php echo esc_html($key->id); ?></td>
                        <td><?php echo esc_html($key->api_key); ?></td>
                        <td><?php echo esc_html($key->created_at); ?></td>
                        <td><?php echo esc_html($key->expires_at); ?></td>
                        <td>
                            <span class="<?php echo esc_attr($key->status === 'active' ? 'baguette-active' : 'baguette-inactive'); ?>">
                                <?php echo esc_html($key->status); ?>
                            </span>
                        </td>


                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
// Hàm tạo API key ngẫu nhiên
function generate_unique_api_key() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'esim_api_keys';

    do {
        $api_key = bin2hex(random_bytes(32)); // Tạo chuỗi ngẫu nhiên 64 ký tự
        $existing_key_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE api_key = %s",
            $api_key
        ));
    } while ($existing_key_count > 0);

    return $api_key;
}
