/**
 * Template Upload Handler
 * 
 * @package     DocGen_Implementation
 * @subpackage  Admin/JS
 * @version     1.0.0
 */

jQuery(document).ready(function($) {
    // Template upload handling
    $('.template-upload-area input[type="file"]').on('change', function(e) {
        const $input = $(this);
        const $uploadArea = $input.closest('.template-upload-area');
        const $form = $input.closest('form');
        const $submitButton = $form.find(':submit');
        
        // Check if file is selected
        if (!$input[0].files.length) {
            return;
        }
        
        const file = $input[0].files[0];
        const formData = new FormData();
        formData.append('action', 'upload_template');
        formData.append('nonce', docgenSettings.nonce);
        formData.append('template_file', file);

        // Show upload status
        const $status = $('<div class="notice notice-info"><p>' + 
            docgenSettings.strings.uploadInProgress + '</p></div>');
        $uploadArea.find('.notice').remove();
        $uploadArea.append($status);
        
        // Disable form submission during upload
        $submitButton.prop('disabled', true);
        
        // Send upload request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $uploadArea.find('.notice').remove();
                
                if (response.success) {
                    // Show success message
                    $uploadArea.append(
                        '<div class="notice notice-success"><p>' + 
                        docgenSettings.strings.uploadSuccess + '</p></div>'
                    );
                    
                    // Clear file input
                    $input.val('');
                    
                    // Refresh template list if it exists
                    const $templateList = $('.template-list');
                    if ($templateList.length) {
                        $templateList.before(
                            '<div class="notice notice-info"><p>' + 
                            docgenSettings.strings.refreshing + '</p></div>'
                        );
                        location.reload();
                    }
                } else {
                    // Show error message
                    const error = response.data || docgenSettings.strings.serverError;
                    $uploadArea.append(
                        '<div class="notice notice-error"><p>' + 
                        docgenSettings.strings.uploadFailed + ' ' + error + '</p></div>'
                    );
                }
            },
            error: function() {
                $uploadArea.find('.notice').remove();
                $uploadArea.append(
                    '<div class="notice notice-error"><p>' + 
                    docgenSettings.strings.serverError + '</p></div>'
                );
            },
            complete: function() {
                // Re-enable form submission
                $submitButton.prop('disabled', false);
            }
        });
    });

    // Clear messages when changing tabs
    $('.nav-tab').on('click', function() {
        $('.template-upload-area .notice').remove();
    });
});