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
 * Description: 
 * Handles settings page functionality including directory configuration
 * and template management. Integrates with Settings Manager for centralized
 * settings handling and coordinates with Directory Handler for file operations.
 * 
 * Dependencies:
 * - class-admin-page.php (parent class)
 * - class-settings-manager.php (for centralized settings)
 * - class-directory-handler.php (for directory operations)
 * - class-directory-migration.php (for migrations)
 * 
 * Components Integration:
 * - Uses Settings Manager for core settings access and updates
 * - Uses Directory Handler for file system operations
 * - Coordinates with Template Handler for template validation
 * - Supports adapter integration for plugin-specific paths
 * 
 * Usage Flow:
 * 1. Settings Manager provides core settings structure
 * 2. Directory Handler validates and manages paths
 * 3. Template Handler manages template operations
 * 4. View renders based on combined settings
 * 
 * @since      1.0.0
 * @changelog
 * 1.0.2 - 2024-11-25
 * - Added directory migration support 
 * - Enhanced settings validation
 * - Improved dependency handling
 * 
 * 1.0.1 - 2024-11-24
 * - Added directory testing
 * - Improved template validation
 * - Added security measures
 * 
 * 1.0.0 - 2024-11-24
 * - Initial implementation
 * - Basic settings management
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
    private $template_handler   ;

    protected $settings_manager;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings_manager = DocGen_Implementation_Settings_Manager::get_instance();
        $this->page_slug = 'docgen-implementation-settings';
        $this->directory_handler = new DocGen_Implementation_Directory_Handler();
        
        // Initialize template handler
        $this->template_handler = new DocGen_Implementation_Directory_Handler();
        $this->template_handler->set_directory_type('Template Directory');

        // Add action handler for directory settings
        add_action('admin_post_docgen_save_directory_settings', array($this, 'handle_directory_settings_save'));

        add_action('wp_ajax_upload_template', array($this, 'ajax_handle_template_upload'));
        add_action('wp_ajax_test_directory', array($this, 'ajax_test_directory'));
        add_action('wp_ajax_test_template_dir', array($this, 'ajax_test_template_dir'));
        add_action('wp_ajax_get_directory_stats', array($this, 'ajax_get_directory_stats'));
            
        parent::__construct();
}

    /**
     * Get page title
     * @return string
     */
    protected function get_page_title() {
        return __('DocGen Implementation Settings', 'docgen-implementation');
    }

    /**
     * Render general settings section
     * Uses separated template file for better organization
     * 
     * @param array $settings Current settings
     */
    private function render_general_settings($settings) {
        // Include general settings template
        require DOCGEN_IMPLEMENTATION_DIR . 'admin/views/general-settings.php';
    }

    /**
     * Render directory settings section
     * @param array $settings Current settings
     * @param bool $is_standalone Whether this is standalone or part of full settings page
     */

    /**
     * Public method untuk render directory settings
     * @param array $data Settings data
     */

    public function render_directory_settings($data) {
        // Check if we have updated settings from URL
        $current_settings = isset($_GET['current_settings']) ? 
            json_decode(urldecode($_GET['current_settings']), true) : null;
            error_log(json_encode($data));
        // Use current settings if available, otherwise use passed settings
        $settings = $current_settings ?? $data['settings'];
        $adapter = $data['adapter'] ?? null;
        
        //$is_standalone = true;
        include DOCGEN_IMPLEMENTATION_DIR . 'admin/views/directory-settings.php';
    }

    /**
     * Render template settings tab
     * @param array $settings Current settings
     */
    private function render_template_settings($settings) {
        // Get template fields
        $fields = apply_filters('docgen_implementation_template_fields', array(
            'name' => array(
                'type' => 'text',
                'label' => __('Template Name', 'docgen-implementation'),
                'required' => true
            ),
            'description' => array(
                'type' => 'textarea',
                'label' => __('Description', 'docgen-implementation')
            )
        ));

        require DOCGEN_IMPLEMENTATION_DIR . 'admin/views/template-settings.php';
    }

    /**
     * Render template list 
     * @param string $template_dir Template directory path
     */
    private function render_template_list($template_dir) {    

        // Change dir_handler to directory_handler
        $templates = $this->directory_handler->scan_template_files($template_dir);
        
        if (empty($templates)) {
            echo '<p>' . esc_html__('No templates found.', 'docgen-implementation') . '</p>';
            return;
        }

        $templates = $this->dir_handler->scan_template_files($template_dir);
        
        if (empty($templates)) {
            echo '<p>' . esc_html__('No templates found.', 'docgen-implementation') . '</p>';
            return;
        }

        echo '<h3>' . esc_html__('Current Templates', 'docgen-implementation') . '</h3>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Template Name', 'docgen-implementation') . '</th>';
        echo '<th>' . esc_html__('Size', 'docgen-implementation') . '</th>';
        echo '<th>' . esc_html__('Modified', 'docgen-implementation') . '</th>';
        echo '<th>' . esc_html__('Status', 'docgen-implementation') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($templates as $template) {
            echo '<tr>';
            echo '<td>' . esc_html($template['name']) . '</td>';
            echo '<td>' . esc_html(size_format($template['size'])) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), $template['modified'])) . '</td>';
            echo '<td>' . ($template['is_valid'] ? 
                '<span class="valid">✓</span>' : 
                '<span class="invalid">✗</span>') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Handle template file upload via AJAX
     */
    public function ajax_handle_template_upload() {
        error_log('DocGen: Starting template upload handler');
        
        check_ajax_referer('docgen_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'docgen-implementation'));
        }

        if (empty($_FILES['template_file'])) {
            wp_send_json_error(__('No file uploaded', 'docgen-implementation'));
        }

        $result = $this->handle_template_upload($_FILES['template_file']);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    /**
     * Handle template file upload
     * @param array $file $_FILES array element
     * @return array|WP_Error Upload result or error
     */
    private function handle_template_upload($file) {
        // Let plugins validate template
        $validators = apply_filters('docgen_implementation_template_validators', array(
            array($this, 'validate_template_size'),
            array($this, 'validate_template_type'),
            array($this, 'validate_template_structure')
        ));

        // Pre-upload hook
        do_action('docgen_implementation_before_template_upload', $file);

        // Run validations
        foreach ($validators as $validator) {
            if (is_callable($validator)) {
                $result = call_user_func($validator, $file);
                if (is_wp_error($result)) {
                    return $result;
                }
            }
        }

        // Get template directory
        $settings = get_option('docgen_implementation_settings', array());
        $template_dir = $settings['template_dir'] ?? '';

        error_log('DocGen Upload: Template directory: ' . $template_dir);
        
        // Validate directory
        $validation = $this->template_handler->validate_directory_path($template_dir);
        if (is_wp_error($validation)) {
            error_log('DocGen Upload: Directory validation failed - ' . $validation->get_error_message());
            return $validation;
        }

        // Create directory if needed
        $dir_result = $this->template_handler->create_directory($template_dir);
        if (is_wp_error($dir_result)) {
            error_log('DocGen Upload: Directory creation failed - ' . $dir_result->get_error_message());
            return $dir_result;
        }

        // Validate file presence
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            error_log('DocGen Upload: No file uploaded');
            return new WP_Error('upload_error', __('No file uploaded', 'docgen-implementation'));
        }

        // Check file extension
        $allowed_types = array('docx', 'odt');
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            error_log('DocGen Upload: Invalid file type - ' . $file_ext);
            return new WP_Error(
                'invalid_type',
                __('Invalid file type. Only DOCX and ODT files are allowed.', 'docgen-implementation')
            );
        }

        // Prepare for file move
        $filename = sanitize_file_name($file['name']);
        $target_path = trailingslashit($template_dir) . $filename;

        // Move file
        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            error_log('DocGen Upload: Failed to move uploaded file');
            return new WP_Error(
                'move_failed',
                __('Failed to save uploaded file', 'docgen-implementation')
            );
        }

        // Validate template structure
        if (!$this->template_handler->validate_template_file($target_path)) {
            error_log('DocGen Upload: Invalid template structure, removing file');
            @unlink($target_path);
            return new WP_Error(
                'invalid_template',
                __('The uploaded file is not a valid template', 'docgen-implementation')
            );
        }

        $result = array(
            'path' => $target_path,
            'name' => $filename,
            'size' => filesize($target_path),
            'type' => $file_ext
        );

        // Post-upload hook
        do_action('docgen_implementation_after_template_upload', $result);

        error_log('DocGen Upload: Upload template completed successfully');
        return $result;
    }


    /**
     * Enqueue page specific assets
     */
    protected function enqueue_page_assets() {
        // Enqueue settings script
        wp_enqueue_script(
            'docgen-settings',
            DOCGEN_IMPLEMENTATION_URL . 'admin/js/docgen-admin-settings.js',
            array('jquery'),
            DOCGEN_IMPLEMENTATION_VERSION,
            true
        );

        // Enqueue directory migration script
        wp_enqueue_script(
            'docgen-directory-migration',
            DOCGEN_IMPLEMENTATION_URL . 'admin/js/docgen-directory-migration.js',
            array('jquery', 'docgen-settings'), // Dependency pada settings.js
            DOCGEN_IMPLEMENTATION_VERSION,
            true
        );

        // Enqueue template upload handler
        wp_enqueue_script(
            'docgen-template-upload',
            DOCGEN_IMPLEMENTATION_URL . 'admin/js/docgen-template-upload.js',
            array('jquery', 'docgen-settings'),
            DOCGEN_IMPLEMENTATION_VERSION,
            true
        );

        // Localize script dengan strings yang digabungkan
        wp_localize_script('docgen-settings', 'docgenSettings', array(
            'nonce' => wp_create_nonce('docgen_admin_nonce'),
            'strings' => array_merge(array(
                // String yang sudah ada
                'testSuccess' => __('Test successful!', 'docgen-implementation'),
                'testFailed' => __('Test failed:', 'docgen-implementation'),
                'selectTemplate' => __('Select template...', 'docgen-implementation'),
                // Strings untuk migrasi
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
            ), array(
                // String baru untuk upload template
                'uploadSuccess' => __('Template uploaded successfully!', 'docgen-implementation'),
                'uploadFailed' => __('Failed to upload template:', 'docgen-implementation'),
                'invalidType' => __('Invalid file type. Only DOCX and ODT files are allowed.', 'docgen-implementation'),
                'serverError' => __('Server error occurred', 'docgen-implementation'),
                'uploadInProgress' => __('Uploading template...', 'docgen-implementation'),
                'refreshing' => __('Refreshing template list...', 'docgen-implementation')
            ))
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

    public function render() {
        $settings = get_option('docgen_implementation_settings', array());
        $submission_result = $this->handle_submissions();
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->get_page_title()) . '</h1>';

        // Display errors if any
        if (is_wp_error($submission_result)) {
            echo '<div class="notice notice-error"><p>' . esc_html($submission_result->get_error_message()) . '</p></div>';
        }
        
        settings_errors('docgen_implementation_settings');

        // Add tabs
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="?page=' . esc_attr($this->page_slug) . '&tab=general" class="nav-tab ' . ($current_tab === 'general' ? 'nav-tab-active' : '') . '">' . esc_html__('General', 'docgen-implementation') . '</a>';
        echo '<a href="?page=' . esc_attr($this->page_slug) . '&tab=templates" class="nav-tab ' . ($current_tab === 'templates' ? 'nav-tab-active' : '') . '">' . esc_html__('Templates', 'docgen-implementation') . '</a>';
        echo '</h2>';

        echo '<form method="post" action="" enctype="multipart/form-data">';
        wp_nonce_field('docgen_implementation_settings', 'docgen_implementation_settings_nonce');

        // Render tab content
        switch ($current_tab) {
            case 'templates':
                $this->render_template_settings($settings);
                break;
            default:
                $this->render_directory_settings($settings);
                $this->render_general_settings($settings);
                break;
        }

        submit_button();
        echo '</form>';
        echo '</div>';
    }

    /**
     * Handle directory settings save
     */
    public function handle_directory_settings_save() {
        // Verify nonce
        if (!check_admin_referer('docgen_directory_settings', 'docgen_directory_nonce')) {
            wp_die(__('Security check failed', 'docgen-implementation'));
        }
        
        // Validate user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'docgen-implementation'));
        }
        
        // Get and validate settings
        $settings = $this->validate_and_save_settings($_POST);
        
        if (is_wp_error($settings)) {
            add_settings_error(...);
        } else {
            // Refresh settings dari database
            $settings = get_option('docgen_implementation_settings', array());
            
            // Update settings di class
            $this->settings = $settings;
            
            add_settings_error(...);
        }
        
        // Redirect dengan settings yang sudah diupdate
        $redirect_url = add_query_arg(array(
            'page' => $this->page_slug,
            'settings-updated' => 'true',
            'current_settings' => urlencode(json_encode($settings)) // Tambahkan current settings ke URL
        ), admin_url('admin.php'));
        
        wp_redirect($redirect_url);
        exit;
    }
}
