<?php
/**
 * DocGen Adapter Class
 *
 * @package     DocGen_Implementation
 * @subpackage  Core
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: includes/class-docgen-adapter.php
 * 
 * Description: Abstract adapter class untuk integrasi plugin dengan DocGen framework.
 *              Menyediakan interface standar untuk mapping settings dan module
 *              dari plugin host ke format yang dimengerti DocGen Implementation.
 * 
 * Changelog:
 * 1.0.0 - 2024-11-29 11:00:00
 * - Initial release
 * - Added plugin info getter
 * - Added settings mapper
 * - Added modules mapper
 * - Added registration hooks
 * 
 * Dependencies:
 * - class-settings-manager.php (for settings management)
 * - class-module-loader.php (for module registration)
 * 
 * Plugin Info Structure:
 * {
 *   slug: string,         // Unique plugin identifier
 *   name: string,         // Display name
 *   version: string,      // Plugin version
 *   author: string,       // Plugin author
 *   description: string,  // Plugin description
 *   settings?: {         // Optional default settings
 *     setting_key: value
 *   }
 * }
 * 
 * Usage Example:
 * class My_Plugin_DocGen_Adapter extends DocGen_Adapter {
 *   protected function get_plugin_info() {
 *     return [
 *       'slug' => 'my-plugin',
 *       'name' => 'My Plugin',
 *       'version' => '1.0.0',
 *       'author' => 'Developer Name',
 *       'description' => 'Plugin Description'
 *     ];
 *   }
 *   
 *   protected function map_settings($settings) {
 *     return [
 *       'temp_dir' => $settings['temp_directory'],
 *       'template_dir' => $settings['template_directory']
 *     ];
 *   }
 *   
 *   protected function map_modules($modules) {
 *     return array_map(function($module) {
 *       return [
 *         'slug' => $module->get_slug(),
 *         'name' => $module->get_name()
 *       ];
 *     }, $modules);
 *   }
 * }
 * 
 * Actions:
 * - docgen_implementation_adapter_initialized
 * - docgen_implementation_before_map_settings
 * - docgen_implementation_after_map_settings
 * 
 * Filters:
 * - docgen_implementation_plugin_info_{$slug}
 * - docgen_implementation_mapped_settings_{$slug}
 * - docgen_implementation_mapped_modules_{$slug}
 * 
 * Required Methods in Child Classes:
 * - get_plugin_info()  : Get plugin information
 * - map_settings()     : Map plugin settings to DocGen format
 * - map_modules()      : Map plugin modules to DocGen format
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

abstract class DocGen_Adapter {
    /**
     * Settings manager instance
     * @var DocGen_Implementation_Settings_Manager
     */
    protected $settings_manager;

    /**
     * Plugin information
     * @var array
     */
    protected $plugin_info;

    /**
     * Get docgen dir
     * @return string DocGen Implementation Directory
     */
    protected function get_docgen_dir() {
        return DOCGEN_IMPLEMENTATION_DIR;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings_manager = DocGen_Implementation_Settings_Manager::get_instance();
        $this->plugin_info = $this->get_plugin_info();
        
        $this->register_hooks();
    }

    /**
     * Register adapter hooks
     */
    protected function register_hooks() {
        add_action('docgen_implementation_loaded', array($this, 'init'));
        add_filter('docgen_implementation_modules', array($this, 'register_modules'));
    }

    /**
     * Initialize adapter
     */
    public function init() {
        // Register plugin with DocGen
        $this->settings_manager->register_plugin($this->plugin_info);

        // Map and update settings
        $settings = $this->map_settings($this->get_plugin_settings());
        $this->settings_manager->update_plugin_settings($this->plugin_info['slug'], $settings);

        do_action('docgen_implementation_adapter_initialized', $this->plugin_info['slug']);
    }

    /**
     * Register modules with DocGen
     * @param array $modules Current modules list
     * @return array Updated modules list
     */
    public function register_modules($modules) {
        return array_merge($modules, $this->map_modules($this->get_plugin_modules()));
    }

    /**
     * Get plugin settings
     * @return array Plugin settings
     */
    protected function get_plugin_settings() {
        // Override in child class if needed
        return array();
    }

    /**
     * Get plugin modules
     * @return array Plugin modules
     */
    protected function get_plugin_modules() {
        // Override in child class if needed
        return array();
    }

    /**
     * Get plugin information
     * @return array Plugin information
     */
    abstract protected function get_plugin_info();

    /**
     * Map plugin settings to DocGen format
     * @param array $settings Plugin settings
     * @return array Mapped settings
     */
    abstract protected function map_settings($settings);

    /**
     * Map plugin modules to DocGen format
     * @param array $modules Plugin modules
     * @return array Mapped modules
     */
    abstract protected function map_modules($modules);
}