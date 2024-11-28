<?php
/**
 * Settings Manager Class
 *
 * @package     DocGen_Implementation
 * @subpackage  Core
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: includes/class-settings-manager.php 
 * 
 * Description: Centralized settings management untuk DocGen Implementation.
 *              Handles inisialisasi, update, dan akses settings.
 *              Menyediakan interface untuk plugin registration dan
 *              management plugin-specific settings.
 * 
 * Changelog:
 * 1.0.0 - 2024-11-28 15:45:25
 * - Initial release
 * - Added core settings management
 * - Added plugin registration system
 * - Added settings access methods
 * - Added settings update validation
 * 
 * Dependencies:
 * - WordPress core functions (get_option, update_option)
 * - wp_upload_dir() for directory paths
 * 
 * Option Structure:
 * docgen_implementation_settings = {
 *   core: {
 *     temp_dir: string,
 *     template_dir: string,
 *     output_format: string
 *   },
 *   plugins: {
 *     [plugin_slug]: {
 *       name: string,
 *       temp_subdir: string,
 *       modules: string[],
 *       settings: object
 *     }
 *   }
 * }
 * 
 * Usage Example:
 * $settings = DocGen_Implementation_Settings_Manager::get_instance();
 * 
 * // Get core settings
 * $core_settings = $settings->get_core_settings();
 * 
 * // Register plugin
 * $settings->register_plugin([
 *   'slug' => 'my-plugin',
 *   'name' => 'My Plugin',
 *   'modules' => ['module1']
 * ]);
 * 
 * // Get plugin settings
 * $plugin_settings = $settings->get_plugin_settings('my-plugin');
 * 
 * Actions:
 * - docgen_implementation_before_save_settings
 * - docgen_implementation_after_save_settings
 * - docgen_implementation_plugin_registered
 * 
 * Filters:
 * - docgen_implementation_validate_core_settings
 * - docgen_implementation_validate_plugin_settings
 * - docgen_implementation_{$plugin_slug}_settings
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

class DocGen_Implementation_Settings_Manager {
    /**
     * Instance of Settings Manager
     * @var self|null
     */
    private static $instance = null;

    /**
     * Default settings structure
     * @var array
     */
    private $default_settings;
    

    private $modules = array();
    
    
    /**
     * Constructor
     * Initialize default settings structure
     */
    private function __construct() {
        $upload_dir = wp_upload_dir();
        $upload_base = $upload_dir['basedir'];

        $this->default_settings = array(
            'core' => array(
                'temp_dir' => trailingslashit($upload_base) . 'docgen-temp',
                'template_dir' => trailingslashit($upload_base) . 'docgen-templates',
                'output_format' => 'docx',
                'clean_uninstall' => false
            ),
            'plugins' => array(),
            'modules' => array()
        );

        $this->maybe_init_settings();
    }

    /**
     * Get Settings Manager instance
     * @return self Settings Manager instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize settings if not exists
     */
    private function maybe_init_settings() {
        if (!get_option('docgen_implementation_settings')) {
            update_option('docgen_implementation_settings', $this->default_settings);
        }
    }

    /**
     * Get core settings
     * @return array Core settings
     */
    public function get_core_settings() {
        $settings = get_option('docgen_implementation_settings');
        return $settings['core'] ?? array();
    }

    /**
     * Update core settings with validation
     * @param array $new_settings New core settings
     * @return bool True if updated, false otherwise
     */
    public function update_core_settings($new_settings) {
        // Allow validation via filter
        $validated_settings = apply_filters(
            'docgen_implementation_validate_core_settings', 
            $new_settings
        );

        if (is_wp_error($validated_settings)) {
            return false;
        }

        // Action before save
        do_action('docgen_implementation_before_save_settings', $validated_settings);

        // Update settings
        $settings = get_option('docgen_implementation_settings');
        $settings['core'] = $validated_settings;
        $updated = update_option('docgen_implementation_settings', $settings);

        if ($updated) {
            do_action('docgen_implementation_after_save_settings', $settings);
        }

        return $updated;
    }

    /**
     * Register new plugin
     * @param array $plugin_data Plugin registration data
     * @return bool True if registered, false otherwise
     */
    public function register_plugin($plugin_data) {
        if (empty($plugin_data['slug'])) {
            return false;
        }

        // Validate plugin data
        $validated_data = apply_filters(
            'docgen_implementation_validate_plugin_settings',
            $plugin_data
        );

        if (is_wp_error($validated_data)) {
            return false;
        }

        // Register plugin
        $settings = get_option('docgen_implementation_settings');
        $settings['plugins'][$plugin_data['slug']] = array(
            'name' => $plugin_data['name'],
            'temp_subdir' => $plugin_data['slug'],
            'modules' => $plugin_data['modules'] ?? array(),
            'settings' => $plugin_data['settings'] ?? array()
        );

        $updated = update_option('docgen_implementation_settings', $settings);

        if ($updated) {
            do_action('docgen_implementation_plugin_registered', $plugin_data['slug']);
        }

        return $updated;
    }


    /**
     * Register a module
     * @param array $module Module data
     * @return bool Success status
     */
    public function register_module($module) {
        if (empty($module['slug'])) {
            return false;
        }

        $settings = get_option('docgen_implementation_settings');
        
        if (!isset($settings['modules'])) {
            $settings['modules'] = array();
        }

        $settings['modules'][$module['slug']] = array(
            'name' => $module['name'],
            'description' => $module['description'],
            'version' => $module['version'],
            'instance' => $module['instance']
        );

        $this->modules[$module['slug']] = $module;

        return update_option('docgen_implementation_settings', $settings);
    }

    /**
     * Get all registered modules
     * @return array List of modules
     */
    public function get_modules() {
        return $this->modules;
    }

    /**
     * Get module by slug
     * @param string $slug Module slug
     * @return array|null Module data or null if not found
     */
    public function get_module($slug) {
        return $this->modules[$slug] ?? null;
    }

    /**
     * Get plugin settings
     * @param string $plugin_slug Plugin identifier
     * @return array Plugin settings or empty array if not found
     */
    public function get_plugin_settings($plugin_slug) {
        $settings = get_option('docgen_implementation_settings');
        $plugin_settings = $settings['plugins'][$plugin_slug] ?? array();

        // Allow plugin to modify its settings
        return apply_filters(
            "docgen_implementation_{$plugin_slug}_settings",
            $plugin_settings
        );
    }

    /**
     * Update plugin settings
     * @param string $plugin_slug Plugin identifier
     * @param array $new_settings New plugin settings
     * @return bool True if updated, false otherwise
     */
    public function update_plugin_settings($plugin_slug, $new_settings) {
        if (!$this->is_plugin_registered($plugin_slug)) {
            return false;
        }

        $settings = get_option('docgen_implementation_settings');
        $settings['plugins'][$plugin_slug]['settings'] = $new_settings;

        return update_option('docgen_implementation_settings', $settings);
    }

    /**
     * Check if plugin is registered
     * @param string $plugin_slug Plugin identifier
     * @return bool True if registered, false otherwise
     */
    private function is_plugin_registered($plugin_slug) {
        $settings = get_option('docgen_implementation_settings');
        return isset($settings['plugins'][$plugin_slug]);
    }
}
