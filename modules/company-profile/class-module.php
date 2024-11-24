<?php
/**
 * Company Profile Module
 *
 * @package     DocGen_Implementation
 * @subpackage  Company_Profile
 * @version     1.0.0
 * 
 * Path: modules/company-profile/class-module.php
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

class DocGen_Implementation_Company_Profile_Module {
    /**
     * Module instance
     * @var self|null
     */
    protected static $instance = null;

    /**
     * Module info
     */
    const MODULE_SLUG = 'company-profile';
    const MODULE_VERSION = '1.0.1';

    /**
     * Constructor
     */
    private function __construct() {
        // Register module
        add_filter('docgen_implementation_modules', array($this, 'register_module'));
        
        // Add menu item
        add_action('docgen_implementation_register_admin_menu', array($this, 'add_menu_item'));
        
        // Register AJAX handlers
        add_action('wp_ajax_generate_company_profile', array($this, 'handle_generate_profile'));
        
        // Load assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Get module instance
     * @return self
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register module info
     */
    public function register_module($modules) {
        $modules[] = array(
            'slug' => self::MODULE_SLUG,
            'name' => __('Company Profile', 'docgen-implementation'),
            'description' => __('Generate professional company profile documents', 'docgen-implementation'),
            'version' => self::MODULE_VERSION,
            'instance' => $this
        );
        return $modules;
    }

    /**
     * Add menu item
     */
    public function add_menu_item() {
        $parent_slug = DocGen_Implementation_Admin_Menu::get_instance()->get_parent_slug();
        
        add_submenu_page(
            $parent_slug,
            __('Company Profile', 'docgen-implementation'),
            __('Company Profile', 'docgen-implementation'),
            'manage_options',
            'docgen-' . self::MODULE_SLUG,
            array($this, 'render_page')
        );
    }

    /**
     * Render module page
     */
    public function render_page() {
        require_once dirname(__FILE__) . '/views/page.php';
    }

    /**
     * Handle profile generation with detailed error handling
     */
    public function handle_generate_profile() {
        check_ajax_referer('docgen_implementation');

        try {
            // Debug: Cek data form
            error_log('POST data: ' . print_r($_POST, true));
            
            // Load provider
            require_once dirname(__FILE__) . '/includes/class-provider.php';
            $provider = new DocGen_Implementation_Company_Profile_Provider();
            
            // Debug: Cek data provider
            error_log('Provider data: ' . print_r($provider->get_data(), true));
            
            // Generate document
            $result = wp_docgen()->generate($provider);
            
            // Debug: Cek hasil generate
            error_log('Generate result: ' . print_r($result, true));
            
            if (is_wp_error($result)) {
                error_log('Generate error: ' . $result->get_error_message());
                wp_send_json_error($result->get_error_message());
            }

            $upload_dir = wp_upload_dir();
            $file_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $result);
            
            wp_send_json_success(array(
                'url' => $file_url,
                'file' => basename($result)
            ));

        } catch (Exception $e) {
            error_log('Exception: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }



    /**
     * Enqueue module assets
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'docgen-' . self::MODULE_SLUG) === false) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'docgen-company-profile',
            plugins_url('assets/css/style.css', __FILE__),
            array(),
            self::MODULE_VERSION
        );

        // Enqueue JS
        wp_enqueue_script(
            'docgen-company-profile',
            plugins_url('assets/js/script.js', __FILE__),
            array('jquery'),
            self::MODULE_VERSION,
            true
        );

        // Localize script
        wp_localize_script('docgen-company-profile', 'docgenCompanyProfile', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('docgen_implementation'),
            'strings' => array(
                'error' => __('An error occurred while generating the document.', 'docgen-implementation')
            )
        ));
    }

    /**
     * Initialize module
     */
    public static function init() {
        return self::get_instance();
    }
}

// Initialize module
DocGen_Implementation_Company_Profile_Module::init();
