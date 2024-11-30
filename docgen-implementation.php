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

function docgen_implementation_init() {
    // Check dependencies
    if (!docgen_implementation_check_dependencies()) {
        return;
    }

    // Load core files
    require_once DOCGEN_IMPLEMENTATION_DIR . 'includes/class-docgen-adapter.php';
    require_once DOCGEN_IMPLEMENTATION_DIR . 'includes/class-docgen-module.php'; 
    require_once DOCGEN_IMPLEMENTATION_DIR . 'includes/class-settings-manager.php';
    require_once DOCGEN_IMPLEMENTATION_DIR . 'includes/class-module-loader.php';

    // Initialize core components
    $settings = DocGen_Implementation_Settings_Manager::get_instance();
    $module_loader = new DocGen_Implementation_Module_Loader();
    $module_loader->discover_modules();

    // Initialize admin menu
    DocGen_Implementation_Admin_Menu::get_instance();

    do_action('docgen_implementation_loaded');
}


add_action('plugins_loaded', 'docgen_implementation_init');

/**
 * Load plugin text domain
 */
function docgen_implementation_load_textdomain() {
    load_plugin_textdomain(
        'docgen-implementation',
        false,
        dirname(DOCGEN_IMPLEMENTATION_BASENAME) . '/languages'
    );
}
add_action('init', 'docgen_implementation_load_textdomain');


/**
 * Plugin activation
 */
function docgen_implementation_activate() {
    // Get upload directory
    $upload_dir = wp_upload_dir();
    $upload_base = $upload_dir['basedir'];
    
    require_once DOCGEN_IMPLEMENTATION_DIR . 'admin/class-directory-handler.php';
    $directory_handler = new DocGen_Implementation_Directory_Handler();
    
    // Settings Manager akan handle initialization
    DocGen_Implementation_Settings_Manager::get_instance();
    
    // Update/create settings
    if (!get_option('docgen_implementation_settings')) {
        add_option('docgen_implementation_settings', $default_settings);
    } else {
        update_option('docgen_implementation_settings', $default_settings);
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
        
        // Gunakan method clean_directory untuk membersihkan file
        $cleanup_result = $directory_handler->clean_directory($settings['temp_dir'], array(
            'older_than' => 0, // hapus semua file
            'keep_latest' => 0, // tidak perlu menyimpan file
            'recursive' => true // hapus semua subfolder
        ));
        
        if (is_wp_error($cleanup_result)) {
            error_log('Error cleaning temp directory: ' . $cleanup_result->get_error_message());
        } else {
            error_log('Temp directory cleaned: ' . print_r($cleanup_result, true));
        }
    }

    // Clear scheduled hooks
    wp_clear_scheduled_hook('docgen_implementation_cleanup_temp');


    $settings = DocGen_Implementation_Settings_Manager::get_instance()->get_core_settings();
    
    // Check if clean uninstall enabled
    if (!empty($settings['clean_uninstall'])) {
        // Delete options
        delete_option('docgen_implementation_settings');
        
        // Delete directories
        $directory_handler = new DocGen_Implementation_Directory_Handler();
        $directory_handler->cleanup_all();
        
        // Remove any custom capabilities
        $roles = array('administrator', 'editor');
        foreach ($roles as $role) {
            $role_obj = get_role($role);
            if ($role_obj) {
                $role_obj->remove_cap('manage_docgen');
            }
        }
    }

    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'docgen_implementation_deactivate');
