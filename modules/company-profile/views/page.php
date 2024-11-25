<?php
/**
 * Company Profile Admin View
 *
 * @package     DocGen_Implementation
 * @subpackage  Company_Profile
 * @version     1.0.1
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

// Get data from JSON file
$json_file = dirname(__FILE__) . '/../data/data.json';
$json_data = array();
if (file_exists($json_file)) {
    $json_content = file_get_contents($json_file);
    $json_data = json_decode($json_content, true);
}

// Default values for form fields
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
?>

<style>
.docgen-panels {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.docgen-panel-json {
    flex: 0 0 40%;
}

.docgen-panel-form {
    flex: 0 0 60%;
}

.docgen-panel-json .card,
.docgen-panel-form .card {
    height: 100%;
    margin: 0 0 20px 0;
}

.json-data-display {
    background: #f8f9fa;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 10px;
}

.json-data-display p {
    margin: 5px 0;
}

.json-data-display h4 {
    margin: 15px 0 5px 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}

.json-data-section {
    margin-bottom: 15px;
}
</style>

<div class="wrap">
    <h1><?php _e('Company Profile', 'docgen-implementation'); ?></h1>

    <div class="docgen-panels">
        <!-- Left Panel - JSON Data -->
        <div class="docgen-panel-json">
            <div class="card">
                <h2><?php _e('Company Data from JSON', 'docgen-implementation'); ?></h2>
                <div class="json-data-display">
                    <div class="json-data-section">
                        <h4><?php _e('Basic Information', 'docgen-implementation'); ?></h4>
                        <p><strong>Company Name:</strong> <?php echo esc_html($json_data['company_name'] ?? ''); ?></p>
                        <p><strong>Legal Name:</strong> <?php echo esc_html($json_data['legal_name'] ?? ''); ?></p>
                        <p><strong>Tagline:</strong> <?php echo esc_html($json_data['tagline'] ?? ''); ?></p>
                    </div>

                    <div class="json-data-section">
                        <h4><?php _e('Address', 'docgen-implementation'); ?></h4>
                        <p><?php echo esc_html($json_data['address']['street'] ?? ''); ?></p>
                        <p><?php echo sprintf(
                            '%s, %s %s',
                            esc_html($json_data['address']['city'] ?? ''),
                            esc_html($json_data['address']['province'] ?? ''),
                            esc_html($json_data['address']['postal_code'] ?? '')
                        ); ?></p>
                        <p><?php echo esc_html($json_data['address']['country'] ?? ''); ?></p>
                    </div>

                    <div class="json-data-section">
                        <h4><?php _e('Contact', 'docgen-implementation'); ?></h4>
                        <p><strong>Phone:</strong> <?php echo esc_html($json_data['contact']['phone'] ?? ''); ?></p>
                        <p><strong>Email:</strong> <?php echo esc_html($json_data['contact']['email'] ?? ''); ?></p>
                        <p><strong>Website:</strong> <?php echo esc_html($json_data['contact']['website'] ?? ''); ?></p>
                    </div>

                    <div class="json-data-section">
                        <h4><?php _e('Business Information', 'docgen-implementation'); ?></h4>
                        <p><strong>Employee Count:</strong> <?php echo esc_html($json_data['business']['employee_count'] ?? ''); ?></p>
                        <p><strong>Main Services:</strong></p>
                        <ul>
                            <?php
                            if (!empty($json_data['business']['main_services'])) {
                                foreach ($json_data['business']['main_services'] as $service) {
                                    echo '<li>' . esc_html($service) . '</li>';
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>

                <p>
                    <button type="button" id="generate-profile-json" class="button button-primary">
                        <?php _e('Generate from JSON', 'docgen-implementation'); ?>
                        <span class="spinner"></span>
                    </button>
                </p>
                <div id="generation-result-json" style="display:none;">
                    <p class="description">
                        <?php _e('Your document has been generated:', 'docgen-implementation'); ?>
                        <a href="#" id="download-profile-json" class="button">
                            <?php _e('Download JSON Document', 'docgen-implementation'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Panel - Form -->
        <div class="docgen-panel-form">
            <form id="company-profile-form" method="post">
                <div class="card">
                    <h2><?php _e('Company Information Form', 'docgen-implementation'); ?></h2>
                                
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
                    <h2><?php _e('Address & Contact', 'docgen-implementation'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php _e('Street Address', 'docgen-implementation'); ?></th>
                            <td>
                                <input type="text"
                                       class="large-text"
                                       name="address[street]"
                                       value="" />
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('City', 'docgen-implementation'); ?></th>
                            <td>
                                <input type="text"
                                       class="regular-text"
                                       name="address[city]"
                                       value="" />
                            </td>
                        </tr>
                        <!-- ... other address and contact fields ... -->
                    </table>
                </div>

                <div class="card">
                    <h2><?php _e('Document Generation', 'docgen-implementation'); ?></h2>
                    <p>
                        <button type="button" id="generate-profile-form" class="button button-primary">
                            <?php _e('Generate from Form', 'docgen-implementation'); ?>
                            <span class="spinner"></span>
                        </button>
                    </p>
                    <div id="generation-result-form" style="display:none;">
                        <p class="description">
                            <?php _e('Your document has been generated:', 'docgen-implementation'); ?>
                            <a href="#" id="download-profile-form" class="button">
                                <?php _e('Download Form Document', 'docgen-implementation'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle JSON data generation
    $('#generate-profile-json').on('click', function() {
        var $button = $(this);
        var $spinner = $button.find('.spinner');
        var $result = $('#generation-result-json');
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.hide();
        
        $.ajax({
            url: docgenCompanyProfile.ajaxUrl,
            type: 'POST',
            data: {
                action: 'generate_company_profile',
                source: 'json',
                _ajax_nonce: docgenCompanyProfile.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#download-profile-json')
                        .attr('href', response.data.url)
                        .attr('download', response.data.file);
                    $result.fadeIn();
                } else {
                    alert(response.data || docgenCompanyProfile.strings.error);
                }
            },
            error: function() {
                alert(docgenCompanyProfile.strings.error);
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });

    // Handle form data generation
    $('#generate-profile-form').on('click', function() {
        var $button = $(this);
        var $spinner = $button.find('.spinner');
        var $result = $('#generation-result-form');
        var formData = $('#company-profile-form').serialize();
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.hide();
        
        $.ajax({
            url: docgenCompanyProfile.ajaxUrl,
            type: 'POST',
            data: {
                action: 'generate_company_profile',
                source: 'form',
                form_data: formData,
                _ajax_nonce: docgenCompanyProfile.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#download-profile-form')
                        .attr('href', response.data.url)
                        .attr('download', response.data.file);
                    $result.fadeIn();
                } else {
                    alert(response.data || docgenCompanyProfile.strings.error);
                }
            },
            error: function() {
                alert(docgenCompanyProfile.strings.error);
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
});
</script>

