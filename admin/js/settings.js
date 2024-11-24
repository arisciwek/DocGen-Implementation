/**
 * Settings Page Scripts
 *
 * @package     DocGen_Implementation
 * @subpackage  Admin
 * @version     1.0.1
 * @author      arisciwek
 * 
 * Path: admin/js/settings.js
 * 
 * Description:
 * Handles all Settings page specific functionality
 * Including directory testing and template validation
 * 
 * Changelog:
 * 1.0.0 - 2024-11-24
 * - Initial release with directory and template testing
 */

jQuery(document).ready(function($) {
    // Get upload base path from page
    const uploadBase = $('code.base-path').first().text();
    const templatesPath = uploadBase;
    
    // Update base path display for template directory
    $('code.base-path').last().text(templatesPath);

    // Handle Test Template Directory button
    $('#test-template-dir-btn').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $result = $('#test-template-dir-result');
        var directory = $('input[name="template_dir"]').val();
        
        $button.prop('disabled', true);
        $button.find('.spinner').addClass('is-active');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'test_template_dir',
                directory: directory,
                nonce: docgenSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    var templates = response.data.templates;
                    var templateList = '';
                    
                    if (templates.length > 0) {
                        templateList = '<ul>';
                        templates.forEach(function(tpl) {
                            templateList += '<li>' + tpl.name + ' (' + tpl.size + ' - ' + tpl.modified + ')</li>';
                        });
                        templateList += '</ul>';
                    }
                    
                    $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p>' +
                        '<p><strong>Templates Found:</strong> ' + response.data.template_count + '</p>' +
                        templateList +
                        '</div>'
                    );
                    
                    // Update status indicators with full path
                    var status = '<p>' +
                        '<strong>Directory Status:</strong><br>' +
                        'Exists: ' + (response.data.exists ? '✅' : '❌') + '<br>' +
                        'Readable: ' + (response.data.readable ? '✅' : '❌') + '<br>' +
                        'Templates: ' + response.data.template_count + ' files<br>' +
                        'Path: ' + response.data.path +
                        '</p>';
                    $('.template-dir-status').html(status);
                    
                    // Refresh template dropdown if exists
                    if (templates.length > 0 && $('#template-file').length) {
                        var $select = $('#template-file');
                        $select.empty().append('<option value="">' + docgenSettings.strings.selectTemplate + '</option>');
                        
                        templates.forEach(function(tpl) {
                            $select.append('<option value="' + response.data.path + '/' + tpl.name + '">' + tpl.name + '</option>');
                        });
                    }
                    
                } else {
                    $result.html('<div class="notice notice-error"><p>' + docgenSettings.strings.testFailed + ' ' + response.data + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error"><p>' + docgenSettings.strings.testFailed + ' Server error</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false);
                $button.find('.spinner').removeClass('is-active');
            }
        });
    });
    
    // Handle Test Directory button
    $('#test-directory-btn').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $result = $('#test-directory-result');
        var directory = $('input[name="temp_dir"]').val();
        
        $button.prop('disabled', true);
        $button.find('.spinner').addClass('is-active');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'test_directory',
                directory: directory,
                nonce: docgenSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    
                    // Update status indicators with full path
                    var status = '<p>' +
                        '<strong>Status:</strong><br>' +
                        'Exists: ' + (response.data.exists ? '✅' : '❌') + '<br>' +
                        'Writable: ' + (response.data.writable ? '✅' : '❌') + '<br>' +
                        'Free Space: ' + response.data.free_space + '<br>' +
                        'Path: ' + response.data.path +
                        '</p>';
                    $('.temp-dir-status').html(status);
                } else {
                    $result.html('<div class="notice notice-error"><p>' + docgenSettings.strings.testFailed + ' ' + response.data + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error"><p>' + docgenSettings.strings.testFailed + ' Server error</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false);
                $button.find('.spinner').removeClass('is-active');
            }
        });
    });

    // Handle Test Template button
    $('#test-template-dir-btn').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $result = $('#test-template-dir-result');
        var directory = $('input[name="template_dir"]').val();
        
        $button.prop('disabled', true);
        $button.find('.spinner').addClass('is-active');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'test_template_dir',
                directory: directory,
                nonce: docgenSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    var templates = response.data.templates;
                    var templateList = '';
                    
                    if (templates.length > 0) {
                        templateList = '<ul>';
                        templates.forEach(function(tpl) {
                            templateList += '<li>' + tpl.name + ' (' + tpl.size + ' - ' + tpl.modified + ')</li>';
                        });
                        templateList += '</ul>';
                    }
                    
                    $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p>' +
                        '<p><strong>Templates Found:</strong> ' + response.data.template_count + '</p>' +
                        templateList +
                        '</div>'
                    );
                    
                    // Update status indicators with full path
                    var status = '<p>' +
                        '<strong>Directory Status:</strong><br>' +
                        'Exists: ' + (response.data.exists ? '✅' : '❌') + '<br>' +
                        'Readable: ' + (response.data.readable ? '✅' : '❌') + '<br>' +
                        'Templates: ' + response.data.template_count + ' files<br>' +
                        'Path: ' + response.data.path +
                        '</p>';
                    $('.template-dir-status').html(status);
                    
                    // Refresh template dropdown if exists
                    if (templates.length > 0 && $('#template-file').length) {
                        var $select = $('#template-file');
                        $select.empty().append('<option value="">' + docgenSettings.strings.selectTemplate + '</option>');
                        
                        templates.forEach(function(tpl) {
                            $select.append('<option value="' + response.data.path + '/' + tpl.name + '">' + tpl.name + '</option>');
                        });
                    }
                    
                } else {
                    $result.html('<div class="notice notice-error"><p>' + docgenSettings.strings.testFailed + ' ' + response.data + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error"><p>' + docgenSettings.strings.testFailed + ' Server error</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false);
                $button.find('.spinner').removeClass('is-active');
            }
        });
    });
    
    // Update template list when template directory changes
    $('input[name="template_dir"]').on('change', function() {
        var directory = $(this).val();
        var $select = $('#template-file');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_templates',
                directory: directory,
                nonce: docgenSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    $select.empty().append('<option value="">' + docgenSettings.strings.selectTemplate + '</option>');
                    
                    $.each(response.data, function(index, template) {
                        $select.append('<option value="' + template.path + '">' + template.name + '</option>');
                    });
                }
            }
        });
    });
});


