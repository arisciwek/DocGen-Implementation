<?php
/**
 * Company Profile Admin View
 *
 * @package     DocGen_Implementation
 * @subpackage  Company_Profile
 * @version     1.0.0
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

// Get saved company data
$company_data = get_option('docgen_company_profile_data', array());

// Default values for all fields
$defaults = array(
    'company_name' => '',
    'legal_name' => '',
    'tagline' => '',
    'address' => array(
        'street' => '',
        'city' => '',
        'province' => '',
        'postal_code' => '',
        'country' => ''
    ),
    'contact' => array(
        'phone' => '',
        'email' => '',
        'website' => ''
    ),
    'registration' => array(
        'company_id' => '',
        'tax_id' => '',
        'established_date' => ''
    ),
    'profile' => array(
        'vision' => '',
        'mission' => array(),
        'values' => array()
    ),
    'business' => array(
        'main_services' => array(),
        'industries' => array(),
        'employee_count' => '',
        'office_locations' => array()
    ),
    'certifications' => array()
);

// Merge saved data with defaults
$data = wp_parse_args($company_data, $defaults);
?>

<div class="wrap">
    <h1><?php _e('Company Profile', 'docgen-implementation'); ?></h1>

    <div class="card">
        <h2><?php _e('Basic Information', 'docgen-implementation'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php _e('Company Name', 'docgen-implementation'); ?></th>
                <td>
                    <input type="text" 
                           class="regular-text"
                           name="company_name" 
                           value="<?php echo esc_attr($data['company_name']); ?>" />
                </td>
            </tr>
            <tr>
                <th><?php _e('Legal Name', 'docgen-implementation'); ?></th>
                <td>
                    <input type="text"
                           class="regular-text"
                           name="legal_name"
                           value="<?php echo esc_attr($data['legal_name']); ?>" />
                </td>
            </tr>
            <tr>
                <th><?php _e('Tagline', 'docgen-implementation'); ?></th>
                <td>
                    <input type="text"
                           class="large-text"
                           name="tagline"
                           value="<?php echo esc_attr($data['tagline']); ?>" />
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2><?php _e('Address & Contact', 'docgen-implementation'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php _e('Street Address', 'docgen-implementation'); ?></th>
                <td>
                    <input type="text"
                           class="large-text"
                           name="address[street]"
                           value="<?php echo esc_attr($data['address']['street']); ?>" />
                </td>
            </tr>
            <tr>
                <th><?php _e('City', 'docgen-implementation'); ?></th>
                <td>
                    <input type="text"
                           class="regular-text"
                           name="address[city]"
                           value="<?php echo esc_attr($data['address']['city']); ?>" />
                </td>
            </tr>
            <tr>
                <th><?php _e('Province', 'docgen-implementation'); ?></th>
                <td>
                    <input type="text"
                           class="regular-text"
                           name="address[province]"
                           value="<?php echo esc_attr($data['address']['province']); ?>" />
                </td>
            </tr>
            <tr>
                <th><?php _e('Postal Code', 'docgen-implementation'); ?></th>
                <td>
                    <input type="text"
                           class="regular-text"
                           name="address[postal_code]"
                           value="<?php echo esc_attr($data['address']['postal_code']); ?>" />
                </td>
            </tr>
            <tr>
                <th><?php _e('Phone', 'docgen-implementation'); ?></th>
                <td>
                    <input type="tel"
                           class="regular-text"
                           name="contact[phone]"
                           value="<?php echo esc_attr($data['contact']['phone']); ?>" />
                </td>
            </tr>
            <tr>
                <th><?php _e('Email', 'docgen-implementation'); ?></th>
                <td>
                    <input type="email"
                           class="regular-text"
                           name="contact[email]"
                           value="<?php echo esc_attr($data['contact']['email']); ?>" />
                </td>
            </tr>
            <tr>
                <th><?php _e('Website', 'docgen-implementation'); ?></th>
                <td>
                    <input type="url"
                           class="regular-text"
                           name="contact[website]"
                           value="<?php echo esc_attr($data['contact']['website']); ?>" />
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2><?php _e('Business Information', 'docgen-implementation'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php _e('Main Services', 'docgen-implementation'); ?></th>
                <td>
                    <textarea name="business[main_services]" 
                              class="large-text" 
                              rows="4"><?php echo esc_textarea(implode("\n", (array)$data['business']['main_services'])); ?></textarea>
                    <p class="description"><?php _e('One service per line', 'docgen-implementation'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php _e('Industries', 'docgen-implementation'); ?></th>
                <td>
                    <textarea name="business[industries]" 
                              class="large-text" 
                              rows="4"><?php echo esc_textarea(implode("\n", (array)$data['business']['industries'])); ?></textarea>
                    <p class="description"><?php _e('One industry per line', 'docgen-implementation'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php _e('Employee Count', 'docgen-implementation'); ?></th>
                <td>
                    <input type="text"
                           class="regular-text"
                           name="business[employee_count]"
                           value="<?php echo esc_attr($data['business']['employee_count']); ?>" />
                </td>
            </tr>
            <tr>
                <th><?php _e('Office Locations', 'docgen-implementation'); ?></th>
                <td>
                    <textarea name="business[office_locations]" 
                              class="large-text" 
                              rows="4"><?php echo esc_textarea(implode("\n", (array)$data['business']['office_locations'])); ?></textarea>
                    <p class="description"><?php _e('One location per line', 'docgen-implementation'); ?></p>
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2><?php _e('Profile & Vision', 'docgen-implementation'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php _e('Vision', 'docgen-implementation'); ?></th>
                <td>
                    <textarea name="profile[vision]" 
                              class="large-text" 
                              rows="3"><?php echo esc_textarea($data['profile']['vision']); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><?php _e('Mission', 'docgen-implementation'); ?></th>
                <td>
                    <textarea name="profile[mission]" 
                              class="large-text" 
                              rows="4"><?php echo esc_textarea(implode("\n", (array)$data['profile']['mission'])); ?></textarea>
                    <p class="description"><?php _e('One mission statement per line', 'docgen-implementation'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php _e('Values', 'docgen-implementation'); ?></th>
                <td>
                    <textarea name="profile[values]" 
                              class="large-text" 
                              rows="4"><?php echo esc_textarea(implode("\n", (array)$data['profile']['values'])); ?></textarea>
                    <p class="description"><?php _e('One value per line', 'docgen-implementation'); ?></p>
                </td>
            </tr>
        </table>
    </div>

    <div class="card">
        <h2><?php _e('Document Generation', 'docgen-implementation'); ?></h2>
        <p>
            <button type="button" id="generate-profile" class="button button-primary">
                <?php _e('Generate Company Profile', 'docgen-implementation'); ?>
            </button>
            <span class="spinner"></span>
        </p>
        <div id="generation-result" style="display:none;">
            <p class="description">
                <?php _e('Your document has been generated:', 'docgen-implementation'); ?>
                <a href="#" id="download-profile" class="button">
                    <?php _e('Download Document', 'docgen-implementation'); ?>
                </a>
            </p>
        </div>
    </div>
</div>