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
        $this->load_data();
    }

    /**
     * Load company data dari JSON
     */
    private function load_data() {
        $json_file = dirname(__DIR__) . '/data/data.json';
        
        if (!file_exists($json_file)) {
            $this->data = [];
            return;
        }

        $json_content = file_get_contents($json_file);
        $this->data = json_decode($json_content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->data = [];
        }
    }

    /**
     * Get data untuk template
     * @return array
     */
    public function get_data() {
        if (empty($this->data)) {
            return [];
        }

        // Basic company info
        $template_data = [
            'company_name' => $this->data['company_name'],
            'legal_name' => $this->data['legal_name'],
            'tagline' => $this->data['tagline'],
            
            // Address section
            'street' => $this->data['address']['street'],
            'city' => $this->data['address']['city'],
            'province' => $this->data['address']['province'],
            'postal_code' => $this->data['address']['postal_code'],
            'country' => $this->data['address']['country'],
            
            // Full address formatted
            'complete_address' => sprintf(
                "%s\n%s, %s %s\n%s",
                $this->data['address']['street'],
                $this->data['address']['city'], 
                $this->data['address']['province'],
                $this->data['address']['postal_code'],
                $this->data['address']['country']
            ),

            // Contact details
            'phone_number' => $this->data['contact']['phone'],
            'email_address' => $this->data['contact']['email'],
            'website_url' => $this->data['contact']['website'],

            // Registration info with formatted date
            'company_registration_id' => $this->data['registration']['company_id'],
            'tax_registration_id' => $this->data['registration']['tax_id'],
			'date_established' => '${tanggal:' . $this->data['registration']['established_date'] . ':j F Y}',
            // Company profile
            'company_vision' => $this->data['profile']['vision'],
            
            // Format arrays into bullet points
            'company_mission' => $this->format_bullet_points($this->data['profile']['mission']),
            'company_values' => $this->format_bullet_points($this->data['profile']['values']),
            
            // Business information
            'services' => $this->format_bullet_points($this->data['business']['main_services']),
            'target_industries' => $this->format_bullet_points($this->data['business']['industries']),
            'employee_count' => $this->data['business']['employee_count'],
            'office_locations' => $this->format_bullet_points($this->data['business']['office_locations']),

            // Format certifications with expiry dates
            'certifications' => $this->format_certifications(),

            // Add generated timestamp
			'generated_date' => '${tanggal:' . date('Y-m-d H:i:s') . ':j F Y H:i}',
			
			// Contoh penggunaan field user lainnya
			'generated_by_email' => '${user:email}',
			'generated_by_role' => '${user:role}',
			'generated_by_id' => '${user:ID}',
			'generated_by_login' => '${user:user_login}',            
            
            // Add QR code with company website
            //'company_qr' => ${qrcode:$this->data['contact']['website']:150}
        ];

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
                $cert['name'],
                $cert['description'],
                '${tanggal:' . $cert['valid_until'] . ':j F Y}'
            );
        }, $this->data['certifications']));
    }

    /**
     * Get template path
     * @return string
     */
    public function get_template_path() {
        return dirname(__DIR__) . '/templates/company-profile-template.docx';
    }

    /**
     * Get output filename
     * @return string
     */
    public function get_output_filename() {
        if (empty($this->data)) {
            return 'company-profile-' . date('Y-m-d');
        }

        return sanitize_title($this->data['company_name']) . '-company-profile-' . date('Y-m-d');
    }

    /**
     * Get output format
     * @return string
     */
    public function get_output_format() {
        return 'docx';
    }

    /**
     * Get temporary directory
     * @return string
     */
    public function get_temp_dir() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/wp-docgen-temp/company-profiles';
    }
}
