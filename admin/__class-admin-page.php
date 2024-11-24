<?php
/**
 * Admin Page Handler
 *
 * @package     DocGen_Implementation
 * @subpackage  Admin
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: admin/class-admin-page.php
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

class DocGen_Implementation_Admin_Page {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_test_directory', array($this, 'ajax_test_directory'));
        add_action('wp_ajax_test_template', array($this, 'ajax_test_template'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue required scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'docgen-implementation') === false) {
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
            'nonce' => wp_create_nonce('docgen_admin_nonce'),
            'strings' => array(
                'testSuccess' => __('Test successful!', 'docgen-implementation'),
                'testFailed' => __('Test failed:', 'docgen-implementation')
            )
        ));
    }

    /**
     * Handle directory test AJAX request
     */
    public function ajax_test_directory() {
        check_ajax_referer('docgen_admin_nonce', 'nonce');
        
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

    /**
     * Handle template test AJAX request
     */
    public function ajax_test_template() {
        check_ajax_referer('docgen_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $template_path = sanitize_text_field($_POST['template']);
        
        try {
            // Check if template exists
            if (!file_exists($template_path)) {
                throw new Exception(__('Template file not found', 'docgen-implementation'));
            }

            // Check file extension
            $extension = strtolower(pathinfo($template_path, PATHINFO_EXTENSION));
            if (!in_array($extension, array('docx', 'odt'))) {
                throw new Exception(__('Invalid template format. Must be DOCX or ODT', 'docgen-implementation'));
            }

            // Try to read template
            if ($extension === 'docx') {
                $this->test_docx_template($template_path);
            } else {
                $this->test_odt_template($template_path);
            }
            
            // Get template info
            $template_size = filesize($template_path);
            $template_modified = filemtime($template_path);
            
            wp_send_json_success(array(
                'message' => __('Template test successful!', 'docgen-implementation'),
                'format' => strtoupper($extension),
                'size' => size_format($template_size),
                'modified' => date_i18n(get_option('date_format'), $template_modified),
                'path' => $template_path
            ));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Test DOCX template
     */
    private function test_docx_template($path) {
        // Check if zip archive can be opened
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new Exception(__('Could not read DOCX file', 'docgen-implementation'));
        }

        // Check for required files
        $required_files = array(
            '[Content_Types].xml',
            'word/document.xml',
        );

        foreach ($required_files as $file) {
            if ($zip->locateName($file) === false) {
                $zip->close();
                throw new Exception(sprintf(
                    /* translators: %s: Missing file name */
                    __('Invalid DOCX structure: Missing %s', 'docgen-implementation'),
                    $file
                ));
            }
        }

        // Try to read document.xml
        $content = $zip->getFromName('word/document.xml');
        if ($content === false) {
            $zip->close();
            throw new Exception(__('Could not read document content', 'docgen-implementation'));
        }

        // Check if content is valid XML
        $xml = simplexml_load_string($content);
        if ($xml === false) {
            $zip->close();
            throw new Exception(__('Invalid document XML content', 'docgen-implementation'));
        }

        $zip->close();
    }

    /**
     * Test ODT template
     */
    private function test_odt_template($path) {
        // Check if zip archive can be opened
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new Exception(__('Could not read ODT file', 'docgen-implementation'));
        }

        // Check for required files
        $required_files = array(
            'META-INF/manifest.xml',
            'content.xml',
            'styles.xml'
        );

        foreach ($required_files as $file) {
            if ($zip->locateName($file) === false) {
                $zip->close();
                throw new Exception(sprintf(
                    /* translators: %s: Missing file name */
                    __('Invalid ODT structure: Missing %s', 'docgen-implementation'),
                    $file
                ));
            }
        }

        // Try to read content.xml
        $content = $zip->getFromName('content.xml');
        if ($content === false) {
            $zip->close();
            throw new Exception(__('Could not read document content', 'docgen-implementation'));
        }

        // Check if content is valid XML
        $xml = simplexml_load_string($content);
        if ($xml === false) {
            $zip->close();
            throw new Exception(__('Invalid document XML content', 'docgen-implementation'));
        }

        $zip->close();
    }

    /**
     * Render settings page
     */
public function render_settings() {
    // Get plugin settings
    $settings = get_option('docgen_implementation_settings', array());
    
    // Handle form submission
    if (isset($_POST['docgen_implementation_settings_nonce'])) {
        if (check_admin_referer('docgen_implementation_settings', 'docgen_implementation_settings_nonce')) {
            $settings = $this->save_settings($_POST);
        }
    }
    ?>
    <div class="wrap">
        <h1><?php _e('DocGen Implementation Settings', 'docgen-implementation'); ?></h1>

        <form method="post" action="">
            <?php wp_nonce_field('docgen_implementation_settings', 'docgen_implementation_settings_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Temporary Directory', 'docgen-implementation'); ?>
                    </th>
                    <td>
                        <?php 
                        $upload_dir = wp_upload_dir();
                        $base_path = $upload_dir['basedir']; 
                        $temp_folder = isset($settings['temp_dir']) ? basename($settings['temp_dir']) : 'docgen-temp';
                        ?>
                        <div class="template-dir-input">
                            <code class="base-path"><?php echo esc_html($base_path); ?>/</code>
                            <input type="text" 
                                   name="temp_dir" 
                                   value="<?php echo esc_attr($temp_folder); ?>" 
                                   class="regular-text folder-name" 
                                   placeholder="docgen-temp"
                                   style="width: 200px;" />
                        </div>
                        <p class="description">
                            <?php _e('Directory for temporary files. Must be writable.', 'docgen-implementation'); ?>
                        </p>
                        <p class="description">
                            <?php _e('Example: docgen-temp, temp-files', 'docgen-implementation'); ?>
                        </p>
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
                        <div class="temp-dir-status"></div>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php _e('Template Directory', 'docgen-implementation'); ?>
                    </th>
                    <td>
                        <?php 
                        $template_folder = isset($settings['template_dir']) ? basename($settings['template_dir']) : 'docgen-templates';
                        ?>
                        <div class="template-dir-input">
                            <code class="base-path"><?php echo esc_html($base_path); ?>/</code>
                            <input type="text" 
                                   name="template_dir" 
                                   value="<?php echo esc_attr($template_folder); ?>" 
                                   class="regular-text folder-name" 
                                   placeholder="docgen-templates"
                                   style="width: 200px;" />
                        </div>
                        <p class="description">
                            <?php _e('Enter folder name for template files (DOCX/ODT).', 'docgen-implementation'); ?>
                        </p>
                        <p class="description">
                            <?php _e('Example: certificate-templates, docgen-templates, template-files', 'docgen-implementation'); ?>
                        </p>
                        <p class="directory-actions">
                            <button type="button" id="test-template-dir-btn" class="button">
                                <?php _e('Test Template Directory', 'docgen-implementation'); ?>
                                <span class="spinner"></span>
                            </button>
                        </p>
                        <div id="test-template-dir-result"></div>
                        <div class="template-dir-status"></div>
                    </td>
                </tr>


                    <tr>
                        <th scope="row">
                            <?php _e('Default Output Format', 'docgen-implementation'); ?>
                        </th>
                        <td>
                            <select name="output_format">
                                <option value="docx" <?php selected($settings['output_format'] ?? 'docx', 'docx'); ?>>
                                    <?php _e('DOCX', 'docgen-implementation'); ?>
                                </option>
                                <option value="pdf" <?php selected($settings['output_format'] ?? 'docx', 'pdf'); ?>>
                                    <?php _e('PDF', 'docgen-implementation'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <?php _e('Debug Mode', 'docgen-implementation'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="debug_mode" 
                                       value="1" 
                                       <?php checked($settings['debug_mode'] ?? false); ?> />
                                <?php _e('Enable debug mode', 'docgen-implementation'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

	/**
	 * Save settings
	 * @param array $data Form data
	 * @return array Updated settings
	 */
    private function save_settings($data) {
        $upload_dir = wp_upload_dir();
        $base_path = trailingslashit(WP_CONTENT_DIR) . 'docgen-templates';  // Untuk Template Directory

        
        // Sanitize folder names and remove any path traversal attempts
        $temp_folder = sanitize_file_name($data['temp_dir'] ?? 'docgen-temp');
        $temp_folder = basename($temp_folder);
        
        $template_folder = sanitize_file_name($data['template_dir'] ?? 'docgen-templates');
        $template_folder = basename($template_folder);
        
        $settings = array(
            'temp_dir' => trailingslashit($base_path) . $temp_folder,
            'template_dir' => trailingslashit($base_path) . $template_folder,
            'output_format' => sanitize_text_field($data['output_format'] ?? 'docx'),
            'debug_mode' => isset($data['debug_mode'])
        );

        update_option('docgen_implementation_settings', $settings);

        add_settings_error(
            'docgen_implementation_settings',
            'settings_updated',
            __('Settings saved.', 'docgen-implementation'),
            'updated'
        );

        return $settings;
    }

	/**
	 * Create secure temporary directory
	 * @param string $dir_name Nama folder temp
	 * @return string|WP_Error Path ke folder atau error
	 */
	private function create_secure_temp_dir($dir_name) {
	    $upload_dir = wp_upload_dir();
	    $temp_dir = trailingslashit($upload_dir['basedir']) . $dir_name;
	    
	    // Buat folder jika belum ada
	    if (!file_exists($temp_dir)) {
	        if (!wp_mkdir_p($temp_dir)) {
	            return new WP_Error(
	                'temp_dir_create_failed',
	                __('Could not create temporary directory', 'docgen-implementation')
	            );
	        }
	        
	        // Buat .htaccess untuk mencegah direct access
	        $htaccess = $temp_dir . '/.htaccess';
	        $rules = "Order deny,allow\n";
	        $rules .= "Deny from all\n";
	        
	        if (!file_put_contents($htaccess, $rules)) {
	            return new WP_Error(
	                'htaccess_create_failed',
	                __('Could not create .htaccess file', 'docgen-implementation')
	            );
	        }
	        
	        // Buat index.php kosong untuk mencegah directory listing
	        $index = $temp_dir . '/index.php';
	        if (!file_put_contents($index, "<?php\n// Silence is golden.")) {
	            return new WP_Error(
	                'index_create_failed',
	                __('Could not create index.php file', 'docgen-implementation')
	            );
	        }

	        // Set permissions yang aman
	        chmod($temp_dir, 0755);
	        chmod($htaccess, 0444);
	        chmod($index, 0444);
	    }
	    
	    return $temp_dir;
	}

	/**
	 * Cleanup old temporary files
	 * @param int $max_age Maximum age in hours (default 24)
	 */
	private function cleanup_temp_files($max_age = 24) {
	    $settings = get_option('docgen_implementation_settings');
	    $temp_dir = isset($settings['temp_dir']) ? $settings['temp_dir'] : '';
	    
	    if (!$temp_dir || !is_dir($temp_dir)) {
	        return;
	    }
	    
	    $now = time();
	    $files = glob($temp_dir . '/*');
	    
	    foreach ($files as $file) {
	        // Skip .htaccess dan index.php
	        if (basename($file) === '.htaccess' || basename($file) === 'index.php') {
	            continue;
	        }
	        
	        // Hapus file yang lebih tua dari $max_age jam
	        if (is_file($file) && ($now - filemtime($file) > $max_age * 3600)) {
	            @unlink($file);
	        }
	    }
	}
	/**
	 * Get default temp folder name
	 * @return string
	 */
	private function get_default_temp_folder() {
	    $site_id = get_current_blog_id();
	    return 'docgen-temp-' . $site_id;
	}

	/**
	 * Validate and save temp directory setting
	 * @param string $folder_name
	 * @return string|WP_Error
	 */
	private function validate_temp_dir($folder_name) {
	    // Sanitize folder name
	    $folder_name = sanitize_file_name($folder_name);
	    $folder_name = basename($folder_name);
	    
	    // Pastikan folder name valid
	    if (empty($folder_name) || strpos($folder_name, '..') !== false) {
	        return new WP_Error(
	            'invalid_temp_dir',
	            __('Invalid temporary directory name', 'docgen-implementation')
	        );
	    }
	    
	    // Create secure directory
	    $temp_dir = $this->create_secure_temp_dir($folder_name);
	    if (is_wp_error($temp_dir)) {
	        return $temp_dir;
	    }
	    
	    return $temp_dir;
	}

	/**
	 * Schedule temp files cleanup
	 */
	public function schedule_temp_cleanup() {
	    $this->cleanup_temp_files(24); // Hapus file lebih dari 24 jam
	}
}
