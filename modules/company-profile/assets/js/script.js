/**
 * Company Profile Module Scripts
 *
 * @package     DocGen_Implementation
 * @subpackage  Company_Profile
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: modules/company-profile/assets/js/script.js
 */

jQuery(document).ready(function($) {
    // Handle Generate Profile button
    $('#generate-profile').on('click', function() {
        var $button = $(this);
        var $spinner = $button.find('.spinner');
        var $result = $('#generation-result');
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.hide();
        
        $.ajax({
            url: docgenCompanyProfile.ajaxUrl,
            type: 'POST',
            data: {
                action: 'generate_company_profile',
                _ajax_nonce: docgenCompanyProfile.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.find('#download-profile')
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