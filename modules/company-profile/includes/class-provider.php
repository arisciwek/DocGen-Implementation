<?php
/**
 * Base Provider Class untuk DocGen
 *
 * @package     DocGen_Implementation
 * @subpackage  Company_Profile
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: modules/company-profile/includes/class-provider.php
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

abstract class DocGen_Implementation_Company_Profile_Provider implements WP_DocGen_Provider {
    /**
     * Company data
     * @var array
     */
    protected $data = [];

    /**
     * Get template path
     * @return string
     */
    public function get_template_path() {
        $settings = get_option('docgen_implementation_settings', array());
        $template_dir = $settings['template_dir'] ?? '';
        
        if (empty($template_dir)) {
            throw new Exception('Template directory not configured');
        }

        $template_path = trailingslashit($template_dir) . 'company-profile-template.docx';
        
        if (!file_exists($template_path)) {
            throw new Exception('Template file not found at: ' . $template_path);
        }

        return $template_path;
    }

    /**
     * Get output filename
     * @return string
     */
    public function get_output_filename() {
        // Get company name or use default
        $company_name = !empty($this->data['company_name']) ? 
            sanitize_title($this->data['company_name']) : 
            'company';

        // Format timestamp
        $timestamp = date('Ymd-His');

        // Construct filename without extension
        return sprintf(
            '%s-profile-%s-%s',
            $company_name,
            $this->get_source_identifier(),
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
     * Get temporary directory
     * @return string
     */
    public function get_temp_dir() {
        $settings = get_option('docgen_implementation_settings', array());
        $temp_dir = $settings['temp_dir'] ?? '';
        
        if (empty($temp_dir)) {
            throw new Exception('Temporary directory not configured');
        }

        $profile_temp_dir = trailingslashit($temp_dir) . 'company-profiles';
        
        if (!file_exists($profile_temp_dir)) {
            wp_mkdir_p($profile_temp_dir);
        }
        
        if (!is_writable($profile_temp_dir)) {
            throw new Exception('Temporary directory is not writable');
        }

        return $profile_temp_dir;
    }

    /**
     * Format array into bullet points
     * @param array $items Array of items
     * @return string Formatted bullet points
     */
    protected function format_bullet_points($items) {
        if (!is_array($items)) {
            return '';
        }
        
        return implode("\n", array_map(function($item) {
            return "• " . trim($item);
        }, $items));
    }

    /**
     * Format certifications with dates
     * @param array $certifications Array of certification data
     * @return string Formatted certification list
     */
    protected function format_certifications($certifications) {
        if (!is_array($certifications)) {
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
        }, $certifications));
    }

    /**
     * Get identifier for source type
     * @return string
     */
    abstract protected function get_source_identifier();

    /**
     * Get data for template
     * @return array
     */
    abstract public function get_data();
}
