<?php
/**
 * Base Admin Page Handler
 *
 * @package     DocGen_Implementation
 * @subpackage  Admin
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: admin/class-admin-page.php
 * 
 * Description: Abstract base class untuk semua admin pages.
 *              Menyediakan struktur dasar dan helper methods yang dibutuhkan
 *              oleh semua admin page implementations.
 * 
 * Changelog:
 * 1.0.0 - Initial abstract base class
 * - Abstract base class untuk admin pages
 * - Common utility methods
 * - Asset handling
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

abstract class DocGen_Implementation_Admin_Page {
    /**
     * Page slug
     * @var string
     */
    protected $page_slug;

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Enqueue page specific assets
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, $this->page_slug) === false) {
            return;
        }

        $this->enqueue_page_assets();
    }

    /**
     * Enqueue page specific CSS/JS
     * To be implemented by child classes
     */
    protected function enqueue_page_assets() {
        // Child classes should implement this
    }

    /**
     * Get upload base directory
     * @return string
     */
    protected function get_upload_base_dir() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'];
    }

    /**
     * Get content base directory
     * @return string
     */
    protected function get_content_base_dir() {
        return trailingslashit(WP_CONTENT_DIR);
    }

    /**
     * Validate directory name
     * @param string $dir_name Directory name to validate
     * @return string|WP_Error Sanitized directory name or error
     */
    protected function validate_directory_name($dir_name) {
        $dir_name = sanitize_file_name($dir_name);
        $dir_name = basename($dir_name);
        
        if (empty($dir_name) || strpos($dir_name, '..') !== false) {
            return new WP_Error(
                'invalid_directory',
                __('Invalid directory name', 'docgen-implementation')
            );
        }
        
        return $dir_name;
    }

    /**
     * Render page content
     * Must be implemented by child classes
     */
    abstract public function render();

    /**
     * Get page title
     * @return string Page title
     */
    abstract protected function get_page_title();

    /**
     * Handle form submissions
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    abstract protected function handle_submissions();
}