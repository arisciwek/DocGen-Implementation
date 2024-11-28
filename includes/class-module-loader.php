<?php
/**
 * Module Loader
 *
 * @package     DocGen_Implementation
 * @subpackage  Core
 * @version     1.0.1
 * @author      arisciwek
 * 
 * Path: includes/class-module-loader.php
 * 
 * Description: Handles module discovery, registration, and loading.
 *              Provides centralized module management system with hooks
 *              for plugin integration.
 * 
 * Changelog:
 * 1.0.1 - 2024-11-28 07:15:20
 * - Fixed load_modules method
 * - Improved module registration
 * - Added proper Settings Manager integration
 * - Enhanced documentation
 * 
 * 1.0.0 - 2024-11-24
 * - Initial implementation
 * - Module registration and loading
 * 
 * Dependencies:
 * - class-settings-manager.php
 * 
 * Usage:
 * $loader = new DocGen_Implementation_Module_Loader();
 * $loader->register_module('path/to/module.php');
 * 
 * Actions:
 * - docgen_implementation_modules_loaded
 * 
 * Filters:
 * - docgen_implementation_modules
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
     * Sets up action and filter hooks
     */
    public function __construct() {
        // Load modules after plugins loaded, but after DocGen (priority 20)
        add_action('plugins_loaded', array($this, 'load_modules'), 20);
        
        // Filter hook for modules list
        add_filter('docgen_implementation_modules', array($this, 'filter_modules'));
        
        // Auto-discover modules on instantiation
        $this->discover_modules();
    }

    /**
     * Filter hook for modules list
     * Allows other plugins to modify modules list
     * 
     * @param array $modules Current modules list
     * @return array Modified modules list
     */
    public function filter_modules($modules = array()) {
        return $modules;
    }

    /**
     * Load all registered modules
     * Requires module files and triggers registration
     */
    public function load_modules() {
        // Load all registered module files
        foreach ($this->modules as $module_file) {
            require_once $module_file;
        }

        // Signal that modules are loaded
        do_action('docgen_implementation_modules_loaded');

        // Get modules through filter
        $modules = apply_filters('docgen_implementation_modules', array());
        
        // Register modules with Settings Manager
        $settings = DocGen_Implementation_Settings_Manager::get_instance();
        foreach ($modules as $module) {
            $settings->register_module($module);
        }
    }

    /**
     * Register a new module
     * 
     * @param string $module_file Full path to module main file
     * @return bool True on success, false if file doesn't exist
     */
    public function register_module($module_file) {
        if (!file_exists($module_file)) {
            return false;
        }

        $this->modules[] = $module_file;
        return true;
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
     * 
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
     * 
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
     * 
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
     * 
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
