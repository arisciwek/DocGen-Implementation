<?php
/**
 * Company Profile Provider untuk DocGen
 *
 * @package     DocGen_Implementation
 * @subpackage  Company_Profile
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: modules/company-profile/includes/class-provider.php
 * 
 * Changelog:
 * 1.0.0 - 2024-11-24
 * - Initial implementation
 * - Handles JSON data loading and formatting
 * - Implements WP_DocGen_Provider interface
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

class DocGen_Implementation_Company_Profile_Provider implements WP_DocGen_Provider {
    /**
     * Company data
     * @var array
     */
    private $data;

    /**
     * Constructor
     */
    public function __construct() {
        // Existing hooks
        add_filter('docgen_implementation_modules', array($this, 'register_module'));
        add_action('docgen_implementation_register_admin_menu', array($this, 'add_menu_item'));
        add_action('wp_ajax_generate_company_profile', array($this, 'handle_generate_profile'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Add form submission handler
        add_action('admin_init', array($this, 'handle_form_submission'));

        $this->load_data();
    }

    /**
     * Load company data dari JSON
     */
    private function load_data() {
        $json_file = dirname(__DIR__) . '/data/data.json';
        
        error_log('Mencoba membaca file: ' . $json_file);
        
        if (!file_exists($json_file)) {
            error_log('File tidak ditemukan: ' . $json_file);
            $this->data = [];
            return;
        }

        $json_content = file_get_contents($json_file);
        error_log('Isi JSON: ' . $json_content);
        
        $this->data = json_decode($json_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Error parsing JSON: ' . json_last_error_msg());
            $this->data = [];
        }
        
        error_log('Data setelah parsing: ' . print_r($this->data, true));
    }

    public function get_data() {
        error_log('DocGen Raw Data: ' . print_r($this->data, true));

        if (empty($this->data)) {
            return [];
        }

        // Basic company info
        $template_data = array(
            // Info dasar perusahaan
            'company_name' => $this->data['company_name'] ?? '',
            'legal_name' => $this->data['legal_name'] ?? '',
            'tagline' => $this->data['tagline'] ?? '',
            
            // Alamat lengkap
            'address' => sprintf(
                "%s\n%s, %s %s\n%s",
                $this->data['address']['street'] ?? '',
                $this->data['address']['city'] ?? '',
                $this->data['address']['province'] ?? '',
                $this->data['address']['postal_code'] ?? '',
                $this->data['address']['country'] ?? ''
            ),
            
            // Kontak
            'phone' => $this->data['contact']['phone'] ?? '',
            'email' => $this->data['contact']['email'] ?? '',
            'website' => $this->data['contact']['website'] ?? '',

            // Registrasi
            'company_id' => $this->data['registration']['company_id'] ?? '',
            'tax_id' => $this->data['registration']['tax_id'] ?? '',
            'established_date' => isset($this->data['registration']['established_date']) ? 
                '${date:' . $this->data['registration']['established_date'] . ':j F Y}' : '',

            // Profile perusahaan
            'vision' => $this->data['profile']['vision'] ?? '',
            'mission' => isset($this->data['profile']['mission']) ? 
                $this->format_bullet_points($this->data['profile']['mission']) : '',
            'values' => isset($this->data['profile']['values']) ? 
                $this->format_bullet_points($this->data['profile']['values']) : '',

            // Informasi bisnis
            'main_services' => isset($this->data['business']['main_services']) ? 
                $this->format_bullet_points($this->data['business']['main_services']) : '',
            'industries' => isset($this->data['business']['industries']) ?
                $this->format_bullet_points($this->data['business']['industries']) : '',
            'employee_count' => $this->data['business']['employee_count'] ?? '',
            'office_locations' => isset($this->data['business']['office_locations']) ?
                $this->format_bullet_points($this->data['business']['office_locations']) : '',

            // Sertifikasi
            'certifications' => $this->format_certifications(),

            // Metadata
            'generated_date' => '${date:' . date('Y-m-d H:i:s') . ':j F Y H:i}',
            'generated_by' => '${user:display_name}',
            'generated_by_email' => '${user:user_email}'
        );

        // Debug: Log data yang akan digunakan
        if (WP_DEBUG) {
            error_log('DocGen Template Data: ' . print_r($template_data, true));
        }

        return $template_data;
    }

    /**
     * Format array into bullet points
     * @param array $items Array of items
     * @return string Formatted bullet points
     */
    private function format_bullet_points($items) {
        if (!is_array($items)) {
            return '';
        }
        
        return implode("\n", array_map(function($item) {
            return "• " . trim($item);
        }, $items));
    }

    /**
     * Format certifications with dates
     * @return string Formatted certification list
     */
    private function format_certifications() {
        if (!isset($this->data['certifications']) || !is_array($this->data['certifications'])) {
            return '';
        }

        return implode("\n", array_map(function($cert) {
            return sprintf(
                "• %s - %s (Valid until: %s)",
                $cert['name'] ?? '',
                $cert['description'] ?? '',
                isset($cert['valid_until']) ? 
                    '${date:' . $cert['valid_until'] . ':j F Y}' : ''
            );
        }, $this->data['certifications']));
    }

    /**
     * Get template path
     * @return string
     */
    public function get_template_path() {
        $settings = get_option('docgen_implementation_settings', array());
        $template_dir = $settings['template_dir'] ?? '';
        
        if (empty($template_dir)) {
            error_log('DocGen: Template directory not configured in settings');
            throw new Exception('Template directory not configured');
        }

        $template_path = trailingslashit($template_dir) . 'company-profile-template.docx';
        
        error_log('DocGen: Looking for template at: ' . $template_path);
        error_log('Template exists: ' . (file_exists($template_path) ? 'Yes' : 'No'));
        
        if (!file_exists($template_path)) {
            error_log('DocGen: Template file not found at: ' . $template_path);
            throw new Exception('Template file not found at: ' . $template_path);
        }

        return $template_path;
    }

    /**
     * Get output filename
     * @return string
     *
    public function get_output_filename() {
        $settings = get_option('docgen_implementation_settings', array());
        $temp_dir = $settings['temp_dir'] ?? '';
        
        if (empty($temp_dir)) {
            error_log('DocGen: Temp directory not configured in settings');
            throw new Exception('Temp directory not configured');
        }

        $filename = empty($this->data) ? 
            'company-profile-' . date('Y-m-d-His') : 
            sanitize_title($this->data['company_name']) . '-profile-' . date('Y-m-d-His');
        
        $output_path = trailingslashit($temp_dir) . $filename . '.docx';
        error_log('DocGen: Output file will be generated at: ' . $output_path);

        return $output_path;
    }
    */


    /**
     * Get output filename
     * Generates a clean filename for the output document
     * 
     * @return string
     */
    public function get_output_filename() {
        // Get company name or use default
        $company_name = !empty($this->data['company_name']) ? 
            sanitize_title($this->data['company_name']) : 
            'company';

        // Format timestamp
        $timestamp = date('Ymd-His');

        // Construct filename without extension - WP DocGen will add the correct extension
        return sprintf(
            '%s-profile-%s',
            $company_name,
            $timestamp
        );
    }


    /**
     * Get output format
     * @return string
     */
    public function get_output_format() {
        $settings = get_option('docgen_implementation_settings', array());
        return $settings['output_format'] ?? 'docx';
    }

    /**
     * Get temporary directory - Updated untuk menggunakan directory settings
     * @return string
     */
    public function get_temp_dir() {
        $settings = get_option('docgen_implementation_settings', array());
        $temp_dir = $settings['temp_dir'] ?? '';
        
        if (empty($temp_dir)) {
            throw new Exception('Temporary directory not configured');
        }

        // Buat subdirectory untuk company profiles
        $profile_temp_dir = trailingslashit($temp_dir) . 'company-profiles';
        
        // Pastikan directory ada dan writable
        if (!file_exists($profile_temp_dir)) {
            wp_mkdir_p($profile_temp_dir);
        }
        
        if (!is_writable($profile_temp_dir)) {
            throw new Exception('Temporary directory is not writable');
        }

        return $profile_temp_dir;
    }

}
