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
 * Autoloader untuk class-class plugin
 */
spl_autoload_register(function($class) {
    // Base namespace untuk plugin
    $namespace = 'DocGen_Implementation_';
    
    // Check if class uses our namespace
    if (strpos($class, $namespace) !== 0) {
        return;
    }

    // Remove namespace from class name
    $class_name = str_replace($namespace, '', $class);
    
    // Convert class name to filename
    $filename = 'class-' . strtolower(
        str_replace('_', '-', $class_name)
    ) . '.php';

    // Different paths for different types of classes
    $possible_paths = array(
        DOCGEN_IMPLEMENTATION_DIR . 'admin/' . $filename,
        DOCGEN_IMPLEMENTATION_DIR . 'includes/' . $filename,
        DOCGEN_IMPLEMENTATION_DIR . 'modules/' . $filename
    );

    // Try to load the file
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
});

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

    // Initialize module loader
    $module_loader = new DocGen_Implementation_Module_Loader();
    $module_loader->discover_modules();

    // Initialize admin menu - will handle loading of other admin classes
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
 * Plugin activation
 */
function docgen_implementation_activate() {
    // Create required directories
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/docgen-temp';
    
    require_once DOCGEN_IMPLEMENTATION_DIR . 'admin/class-directory-handler.php';
    $directory_handler = new DocGen_Implementation_Directory_Handler();
    
    // Create secure temp directory
    $result = $directory_handler->create_secure_directory(
        'docgen-temp',
        $upload_dir['basedir']
    );
    
    if (is_wp_error($result)) {
        wp_die($result->get_error_message());
    }

    // Set default settings
    $default_settings = array(
        'temp_dir' => $temp_dir,
        'template_dir' => trailingslashit(WP_CONTENT_DIR) . 'docgen-templates',
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
    // Get settings
    $settings = get_option('docgen_implementation_settings');
    
    // Clean up temp directory if exists
    if (!empty($settings['temp_dir']) && file_exists($settings['temp_dir'])) {
        require_once DOCGEN_IMPLEMENTATION_DIR . 'admin/class-directory-handler.php';
        $directory_handler = new DocGen_Implementation_Directory_Handler();
        $directory_handler->cleanup_temp_files();
    }

    // Clear scheduled hooks
    wp_clear_scheduled_hook('docgen_implementation_cleanup_temp');

    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'docgen_implementation_deactivate');
