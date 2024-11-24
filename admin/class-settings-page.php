<?php
/**
 * Settings Page Handler
 *
 * @package     DocGen_Implementation
 * @subpackage  Admin
 * @version     1.0.2
 * @author      arisciwek
 * 
 * Path: admin/class-settings-page.php
 * 
 * Description: Handles settings page functionality.
 *              Manages configuration untuk temporary directory,
 *              template directory, dan plugin options lainnya.
 * 
 * Changelog:
 * 1.0.2 - 2024-11-24
 * - Added directory migration enqueue script 
 * - Enhanced string localization for migration features
 * - Added dependency handling for JS files
 * 
 * 1.0.1 - 2024-11-24
 * - Added directory testing functionality
 * - Improved template validation
 * - Enhanced UI/UX for directory configuration
 * - Added security measures for directory handling
 * 
 * 1.0.0 - 2024-11-24
 * - Initial implementation
 * - Settings form implementation
 * - Directory configuration
 * - Integration with DirectoryHandler
 * 
 * Dependencies:
 * - class-admin-page.php
 * - class-directory-handler.php
 * - class-directory-migration.php
 * 
 * Usage:
 * Handles all settings page related functionality including:
 * - Directory configuration and testing
 * - Template management
 * - Migration handling
 * - Security validation
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

class DocGen_Implementation_Settings_Page extends DocGen_Implementation_Admin_Page {
    /**
     * DirectoryHandler instance
     * @var DocGen_Implementation_Directory_Handler
     */
    private $directory_handler;

    /**
     * Constructor
     */
    public function __construct() {
        $this->page_slug = 'docgen-implementation-settings';
        $this->directory_handler = new DocGen_Implementation_Directory_Handler();
        
        parent::__construct();
        
        add_action('wp_ajax_test_directory', array($this, 'ajax_test_directory'));
        add_action('wp_ajax_test_template_dir', array($this, 'ajax_test_template_dir'));
        add_action('wp_ajax_get_directory_stats', array($this, 'ajax_get_directory_stats'));
    }

    /**
     * Get page title
     * @return string
     */
    protected function get_page_title() {
        return __('DocGen Implementation Settings', 'docgen-implementation');
    }

    /**
     * Enqueue page specific assets
     */
    protected function enqueue_page_assets() {
        // Enqueue settings script
        wp_enqueue_script(
            'docgen-settings',
            DOCGEN_IMPLEMENTATION_URL . 'admin/js/settings.js',
            array('jquery'),
            DOCGEN_IMPLEMENTATION_VERSION,
            true
        );

        // Enqueue directory migration script
        wp_enqueue_script(
            'docgen-directory-migration',
            DOCGEN_IMPLEMENTATION_URL . 'admin/js/directory-migration.js',
            array('jquery', 'docgen-settings'), // Dependency pada settings.js
            DOCGEN_IMPLEMENTATION_VERSION,
            true
        );

        // Localize script dengan tambahan strings untuk migrasi
        wp_localize_script('docgen-settings', 'docgenSettings', array(
            'nonce' => wp_create_nonce('docgen_admin_nonce'),
            'strings' => array(
                'testSuccess' => __('Test successful!', 'docgen-implementation'),
                'testFailed' => __('Test failed:', 'docgen-implementation'),
                'selectTemplate' => __('Select template...', 'docgen-implementation'),
                // Tambahan strings untuk migrasi
                'migrationPrompt' => __('Directory changes detected. Migration may be needed:', 'docgen-implementation'),
                'migrating' => __('Migrating files...', 'docgen-implementation'),
                'migrationComplete' => __('Migration completed!', 'docgen-implementation'),
                'migrationError' => __('Migration failed:', 'docgen-implementation'),
                'from' => __('From', 'docgen-implementation'),
                'to' => __('To', 'docgen-implementation'),
                'files' => __('Files', 'docgen-implementation'),
                'migrated' => __('Migrated', 'docgen-implementation'),
                'skipped' => __('Skipped', 'docgen-implementation'),
                'errors' => __('Errors', 'docgen-implementation'),
                'templateDir' => __('Template Directory', 'docgen-implementation'),
                'tempDir' => __('Temporary Directory', 'docgen-implementation'),
                'migrationConfirm' => __('Do you want to migrate these files?', 'docgen-implementation')
            )
        ));
    }


    /**
     * Handle AJAX directory test
     */
    public function ajax_test_directory() {
        check_ajax_referer('docgen_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'docgen-implementation'));
        }
        
        // Get uploads directory
        $upload_dir = wp_upload_dir();
        $upload_base = $upload_dir['basedir'];
        
        // Get directory from POST and build full path
        $directory = sanitize_text_field($_POST['directory']);
        $full_path = trailingslashit($upload_base) . $directory;
        
        $dir_type = sanitize_text_field($_POST['type'] ?? 'Temporary Directory');
        
        // Debug: Tampilkan path yang akan divalidasi
        error_log('Testing directory: ' . $full_path);
        
        // Set directory type for context
        $this->directory_handler->set_directory_type($dir_type);
        
        // Validate with full path
        $validation = $this->directory_handler->validate_directory_path($full_path);
        
        if (is_wp_error($validation)) {
            wp_send_json_error(sprintf(
                __('Failed to validate %s: %s', 'docgen-implementation'),
                strtolower($dir_type),
                $validation->get_error_message()
            ));
        }
        
        // Create directory if needed
        $result = wp_mkdir_p($full_path);
        if (!$result) {
            wp_send_json_error(sprintf(
                __('Failed to create %s', 'docgen-implementation'),
                strtolower($dir_type)
            ));
        }
        
        // Get directory stats with full path
        $stats = $this->directory_handler->get_directory_stats($full_path);
        if (is_wp_error($stats)) {
            wp_send_json_error(sprintf(
                __('Failed to get %s stats: %s', 'docgen-implementation'),
                strtolower($dir_type),
                $stats->get_error_message()
            ));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%s test successful!', 'docgen-implementation'), $dir_type),
            'stats' => $stats,
            'exists' => true,
            'writable' => is_writable($full_path),
            'free_space' => size_format(disk_free_space($full_path)),
            'path' => $full_path
        ));
    }


    /**
     * Handle AJAX template directory test
     */
    public function ajax_test_template_dir() {
        check_ajax_referer('docgen_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'docgen-implementation'));
        }
        
        // Get uploads directory
        $upload_dir = wp_upload_dir();
        $upload_base = $upload_dir['basedir'];
        
        // Get directory from POST and build full path
        $directory = sanitize_text_field($_POST['directory']);
        $full_path = trailingslashit($upload_base) . $directory;
        
        // Debug log
        error_log('Testing template directory: ' . $full_path);
        
        // Set directory type for context
        $this->directory_handler->set_directory_type('Template Directory');
        
        $validation = $this->directory_handler->validate_directory_path($full_path);
        
        if (is_wp_error($validation)) {
            wp_send_json_error($validation->get_error_message());
        }
        
        // Create directory if needed
        $result = wp_mkdir_p($full_path);
        if (!$result) {
            wp_send_json_error(sprintf(
                __('Failed to create template directory: %s', 'docgen-implementation'),
                'Permission denied'
            ));
        }
        
        // Scan for templates
        $templates = $this->directory_handler->scan_template_files($full_path);
        if (is_wp_error($templates)) {
            wp_send_json_error(sprintf(
                __('Failed to scan templates: %s', 'docgen-implementation'),
                $templates->get_error_message()
            ));
        }
        
        // Format template info
        $formatted_templates = array_map(function($template) {
            return array(
                'name' => $template['name'],
                'size' => size_format($template['size']),
                'modified' => date_i18n(get_option('date_format'), $template['modified']),
                'type' => strtoupper($template['type']),
                'valid' => $template['is_valid']
            );
        }, $templates);
        
        wp_send_json_success(array(
            'message' => __('Template directory scan completed!', 'docgen-implementation'),
            'templates' => $formatted_templates,
            'template_count' => count($templates),
            'valid_count' => count(array_filter($templates, function($t) { return $t['is_valid']; })),
            'exists' => true,
            'readable' => is_readable($full_path),
            'path' => $full_path
        ));
    }



    /**
     * Handle AJAX get directory stats
     */
    public function ajax_get_directory_stats() {
        check_ajax_referer('docgen_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'docgen-implementation'));
        }
        
        $directory = sanitize_text_field($_POST['directory']);
        $stats = $this->directory_handler->get_directory_stats($directory);
        
        if (is_wp_error($stats)) {
            wp_send_json_error($stats->get_error_message());
        }
        
        wp_send_json_success($stats);
    }



    /**
     * Handle form submissions
     * @return bool|WP_Error
     */
    protected function handle_submissions() {
        if (!isset($_POST['docgen_implementation_settings_nonce'])) {
            return false;
        }

        if (!check_admin_referer('docgen_implementation_settings', 'docgen_implementation_settings_nonce')) {
            return new WP_Error('invalid_nonce', __('Security check failed', 'docgen-implementation'));
        }

        $settings = $this->validate_and_save_settings($_POST);
        if (is_wp_error($settings)) {
            return $settings;
        }

        add_settings_error(
            'docgen_implementation_settings',
            'settings_updated',
            __('Settings saved.', 'docgen-implementation'),
            'updated'
        );

        return true;
    }

    /**
     * Validate and save settings
     * @param array $data Form data
     * @return array|WP_Error Settings array or error
     */
    private function validate_and_save_settings($data) {
        $upload_dir = wp_upload_dir();
        $upload_base = $upload_dir['basedir'];
        $content_base = $this->get_content_base_dir();

        // Validate temp directory
        $temp_folder = $this->validate_directory_name($data['temp_dir'] ?? '');
        if (is_wp_error($temp_folder)) {
            return $temp_folder;
        }

        // Validate template directory
        $template_folder = $this->validate_directory_name($data['template_dir'] ?? '');
        if (is_wp_error($template_folder)) {
            return $template_folder;
        }

        $settings = array(
            'temp_dir' => trailingslashit($upload_base) . $temp_folder,
            'template_dir' => trailingslashit($upload_base) . $template_folder,
            'output_format' => sanitize_text_field($data['output_format'] ?? 'docx'),
            'debug_mode' => isset($data['debug_mode'])
        );

        update_option('docgen_implementation_settings', $settings);
        return $settings;
    }

    /**
     * Render settings page
     */
    public function render() {
        $settings = get_option('docgen_implementation_settings', array());
        $submission_result = $this->handle_submissions();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->get_page_title()) . '</h1>';

        // Display errors if any
        if (is_wp_error($submission_result)) {
            echo '<div class="notice notice-error"><p>' . esc_html($submission_result->get_error_message()) . '</p></div>';
        }
        
        settings_errors('docgen_implementation_settings');

        echo '<form method="post" action="">';
        wp_nonce_field('docgen_implementation_settings', 'docgen_implementation_settings_nonce');

        $this->render_directory_settings($settings);
        $this->render_general_settings($settings);

        submit_button();
        echo '</form>';
        echo '</div>';
    }

    /**
     * Render directory settings section
     * @param array $settings Current settings
     */
    private function render_directory_settings($settings) {
        $upload_dir = wp_upload_dir();
        $upload_base = $upload_dir['basedir'];
        $content_base = $this->get_content_base_dir();

        echo '<table class="form-table">';
        
        // Temporary Directory
        echo '<tr>';
        echo '<th scope="row">' . esc_html__('Temporary Directory', 'docgen-implementation') . '</th>';
        echo '<td>';
        echo '<div class="template-dir-input">';
        echo '<code class="base-path">' . esc_html($upload_base) . '/</code>';
        echo '<input type="text" name="temp_dir" value="' . esc_attr(basename($settings['temp_dir'] ?? 'docgen-temp')) . '" ';
        echo 'class="regular-text folder-name" placeholder="docgen-temp" style="width: 200px;" />';
        echo '</div>';
        
        echo '<p class="description">' . esc_html__('Directory for temporary files. Must be writable.', 'docgen-implementation') . '</p>';
        echo '<p class="directory-actions">';
        echo '<button type="button" id="test-directory-btn" class="button">';
        echo esc_html__('Test Directory', 'docgen-implementation');
        echo '<span class="spinner"></span>';
        echo '</button>';
        echo '<button type="submit" name="cleanup_temp" class="button">';
        echo esc_html__('Cleanup Temp Files', 'docgen-implementation');
        echo '</button>';
        echo '</p>';
        echo '<div id="test-directory-result"></div>';
        echo '<div class="temp-dir-status"></div>';
        echo '</td>';
        echo '</tr>';

        // Template Directory
        echo '<tr>';
        echo '<th scope="row">' . esc_html__('Template Directory', 'docgen-implementation') . '</th>';
        echo '<td>';
        echo '<div class="template-dir-input">';
        echo '<code class="base-path">' . esc_html($content_base) . '</code>';
        echo '<input type="text" name="template_dir" value="' . esc_attr(basename($settings['template_dir'] ?? 'docgen-templates')) . '" ';
        echo 'class="regular-text folder-name" placeholder="docgen-templates" style="width: 200px;" />';
        echo '</div>';
        
        echo '<p class="description">' . esc_html__('Directory for template files (DOCX/ODT).', 'docgen-implementation') . '</p>';
        echo '<p class="directory-actions">';
        echo '<button type="button" id="test-template-dir-btn" class="button">';
        echo esc_html__('Test Template Directory', 'docgen-implementation');
        echo '<span class="spinner"></span>';
        echo '</button>';
        echo '</p>';
        echo '<div id="test-template-dir-result"></div>';
        echo '<div class="template-dir-status"></div>';
        echo '</td>';
        echo '</tr>';

        echo '</table>';
    }

    /**
     * Render general settings section
     * @param array $settings Current settings
     */
    private function render_general_settings($settings) {
        echo '<table class="form-table">';
        
        // Output Format
        echo '<tr>';
        echo '<th scope="row">' . esc_html__('Default Output Format', 'docgen-implementation') . '</th>';
        echo '<td>';
        echo '<select name="output_format">';
        echo '<option value="docx" ' . selected($settings['output_format'] ?? 'docx', 'docx', false) . '>';
        echo esc_html__('DOCX', 'docgen-implementation');
        echo '</option>';
        echo '<option value="pdf" ' . selected($settings['output_format'] ?? 'docx', 'pdf', false) . '>';
        echo esc_html__('PDF', 'docgen-implementation');
        echo '</option>';
        echo '</select>';
        echo '</td>';
        echo '</tr>';

        // Debug Mode
        echo '<tr>';
        echo '<th scope="row">' . esc_html__('Debug Mode', 'docgen-implementation') . '</th>';
        echo '<td>';
        echo '<label>';
        echo '<input type="checkbox" name="debug_mode" value="1" ' . checked($settings['debug_mode'] ?? false, true, false) . ' />';
        echo esc_html__('Enable debug mode', 'docgen-implementation');
        echo '</label>';
        echo '</td>';
        echo '</tr>';

        echo '</table>';
    }
}