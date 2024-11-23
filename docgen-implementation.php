<?php
/**
 * DocGen Implementation
 *
 * @package     DocGen_Implementation
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Plugin Name: DocGen Implementation
 * Plugin URI: http://example.com/docgen-implementation
 * Description: Implementation of WP DocGen for generating various documents
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: arisciwek
 * Author URI: http://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: docgen-implementation
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

// Plugin version
define('DOCGEN_IMPLEMENTATION_VERSION', '1.0.0');

// Plugin paths
define('DOCGEN_IMPLEMENTATION_FILE', __FILE__);
define('DOCGEN_IMPLEMENTATION_DIR', plugin_dir_path(__FILE__));
define('DOCGEN_IMPLEMENTATION_URL', plugin_dir_url(__FILE__));
define('DOCGEN_IMPLEMENTATION_BASENAME', plugin_basename(__FILE__));

/**
 * Check WP DocGen dependency
 */
function docgen_implementation_check_dependencies() {
    if (!class_exists('WP_DocGen')) {
        add_action('admin_notices', function() {
            $message = sprintf(
                /* translators: %s: Plugin name */
                __('%s requires WP DocGen plugin to be installed and activated.', 'docgen-implementation'),
                '<strong>DocGen Implementation</strong>'
            );
            echo '<div class="notice notice-error"><p>' . wp_kses_post($message) . '</p></div>';
        });
        return false;
    }
    return true;
}

/**
 * Initialize plugin
 */
function docgen_implementation_init() {
    // Check dependencies
    if (!docgen_implementation_check_dependencies()) {
        return;
    }

    // Load required files
    require_once DOCGEN_IMPLEMENTATION_DIR . 'includes/class-module-loader.php';
    require_once DOCGEN_IMPLEMENTATION_DIR . 'admin/class-admin-menu.php';

    // Initialize module loader
    $module_loader = new DocGen_Implementation_Module_Loader();
    $module_loader->discover_modules();

    // Initialize admin menu
    DocGen_Implementation_Admin_Menu::get_instance();

    // Load text domain
    load_plugin_textdomain(
        'docgen-implementation',
        false,
        dirname(DOCGEN_IMPLEMENTATION_BASENAME) . '/languages'
    );

    do_action('docgen_implementation_loaded');
}
add_action('plugins_loaded', 'docgen_implementation_init');

/**
 * Create secure temporary directory
 * @param string $dir_path Full path to directory
 * @return bool True on success
 */
function docgen_implementation_create_secure_directory($dir_path) {
    if (!file_exists($dir_path)) {
        if (!wp_mkdir_p($dir_path)) {
            return false;
        }
        
        // Create .htaccess
        $htaccess = $dir_path . '/.htaccess';
        if (!file_exists($htaccess)) {
            $rules = "deny from all\n";
            if (!@file_put_contents($htaccess, $rules)) {
                return false;
            }
        }

        // Create empty index.php
        $index = $dir_path . '/index.php';
        if (!file_exists($index)) {
            if (!@file_put_contents($index, "<?php\n// Silence is golden.")) {
                return false;
            }
        }
    }
    
    return true;
}

/**
 * Plugin activation
 */
function docgen_implementation_activate() {
    // Create required directories
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/docgen-impl-temp';
    
    // Create secure directory
    if (!docgen_implementation_create_secure_directory($temp_dir)) {
        wp_die(
            __('Could not create temporary directory. Please check directory permissions.', 'docgen-implementation'),
            __('Plugin Activation Failed', 'docgen-implementation'),
            array('back_link' => true)
        );
    }

    // Set default settings
    $default_settings = array(
        'temp_dir' => $temp_dir,
        'output_format' => 'docx',
        'debug_mode' => false
    );
    
    if (!get_option('docgen_implementation_settings')) {
        add_option('docgen_implementation_settings', $default_settings);
    }

    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'docgen_implementation_activate');

/**
 * Plugin deactivation
 */
function docgen_implementation_deactivate() {
    // Optional: Clean up temp files
    $settings = get_option('docgen_implementation_settings');
    if (isset($settings['temp_dir']) && file_exists($settings['temp_dir'])) {
        array_map('unlink', glob($settings['temp_dir'] . '/*.*'));
    }

    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'docgen_implementation_deactivate');
