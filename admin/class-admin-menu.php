<?php
/**
 * Admin Menu Handler
 *
 * @package     DocGen_Implementation
 * @subpackage  Admin
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: admin/class-admin-menu.php
 * 
 * Changelog:
 * 1.0.0 - 2024-11-24
 * - Initial implementation
 * - Handles main admin menu registration
 * - Manages admin pages
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

class DocGen_Implementation_Admin_Menu {
    /**
     * Menu instance
     * @var DocGen_Implementation_Admin_Menu
     */
    private static $instance = null;

    /**
     * Parent menu slug
     * @var string
     */
    private $parent_slug = 'docgen-implementation';

    /**
     * Admin pages
     * @var array
     */
    private $pages = array();

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'register_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Get menu instance
     * @return DocGen_Implementation_Admin_Menu
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register admin menus
     */
    public function register_menus() {
        // Add main menu
        add_menu_page(
            __('DocGen Implementation', 'docgen-implementation'),
            __('DocGen Impl', 'docgen-implementation'),
            'manage_options',
            $this->parent_slug,
            array($this, 'render_main_page'),
            'dashicons-media-document',
            30
        );

        // Add dashboard submenu
        add_submenu_page(
            $this->parent_slug,
            __('Dashboard', 'docgen-implementation'),
            __('Dashboard', 'docgen-implementation'),
            'manage_options',
            $this->parent_slug,
            array($this, 'render_main_page')
        );

        // Add settings submenu
        add_submenu_page(
            $this->parent_slug,
            __('Settings', 'docgen-implementation'),
            __('Settings', 'docgen-implementation'),
            'manage_options',
            $this->parent_slug . '-settings',
            array($this, 'render_settings_page')
        );

        do_action('docgen_implementation_register_admin_menu');
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        // Only load on our admin pages
        if (strpos($hook, $this->parent_slug) === false) {
            return;
        }

        // Enqueue admin styles
        wp_enqueue_style(
            'docgen-implementation-admin',
            DOCGEN_IMPLEMENTATION_URL . 'assets/css/style.css',
            array(),
            DOCGEN_IMPLEMENTATION_VERSION
        );

        // Enqueue admin scripts
        wp_enqueue_script(
            'docgen-implementation-admin',
            DOCGEN_IMPLEMENTATION_URL . 'assets/js/script.js',
            array('jquery'),
            DOCGEN_IMPLEMENTATION_VERSION,
            true
        );

        // Localize script
        wp_localize_script('docgen-implementation-admin', 'docgenImplementation', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('docgen_implementation'),
            'strings' => array(
                'confirm' => __('Are you sure?', 'docgen-implementation'),
                'success' => __('Success!', 'docgen-implementation'),
                'error' => __('Error occurred.', 'docgen-implementation')
            )
        ));
    }

    /**
     * Render main admin page
     */
    public function render_main_page() {
        require_once DOCGEN_IMPLEMENTATION_DIR . 'admin/class-admin-page.php';
        $page = new DocGen_Implementation_Admin_Page();
        $page->render_dashboard();
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        require_once DOCGEN_IMPLEMENTATION_DIR . 'admin/class-admin-page.php';
        $page = new DocGen_Implementation_Admin_Page();
        $page->render_settings();
    }

    /**
     * Register admin page
     * @param string $slug Page slug
     * @param array $args Page arguments
     */
    public function register_page($slug, $args = array()) {
        $this->pages[$slug] = wp_parse_args($args, array(
            'title' => '',
            'menu_title' => '',
            'capability' => 'manage_options',
            'callback' => null,
            'position' => null
        ));
    }

    /**
     * Get parent menu slug
     * @return string
     */
    public function get_parent_slug() {
        return $this->parent_slug;
    }
}