/**
* Directory Migration Handler Scripts
*
* @package     DocGen_Implementation
* @subpackage  Admin
* @version     1.0.0
* @author      arisciwek
* 
* Path: admin/js/directory-migration.js
* 
* Description: 
* Handles client-side functionality for directory migration process.
* Manages form interception, directory checking, migration progress,
* and user interaction during migration process.
* 
* Changelog:
* 1.0.0 - 2024-11-24
* - Initial release
* - Form submission handling
* - Directory change detection
* - Migration progress UI
* - AJAX handlers for migration process
* - User confirmation dialogs
* 
* Dependencies:
* - jQuery
* - settings.js (docgenSettings object)
* - WordPress admin AJAX
* 
* Global Objects:
* docgenSettings {
*   ajaxUrl: string
*   nonce: string
*   strings: {
*     migrationPrompt: string
*     migrating: string 
*     migrationComplete: string
*     migrationError: string
*     from: string
*     to: string
*     files: string
*     migrated: string
*     skipped: string
*     errors: string
*     templateDir: string
*     tempDir: string
*     migrationConfirm: string
*   }
* }
* 
* Usage:
* Handles:
* - Form submission interception when directory changes detected
* - AJAX request to check if migration needed
* - User confirmation for migration process
* - Migration progress UI and overlay
* - Success/error handling and user feedback
* 
* Notes:
* - Must be loaded after jQuery and settings.js
* - Requires docgenSettings global object
* - Uses WordPress admin AJAX endpoint
*/

jQuery(document).ready(function($) {
    var formModified = false;
    var $form = $('form[name="docgen-settings"]');
    var $dirInputs = $('input[name="temp_dir"], input[name="template_dir"]');
    
    // Track form changes
    $dirInputs.on('change', function() {
        formModified = true;
    });
    
    // Handle form submission
    $form.on('submit', function(e) {
        if (!formModified) return true;
        
        e.preventDefault();
        
        // Check if migration needed
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'docgen_check_migration',
                nonce: docgenSettings.nonce,
                temp_dir: $('input[name="temp_dir"]').val(),
                template_dir: $('input[name="template_dir"]').val()
            },
            success: function(response) {
                if (!response.success) {
                    alert(docgenSettings.strings.error);
                    return;
                }
                
                if (!response.data.has_changes) {
                    $form.off('submit').submit();
                    return;
                }
                
                // Show migration dialog
                showMigrationDialog(response.data.changes, function() {
                    migrateFiles(function() {
                        $form.off('submit').submit();
                    });
                });
            }
        });
    });
    
    function showMigrationDialog(changes, onConfirm) {
        var message = docgenSettings.strings.migrationPrompt + "\n\n";
        
        if (changes.template) {
            message += docgenSettings.strings.templateDir + ":\n";
            message += "- " + docgenSettings.strings.from + ": " + changes.template.from + "\n";
            message += "- " + docgenSettings.strings.to + ": " + changes.template.to + "\n";
            message += "- " + docgenSettings.strings.files + ": " + changes.template.files + "\n\n";
        }
        
        if (changes.temp) {
            message += docgenSettings.strings.tempDir + ":\n";
            message += "- " + docgenSettings.strings.from + ": " + changes.temp.from + "\n";
            message += "- " + docgenSettings.strings.to + ": " + changes.temp.to + "\n";
            message += "- " + docgenSettings.strings.files + ": " + changes.temp.files + "\n\n";
        }
        
        message += docgenSettings.strings.migrationConfirm;
        
        if (confirm(message)) {
            onConfirm();
        }
    }
    
    function migrateFiles(onComplete) {
        var $overlay = $('<div class="docgen-migration-overlay">' +
            '<div class="migration-status">' +
            '<h3>' + docgenSettings.strings.migrating + '</h3>' +
            '<div class="spinner is-active"></div>' +
            '</div></div>');
        
        $('body').append($overlay);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'docgen_migrate_files',
                nonce: docgenSettings.nonce,
                temp_dir: $('input[name="temp_dir"]').val(),
                template_dir: $('input[name="template_dir"]').val()
            },
            success: function(response) {
                if (!response.success) {
                    alert(docgenSettings.strings.migrationError);
                    return;
                }
                
                var results = response.data;
                var message = docgenSettings.strings.migrationComplete + "\n\n";
                
                if (results.template) {
                    message += docgenSettings.strings.templateDir + ":\n";
                    message += "- " + docgenSettings.strings.migrated + ": " + results.template.migrated + "\n";
                    message += "- " + docgenSettings.strings.skipped + ": " + results.template.skipped + "\n";
                    
                    if (results.template.errors.length) {
                        message += "- " + docgenSettings.strings.errors + ":\n  " + 
                            results.template.errors.join("\n  ") + "\n";
                    }
                }
                
                if (results.temp) {
                    message += "\n" + docgenSettings.strings.tempDir + ":\n";
                    message += "- " + docgenSettings.strings.migrated + ": " + results.temp.migrated + "\n";
                    message += "- " + docgenSettings.strings.skipped + ": " + results.temp.skipped + "\n";
                    
                    if (results.temp.errors.length) {
                        message += "- " + docgenSettings.strings.errors + ":\n  " + 
                            results.temp.errors.join("\n  ") + "\n";
                    }
                }
                
                alert(message);
                
                if (onComplete) onComplete();
            },
            error: function() {
                alert(docgenSettings.strings.migrationError);
            },
            complete: function() {
                $overlay.remove();
            }
        });
    }
});

