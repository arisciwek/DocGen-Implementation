/**
 * General Admin Scripts
 *
 * @package     DocGen_Implementation
 * @subpackage  Assets
 * @version     1.0.1
 * @author      arisciwek
 * 
 * Path: assets/js/script.js
 * 
 * Description:
 * Handles general admin functionality and UI interactions
 * For features that are common across all admin pages
 * Including profile generation and document handling
 * 
 * Dependencies:
 * - jQuery
 * - wp.ajax
 * - docgenCompanyProfile global object
 * 
 * Changelog:
 * 1.0.1 - 2024-11-24
 * - Added improved error handling
 * - Enhanced document generation feedback
 * - Added response validation
 * - Improved UX with loading states
 * 
 * 1.0.0 - 2024-11-24
 * - Initial release with general admin UI handlers
 */

jQuery(document).ready(function($) {
    // Stats refresh timer
    let statsTimer = null;

    // Function to format bytes to human readable size
    function formatBytes(bytes) {
        if (bytes === 0) return '0 ' + docgenSettings.i18n.bytes;
        const k = 1024;
        const sizes = [
            docgenSettings.i18n.bytes,
            docgenSettings.i18n.kb,
            docgenSettings.i18n.mb,
            docgenSettings.i18n.gb
        ];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Function to update directory stats display
    function updateDirectoryStats($container, stats) {
        let html = '<div class="directory-stats">';
        html += '<p><strong>' + docgenSettings.strings.totalFiles + ':</strong> ' + stats.total_files + '</p>';
        html += '<p><strong>' + docgenSettings.strings.totalSize + ':</strong> ' + formatBytes(stats.total_size) + '</p>';
        html += '<p><strong>' + docgenSettings.strings.freeSpace + ':</strong> ' + formatBytes(stats.free_space) + '</p>';
        
        if (stats.by_extension && Object.keys(stats.by_extension).length > 0) {
            html += '<p><strong>' + docgenSettings.strings.fileTypes + ':</strong></p><ul>';
            for (let ext in stats.by_extension) {
                html += '<li>' + ext.toUpperCase() + ': ' + stats.by_extension[ext] + '</li>';
            }
            html += '</ul>';
        }
        
        if (stats.last_modified) {
            html += '<p><strong>' + docgenSettings.strings.lastModified + ':</strong> ' + 
                   new Date(stats.last_modified * 1000).toLocaleString() + '</p>';
        }
        
        html += '</div>';
        $container.html(html);
    }

    // Function to refresh directory stats
    function refreshDirectoryStats(directory, $container) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_directory_stats',
                directory: directory,
                nonce: docgenSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateDirectoryStats($container, response.data);
                }
            }
        });
    }

    // Handle Test Directory button
    $('#test-directory-btn').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $result = $('#test-directory-result');
        var $stats = $('.temp-dir-status');
        var directory = $('input[name="temp_dir"]').val();
        
        $button.prop('disabled', true);
        $button.find('.spinner').addClass('is-active');
        
        // Clear existing timer
        if (statsTimer) {
            clearInterval(statsTimer);
        }
        
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
                    updateDirectoryStats($stats, response.data.stats);
                    
                    // Set up periodic stats refresh
                    statsTimer = setInterval(function() {
                        refreshDirectoryStats(directory, $stats);
                    }, docgenSettings.refreshInterval * 1000);
                    
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

    // Handle Test Template Directory button
    $('#test-template-dir-btn').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $result = $('#test-template-dir-result');
        var $status = $('.template-dir-status');
        var directory = $('input[name="template_dir"]').val();
        
        $button.prop('disabled', true);
        $button.find('.spinner').addClass('is-active');
        
        $result.html('<div class="notice notice-info"><p>' + docgenSettings.strings.scanning + '</p></div>');
        
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
                    let templates = response.data.templates;
                    let validCount = response.data.valid_count;
                    let totalCount = response.data.template_count;
                    
                    let html = '<div class="notice notice-success"><p>' + response.data.message + '</p>';
                    html += '<p><strong>' + docgenSettings.strings.templatesFound + ':</strong> ' + totalCount;
                    html += ' (' + validCount + ' ' + docgenSettings.strings.valid + ')</p>';
                    
                    if (templates.length > 0) {
                        html += '<table class="widefat striped">';
                        html += '<thead><tr>';
                        html += '<th>' + docgenSettings.strings.name + '</th>';
                        html += '<th>' + docgenSettings.strings.type + '</th>';
                        html += '<th>' + docgenSettings.strings.size + '</th>';
                        html += '<th>' + docgenSettings.strings.modified + '</th>';
                        html += '<th>' + docgenSettings.strings.status + '</th>';
                        html += '</tr></thead><tbody>';
                        
                        templates.forEach(function(tpl) {
                            html += '<tr>';
                            html += '<td>' + tpl.name + '</td>';
                            html += '<td>' + tpl.type + '</td>';
                            html += '<td>' + tpl.size + '</td>';
                            html += '<td>' + tpl.modified + '</td>';
                            html += '<td>' + (tpl.valid ? 
                                   '<span class="valid">✓</span>' : 
                                   '<span class="invalid">✗</span>') + '</td>';
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table>';
                    }
                    
                    html += '</div>';
                    $result.html(html);
                    
                    // Update status display
                    let status = '<p><strong>' + docgenSettings.strings.directoryStatus + ':</strong><br>';
                    status += docgenSettings.strings.exists + ': ' + (response.data.exists ? '✓' : '✗') + '<br>';
                    status += docgenSettings.strings.readable + ': ' + (response.data.readable ? '✓' : '✗') + '<br>';
                    status += docgenSettings.strings.path + ': ' + response.data.path + '</p>';
                    $status.html(status);
                    
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

    // Cleanup confirmation
    $('button[name="cleanup_temp"]').on('click', function(e) {
        if (!confirm(docgenSettings.strings.cleanupConfirm)) {
            e.preventDefault();
        }
    });

    // Stop stats refresh when leaving page
    $(window).on('beforeunload', function() {
        if (statsTimer) {
            clearInterval(statsTimer);
        }
    });
});