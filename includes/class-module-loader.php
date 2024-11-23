<?php
/**
 * Module Loader
 *
 * @package     DocGen_Implementation
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: includes/class-module-loader.php
 * 
 * Changelog:
 * 1.0.0 - 2024-11-24
 * - Initial implementation
 * - Module registration and loading
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

class DocGen_Implementation_Module_Loader {
    /**
     * Registered modules
     * @var array
     */
    private $modules = array();

    /**
     * Constructor
     */

    public function __construct() {
	    add_action('plugins_loaded', array($this, 'load_modules'), 20);
	    add_filter('docgen_implementation_modules', array($this, 'get_modules'));
	    
	    // Auto-discover modules
	    $this->discover_modules();
	}

    /**
     * Register module
     * @param string $module_file Full path to module main file
     * @return bool True on success
     */
    public function register_module($module_file) {
        if (!file_exists($module_file)) {
            return false;
        }

        $this->modules[] = $module_file;
        return true;
    }

    /**
     * Load all registered modules
     */
    public function load_modules() {
        foreach ($this->modules as $module_file) {
            require_once $module_file;
        }

        do_action('docgen_implementation_modules_loaded');
    }

    /**
     * Get registered modules info
     * @param array $modules Current modules
     * @return array Updated modules list
     */
    public function get_modules($modules = array()) {
        return $modules;
    }

    /**
     * Auto-discover modules in modules directory
     */
    public function discover_modules() {
        $modules_dir = DOCGEN_IMPLEMENTATION_DIR . 'modules';
        
        if (!is_dir($modules_dir)) {
            return;
        }

        // Scan modules directory
        $dirs = scandir($modules_dir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $module_dir = $modules_dir . '/' . $dir;
            if (!is_dir($module_dir)) {
                continue;
            }

            // Look for main module file
            $module_file = $module_dir . '/class-module.php';
            if (file_exists($module_file)) {
                $this->register_module($module_file);
            }
        }
    }

    /**
     * Get module instance by slug
     * @param string $slug Module slug
     * @return object|null Module instance or null if not found
     */
    public function get_module($slug) {
        $modules = apply_filters('docgen_implementation_modules', array());
        foreach ($modules as $module) {
            if (isset($module['slug']) && $module['slug'] === $slug) {
                return $module['instance'] ?? null;
            }
        }
        return null;
    }

    /**
     * Check if module exists
     * @param string $slug Module slug
     * @return bool True if module exists
     */
    public function module_exists($slug) {
        return !is_null($this->get_module($slug));
    }

    /**
     * Get active modules
     * @return array List of active modules
     */
    public function get_active_modules() {
        $active = get_option('docgen_implementation_active_modules', array());
        $modules = apply_filters('docgen_implementation_modules', array());
        
        return array_filter($modules, function($module) use ($active) {
            return isset($module['slug']) && in_array($module['slug'], $active);
        });
    }

    /**
     * Activate module
     * @param string $slug Module slug
     * @return bool True on success
     */
    public function activate_module($slug) {
        if (!$this->module_exists($slug)) {
            return false;
        }

        $active = get_option('docgen_implementation_active_modules', array());
        if (!in_array($slug, $active)) {
            $active[] = $slug;
            update_option('docgen_implementation_active_modules', $active);
        }

        return true;
    }

    /**
     * Deactivate module
     * @param string $slug Module slug
     * @return bool True on success
     */
    public function deactivate_module($slug) {
        $active = get_option('docgen_implementation_active_modules', array());
        if (($key = array_search($slug, $active)) !== false) {
            unset($active[$key]);
            update_option('docgen_implementation_active_modules', array_values($active));
            return true;
        }
        return false;
    }
}