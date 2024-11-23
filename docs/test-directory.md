test-directory.md

Ah, untuk membuat tombol Test Directory berfungsi, kita perlu menambahkan handler AJAX untuk testnya. Mari saya perbaiki:

1. Tambahkan JavaScript untuk handle kliknya:

```javascript
jQuery(document).ready(function($) {
    // Handle Test Directory button
    $('#test-directory-btn').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $result = $('#test-directory-result');
        var directory = $('input[name="temp_dir"]').val();
        
        $button.prop('disabled', true);
        $button.find('.spinner').addClass('is-active');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'test_directory',
                directory: directory,
                nonce: docgenSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    
                    // Update status indicators
                    $('.temp-dir-status .exists').html('Exists: ' + (response.data.exists ? '✅' : '❌'));
                    $('.temp-dir-status .writable').html('Writable: ' + (response.data.writable ? '✅' : '❌'));
                    if(response.data.free_space) {
                        $('.temp-dir-status .free-space').html('Free Space: ' + response.data.free_space);
                    }
                } else {
                    $result.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error"><p>Test failed: Server error</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false);
                $button.find('.spinner').removeClass('is-active');
            }
        });
    });
});

```

2. Ubah tombol Test Directory di settings page:

```php
<p class="directory-actions">
    <button type="button" id="test-directory-btn" class="button">
        <?php _e('Test Directory', 'docgen-implementation'); ?>
        <span class="spinner"></span>
    </button>
    <button type="submit" name="cleanup_temp" class="button">
        <?php _e('Cleanup Temp Files', 'docgen-implementation'); ?>
    </button>
</p>
<div id="test-directory-result"></div>
```

3. Tambahkan AJAX handler di class admin page:

```php
/**
 * Add AJAX handlers
 */
public function __construct() {
    add_action('wp_ajax_test_directory', array($this, 'ajax_test_directory'));
    
    // Enqueue scripts
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
}

/**
 * Enqueue required scripts
 */
public function enqueue_scripts($hook) {
    if (strpos($hook, 'docgen-implementation-settings') === false) {
        return;
    }
    
    wp_enqueue_script(
        'docgen-settings',
        DOCGEN_IMPLEMENTATION_URL . 'admin/js/settings.js',
        array('jquery'),
        DOCGEN_IMPLEMENTATION_VERSION,
        true
    );
    
    wp_localize_script('docgen-settings', 'docgenSettings', array(
        'nonce' => wp_create_nonce('docgen_directory_test')
    ));
}

/**
 * Handle directory test AJAX request
 */
public function ajax_test_directory() {
    check_ajax_referer('docgen_directory_test', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    $directory = sanitize_text_field($_POST['directory']);
    
    try {
        // Check if directory exists
        if (!file_exists($directory)) {
            // Try to create it
            if (!wp_mkdir_p($directory)) {
                throw new Exception(__('Could not create directory', 'docgen-implementation'));
            }
        }
        
        // Check if writable
        if (!is_writable($directory)) {
            throw new Exception(__('Directory is not writable', 'docgen-implementation'));
        }
        
        // Try to write test file
        $test_file = $directory . '/test-' . time() . '.txt';
        if (!file_put_contents($test_file, 'Test content')) {
            throw new Exception(__('Could not write test file', 'docgen-implementation'));
        }
        
        // Try to read test file
        $content = file_get_contents($test_file);
        if ($content !== 'Test content') {
            throw new Exception(__('Could not read test file correctly', 'docgen-implementation'));
        }
        
        // Clean up test file
        unlink($test_file);
        
        // Get directory info
        $free_space = disk_free_space($directory);
        
        wp_send_json_success(array(
            'message' => __('Directory test successful!', 'docgen-implementation'),
            'exists' => true,
            'writable' => true,
            'free_space' => size_format($free_space),
            'path' => $directory
        ));
        
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
}

```

4. Tambahkan CSS untuk spinner:

```css
.directory-actions .spinner {
    float: none;
    margin: 0 0 0 5px;
}

#test-directory-result {
    margin-top: 10px;
}

.temp-dir-status {
    margin: 10px 0;
}
```

Untuk mengimplementasikan ini:

1. Buat file `admin/js/settings.js` dan masukkan code JavaScript di atas
2. Update class admin page dengan menambahkan constructor dan method AJAX handler
3. Pastikan URL dan path di enqueue_scripts sudah benar
4. Tambahkan CSS ke file style.css yang sudah ada

Sekarang ketika tombol Test Directory diklik:
1. Akan mencoba membuat directory jika belum ada
2. Test write dan read file
3. Update status indikator secara real-time
4. Tampilkan pesan sukses/error
5. Tampilkan info free space

Mau saya bantu implementasikan atau ada yang perlu dijelaskan lebih detail?