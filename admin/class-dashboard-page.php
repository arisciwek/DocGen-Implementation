<?php
/**
 * Dashboard Page Handler
 *
 * @package     DocGen_Implementation
 * @subpackage  Admin
 * @version     1.0.1
 * @author      arisciwek
 * 
 * Path: admin/class-dashboard-page.php
 * 
 * Description: Handles dashboard page rendering and functionality.
 *              Menampilkan overview dari plugin status, modules,
 *              dan system information.
 * 
 * Changelog:
 * 1.0.1 - 2024-11-24
 * - Fixed asset loading using parent class method
 * - Implemented proper enqueue_page_assets method
 * 
 * 1.0.0 - Initial implementation
 * - Dashboard layout with cards
 * - Module listing
 * - System information display
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

class DocGen_Implementation_Dashboard_Page extends DocGen_Implementation_Admin_Page {
    /**
     * Constructor
     */
    public function __construct() {
        // Set page slug before parent constructor
        $this->page_slug = 'docgen-implementation';
        
        // Call parent constructor
        parent::__construct();
    }

    /**
     * Get page title
     * @return string
     */
    protected function get_page_title() {
        return __('DocGen Implementation Dashboard', 'docgen-implementation');
    }

    /**
     * Enqueue page specific assets
     */
    protected function enqueue_page_assets() {
        wp_enqueue_style(
            'docgen-dashboard',
            DOCGEN_IMPLEMENTATION_URL . 'assets/css/docgen-dashboard.css',
            array(),
            DOCGEN_IMPLEMENTATION_VERSION
        );
    }

    /**
     * Handle form submissions
     * @return bool|WP_Error
     */
    protected function handle_submissions() {
        return true; // Dashboard doesn't handle submissions
    }

    /**
     * Render dashboard page
     */
    public function render() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->get_page_title()) . '</h1>';

        // Add pre-content hook to customize
        do_action('docgen_implementation_before_dashboard_content');
        
        // Get modules and system info first
        $modules = $this->get_modules();
        $system_info = $this->get_system_info();
        
        // Get filtered cards
        $cards = apply_filters('docgen_implementation_dashboard_cards', array(
            'modules' => array(
                'callback' => array($this, 'render_modules_card'),
                'data' => $modules
            ),
            'system_info' => array(
                'callback' => array($this, 'render_system_info_card'), 
                'data' => $system_info
            )
        ));

        // Render each card
        foreach ($cards as $card) {
            if (isset($card['callback']) && is_callable($card['callback'])) {
                // Pass only the data to the callback
                call_user_func($card['callback'], $card['data']);
            }
        }

        // Add post-content hook to customize
        do_action('docgen_implementation_after_dashboard_content');
        
        echo '</div>';
    }

    /**
     * Get available modules
     * @return array
     */
    private function get_modules() {
        return apply_filters('docgen_implementation_modules', array());
    }

    /**
     * Get system information
     * @return array
     */
    private function get_system_info() {
        $settings = get_option('docgen_implementation_settings', array());
        
        return array(
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'docgen_version' => defined('WP_DOCGEN_VERSION') ? WP_DOCGEN_VERSION : __('Not installed', 'docgen-implementation'),
            'implementation_version' => DOCGEN_IMPLEMENTATION_VERSION,
            'temp_dir' => isset($settings['temp_dir']) ? $settings['temp_dir'] : __('Not set', 'docgen-implementation'),
            'template_dir' => isset($settings['template_dir']) ? $settings['template_dir'] : __('Not set', 'docgen-implementation')
        );
    }

    /**
     * Render modules card
     * @param array $modules List of modules
     */
    private function render_modules_card($modules) {
        echo '<div class="card">';
        echo '<h2>' . esc_html__('Available Modules', 'docgen-implementation') . '</h2>';
        
        if (!empty($modules)) {
            echo '<table class="wp-list-table widefixed striped">';
            echo '<thead><tr>';
            echo '<th>' . esc_html__('Module', 'docgen-implementation') . '</th>';
            echo '<th>' . esc_html__('Description', 'docgen-implementation') . '</th>';
            echo '<th>' . esc_html__('Version', 'docgen-implementation') . '</th>';
            echo '</tr></thead><tbody>';
            
            foreach ($modules as $module) {
                echo '<tr>';
                echo '<td><strong>' . esc_html($module['name']) . '</strong></td>';
                echo '<td>' . esc_html($module['description']) . '</td>';
                echo '<td>' . esc_html($module['version']) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . esc_html__('No modules found.', 'docgen-implementation') . '</p>';
        }
        
        echo '</div>';
    }

    /**
     * Render system information card
     * @param array $info System information
     */
    private function render_system_info_card($info) {
        echo '<div class="card">';
        echo '<h2>' . esc_html__('System Information', 'docgen-implementation') . '</h2>';
        
        echo '<table class="widefat striped">';
        echo '<tbody>';
        
        $labels = array(
            'php_version' => __('PHP Version', 'docgen-implementation'),
            'wp_version' => __('WordPress Version', 'docgen-implementation'),
            'docgen_version' => __('WP DocGen Version', 'docgen-implementation'),
            'implementation_version' => __('DocGen Implementation Version', 'docgen-implementation'),
            'temp_dir' => __('Temporary Directory', 'docgen-implementation'),
            'template_dir' => __('Template Directory', 'docgen-implementation')
        );
        
        foreach ($info as $key => $value) {
            echo '<tr>';
            echo '<th>' . esc_html($labels[$key]) . '</th>';
            echo '<td>' . esc_html($value) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }

}
