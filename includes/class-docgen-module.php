<?php
/**
 * Base Module Class
 *
 * @package     DocGen_Implementation
 * @subpackage  Core
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: includes/class-docgen-module.php
 * 
 * Description: Base class untuk semua DocGen modules.
 *              Menyediakan struktur standar dan fungsionalitas dasar
 *              yang dibutuhkan setiap modul. Menghandle registrasi,
 *              settings, menu, dan assets management.
 * 
 * Changelog:
 * 1.0.0 - 2024-11-29 10:00:00
 * - Initial release
 * - Added module registration system
 * - Added settings management integration
 * - Added menu registration
 * - Added assets management
 * - Added abstract methods for standardization
 * 
 * Dependencies:
 * - class-settings-manager.php (for settings management)
 * - class-admin-menu.php (for menu registration)
 * - WordPress admin functions (add_submenu_page, register_setting)
 * 
 * Module Structure Example:
 * {
 *   slug: string,          // Unique module identifier
 *   name: string,          // Display name
 *   description: string,   // Module description
 *   version: string,       // Module version
 *   menu_title: string,    // Admin menu title
 *   settings: {           // Module specific settings
 *     option_name: value
 *   }
 * }
 * 
 * Usage Example:
 * class My_Custom_Module extends DocGen_Module {
 *   public function __construct() {
 *     $module_info = array(
 *       'slug' => 'my-module',
 *       'name' => 'My Module',
 *       'description' => 'Custom module description',
 *       'version' => '1.0.0',
 *       'menu_title' => 'My Module'
 *     );
 *     parent::__construct($module_info);
 *   }
 *   
 *   public function render_page() {
 *     // Implement page rendering
 *   }
 *   
 *   public function render_settings_section() {
 *     // Implement settings UI
 *   }
 *   
 *   protected function get_provider_class() {
 *     return 'My_Module_Provider';
 *   }
 * }
 * 
 * Actions:
 * - docgen_implementation_module_registered
 * - docgen_implementation_before_render_module_{$slug}
 * - docgen_implementation_after_render_module_{$slug}
 * 
 * Filters:
 * - docgen_implementation_module_settings_{$slug}
 * - docgen_implementation_module_menu_capability_{$slug}
 * - docgen_implementation_module_assets_{$slug}
 * 
 * Required Methods in Child Classes:
 * - render_page()             : Render module admin page
 * - render_settings_section() : Render module settings UI
 * - get_provider_class()      : Get document provider class
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

abstract class DocGen_Module {
    /**
     * Module info
     * @var array
     */
    protected $module_info;

    /**
     * Settings manager instance
     * @var DocGen_Implementation_Settings_Manager
     */
    protected $settings;

    /**
     * Constructor
     * @param array $module_info Module information
     */
    protected function __construct($module_info) {
        $this->module_info = $module_info;
        $this->settings = DocGen_Implementation_Settings_Manager::get_instance();
        
        $this->register_hooks();
    }

    /**
     * Register module hooks
     */
    protected function register_hooks() {
        // Module registration
        add_filter('docgen_implementation_modules', array($this, 'register_module'));
        
        // Menu & Settings
        add_action('docgen_implementation_register_admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Register module with system
     * @param array $modules Current modules
     * @return array Updated modules
     */
    public function register_module($modules) {
        $modules[] = array(
            'slug' => $this->module_info['slug'],
            'name' => $this->module_info['name'],
            'description' => $this->module_info['description'],
            'version' => $this->module_info['version'],
            'instance' => $this
        );
        return $modules;
    }

    /**
     * Register module menu item
     */
    public function register_menu() {
        $parent_slug = DocGen_Implementation_Admin_Menu::get_instance()->get_parent_slug();
        
        add_submenu_page(
            $parent_slug,
            $this->module_info['menu_title'],
            $this->module_info['menu_title'],
            'manage_options',
            "docgen-{$this->module_info['slug']}",
            array($this, 'render_page')
        );
    }

    /**
     * Register module settings
     */
    public function register_settings() {
        register_setting(
            "docgen_{$this->module_info['slug']}_settings",
            "docgen_{$this->module_info['slug']}_options",
            array($this, 'validate_settings')
        );

        add_settings_section(
            "docgen_{$this->module_info['slug']}_section",
            $this->module_info['name'] . ' ' . __('Settings', 'docgen-implementation'),
            array($this, 'render_settings_section'),
            "docgen_{$this->module_info['slug']}"
        );

        $this->register_settings_fields();
    }

    /**
     * Get module settings
     * @return array Module settings
     */
    public function get_settings() {
        return $this->settings->get_module_settings($this->module_info['slug']);
    }

    /**
     * Validate module settings
     * @param array $input Settings input
     * @return array Validated settings
     */
    public function validate_settings($input) {
        // Override in child class for validation
        return $input;
    }

    /**
     * Register module specific settings fields
     */
    protected function register_settings_fields() {
        // Override in child class to add specific settings fields
    }

    /**
     * Render module page
     */
    abstract public function render_page();

    /**
     * Render settings section
     */
    abstract public function render_settings_section();

    /**
     * Get provider class for this module
     * @return string|array Provider class name(s)
     */
    abstract protected function get_provider_class();
}
