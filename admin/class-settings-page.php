<?php
/**
 * Settings Page Handler
 *
 * @package     DocGen_Implementation
 * @subpackage  Admin
 * @version     1.0.1
 * @author      arisciwek
 * 
 * Path: admin/class-settings-page.php
 * 
 * Description: Handles settings page functionality.
 *              Manages configuration untuk temporary directory,
 *              template directory, dan plugin options lainnya.
 *              Terintegrasi dengan DirectoryHandler untuk manajemen
 *              dan validasi directory yang lebih baik.
 * 
 * Changelog:
 * 1.0.1 - 2024-11-24
 * - Added integration with enhanced DirectoryHandler
 * - Added directory statistics display
 * - Added template scanning and validation
 * - Improved error handling and validation
 * - Added real-time directory status updates
 * 
 * 1.0.0 - 2024-11-24
 * - Initial implementation
 * - Basic settings form implementation
 * - Directory configuration
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
        wp_enqueue_style(
            'docgen-settings',
            DOCGEN_IMPLEMENTATION_URL . 'assets/css/settings.css',
            array(),
            DOCGEN_IMPLEMENTATION_VERSION
        );

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
                'testFailed' => __('Test failed:', 'docgen-implementation'),
                'selectTemplate' => __('Select template...', 'docgen-implementation'),
                'scanning' => __('Scanning directory...', 'docgen-implementation'),
                'validating' => __('Validating templates...', 'docgen-implementation'),
                'cleanupConfirm' => __('Are you sure you want to clean this directory? This action cannot be undone.', 'docgen-implementation')
            ),
            'refreshInterval' => 30, // Refresh stats every 30 seconds
            'i18n' => array(
                'bytes' => __('bytes', 'docgen-implementation'),
                'kb' => __('KB', 'docgen-implementation'),
                'mb' => __('MB', 'docgen-implementation'),
                'gb' => __('GB', 'docgen-implementation')
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
        
        $directory = sanitize_text_field($_POST['directory']);
        $validation = $this->directory_handler->validate_directory_path($directory);
        
        if (is_wp_error($validation)) {
            wp_send_json_error($validation->get_error_message());
        }
        
        // Create directory if needed
        $result = $this->directory_handler->create_directory($directory);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Get directory stats
        $stats = $this->directory_handler->get_directory_stats($directory);
        if (is_wp_error($stats)) {
            wp_send_json_error($stats->get_error_message());
        }
        
        wp_send_json_success(array(
            'message' => __('Directory test successful!', 'docgen-implementation'),
            'stats' => $stats,
            'exists' => true,
            'writable' => is_writable($directory),
            'free_space' => size_format($stats['free_space']),
            'path' => $directory
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
        
        $directory = sanitize_text_field($_POST['directory']);
        $validation = $this->directory_handler->validate_directory_path($directory);
        
        if (is_wp_error($validation)) {
            wp_send_json_error($validation->get_error_message());
        }
        
        // Create directory if needed
        $result = $this->directory_handler->create_directory($directory);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Scan for templates
        $templates = $this->directory_handler->scan_template_files($directory);
        if (is_wp_error($templates)) {
            wp_send_json_error($templates->get_error_message());
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
            'readable' => is_readable($directory),
            'path' => $directory
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
            'template_dir' => trailingslashit($content_base) . $template_folder,
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