<?php
/**
* Directory Migration Handler
*
* @package     DocGen_Implementation
* @subpackage  Admin
* @version     1.0.0
* @author      arisciwek
* 
* Path: admin/class-directory-migration.php
* 
* Description: Handles directory migration operations.
*              Mengelola perpindahan file antar direktori saat admin 
*              mengubah konfigurasi direktori template atau temporary.
*              Menyediakan fitur pengecekan, konfirmasi, dan 
*              progress migrasi file secara aman.
* 
* Changelog:
* 1.0.1 - 2024-11-24 10:20:14
* - Fixed incorrect class reference (Directory_Structure to Directory_Handler)
* - Updated directory handler instantiation
* 
* 1.0.0 - 2024-11-24
* - Initial release
* - Migration status checking & validation
* - Secure file migration handlers
* - AJAX endpoints for migration process
* - File counting and status tracking
* - Directory permission handling
* 
* Dependencies:
* - class-directory-handler.php (untuk operasi direktori)
* - directory-migration.js (client-side handler)
* - WordPress file system functions
* 
* AJAX Endpoints:
* - docgen_check_migration
*   Mengecek apakah migrasi dibutuhkan saat perubahan direktori
*   
* - docgen_migrate_files
*   Menjalankan proses migrasi file antar direktori
* 
* Actions:
* - None
* 
* Filters: 
* - None
* 
* Usage:
* $migration = DocGen_Implementation_Directory_Migration::get_instance();
* 
* // Check if migration needed
* $results = $migration->check_migration_needed($old_settings, $new_settings);
* 
* // Migrate files if needed
* if ($results['has_changes']) {
*    $migration->migrate_directory($from, $to);
* }
* 
* Security:
* - Nonce validation untuk semua AJAX requests
* - Permission checking (manage_options)
* - Path traversal prevention
* - Secure file operations
* 
* Notes:
* - Singleton pattern untuk instance management
* - Requires write permission pada direktori tujuan
* - Handles file permission secara otomatis
* - Maintains audit trail melalui logging (jika debug mode aktif)
*/

if (!defined('ABSPATH')) {
   die('Direct access not permitted.');
}
class DocGen_Implementation_Directory_Migration {
    private static $instance = null;
    private $dir_handler;
    
    private function __construct() {
        $this->dir_handler = new DocGen_Implementation_Directory_Handler();
        
        add_action('wp_ajax_docgen_check_migration', array($this, 'ajax_check_migration'));
        add_action('wp_ajax_docgen_migrate_files', array($this, 'ajax_migrate_files'));
    }
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Check if migration needed with enhanced validation
     * @param array $old_settings Old settings
     * @param array $new_settings New settings yang akan disimpan
     * @return array Migration info
     */
    public function check_migration_needed($old_settings, $new_settings) {
        $changes = array();
        $upload_dir = wp_upload_dir();
        $upload_base = $upload_dir['basedir'];
        
        // Normalize paths for comparison
        $old_temp = $this->normalize_path($old_settings['temp_dir']);
        $new_temp = $this->normalize_path(trailingslashit($upload_base) . basename($new_settings['temp_dir']));
        $old_template = $this->normalize_path($old_settings['template_dir']);
        $new_template = $this->normalize_path(trailingslashit($upload_base) . basename($new_settings['template_dir']));
        
        // Check template directory changes
        if ($old_template !== $new_template) {
            $changes['template'] = array(
                'from' => $old_template,
                'to' => $new_template,
                'files' => $this->count_files($old_template),
                'valid' => $this->validate_path($new_template)
            );
        }
        
        // Check temp directory changes
        if ($old_temp !== $new_temp) {
            $changes['temp'] = array(
                'from' => $old_temp,
                'to' => $new_temp,
                'files' => $this->count_files($old_temp),
                'valid' => $this->validate_path($new_temp)
            );
        }
        
        return array(
            'has_changes' => !empty($changes),
            'changes' => $changes,
            'upload_base' => $upload_base
        );
    }

    /**
     * Normalize path for consistent comparison
     * @param string $path Path to normalize
     * @return string Normalized path
     */
    private function normalize_path($path) {
        return str_replace('\\', '/', rtrim($path, '/\\'));
    }

    /**
     * Validate if path is within uploads directory
     * @param string $path Path to validate
     * @return bool Valid path or not
     */
    private function validate_path($path) {
        $upload_dir = wp_upload_dir();
        $upload_base = $this->normalize_path($upload_dir['basedir']);
        $check_path = $this->normalize_path($path);
        
        return strpos($check_path, $upload_base) === 0;
    }

    /**
     * Count files in directory recursively with enhanced filtering
     * @param string $dir Directory path
     * @return int File count
     */
    private function count_files($dir) {
        if (!is_dir($dir)) {
            return 0;
        }
        
        $count = 0;
        $excluded_files = array('.htaccess', 'index.php', '.', '..');
        
        try {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($files as $file) {
                if ($file->isFile() && !in_array($file->getBasename(), $excluded_files)) {
                    $count++;
                }
            }
        } catch (Exception $e) {
            error_log('DocGen Directory Count Error: ' . $e->getMessage());
        }
        
        return $count;
    }

    /**
     * AJAX handler for migration check with enhanced validation
     */
    public function ajax_check_migration() {
        check_ajax_referer('docgen_migration_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $upload_dir = wp_upload_dir();
        $upload_base = $upload_dir['basedir'];
        
        $old_settings = get_option('docgen_implementation_settings', array());
        $new_settings = array(
            'template_dir' => trailingslashit($upload_base) . basename(sanitize_text_field($_POST['template_dir'])),
            'temp_dir' => trailingslashit($upload_base) . basename(sanitize_text_field($_POST['temp_dir']))
        );
        
        $migration_info = $this->check_migration_needed($old_settings, $new_settings);
        wp_send_json_success($migration_info);
    }

    /**
     * AJAX handler untuk proses migrasi with enhanced error handling
     */
    public function ajax_migrate_files() {
        check_ajax_referer('docgen_migration_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $upload_dir = wp_upload_dir();
        $upload_base = $upload_dir['basedir'];
        
        $old_settings = get_option('docgen_implementation_settings', array());
        $new_settings = array(
            'template_dir' => trailingslashit($upload_base) . basename(sanitize_text_field($_POST['template_dir'])),
            'temp_dir' => trailingslashit($upload_base) . basename(sanitize_text_field($_POST['temp_dir']))
        );
        
        $results = array();
        
        // Migrate template directory
        if ($old_settings['template_dir'] !== $new_settings['template_dir']) {
            $results['template'] = $this->migrate_directory(
                $old_settings['template_dir'],
                $new_settings['template_dir']
            );
            
            // Log migration result
            if (!$results['template']['success']) {
                error_log('DocGen Template Migration Error: ' . print_r($results['template']['errors'], true));
            }
        }
        
        // Migrate temp directory
        if ($old_settings['temp_dir'] !== $new_settings['temp_dir']) {
            $results['temp'] = $this->migrate_directory(
                $old_settings['temp_dir'],
                $new_settings['temp_dir']
            );
            
            // Log migration result
            if (!$results['temp']['success']) {
                error_log('DocGen Temp Migration Error: ' . print_r($results['temp']['errors'], true));
            }
        }
        
        // Update settings after successful migration
        if ((!isset($results['template']) || $results['template']['success']) && 
            (!isset($results['temp']) || $results['temp']['success'])) {
            update_option('docgen_implementation_settings', $new_settings);
        }
        
        wp_send_json_success($results);
    }

    /**
     * Migrate files between directories with enhanced error handling
     * @param string $from Source directory
     * @param string $to Destination directory
     * @return array Migration results
     */
    private function migrate_directory($from, $to) {
        $results = array(
            'success' => true,
            'migrated' => 0,
            'skipped' => 0,
            'errors' => array()
        );
        
        // Validate paths
        if (!$this->validate_path($to)) {
            $results['success'] = false;
            $results['errors'][] = 'Invalid destination path: Not in uploads directory';
            return $results;
        }

        try {
            // Create destination if not exists
            if (!is_dir($to)) {
                if (!wp_mkdir_p($to)) {
                    $results['success'] = false;
                    $results['errors'][] = 'Failed to create destination directory';
                    return $results;
                }
                
                // Secure new directory
                $this->dir_handler->create_secure_directory(basename($to), dirname($to));
            }

            // If source exists and is different from destination
            if (is_dir($from) && $from !== $to) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($from, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($files as $file) {
                    $relative_path = str_replace($from, '', $file->getPathname());
                    $target = $to . $relative_path;

                    if ($file->isDir()) {
                        if (!is_dir($target)) {
                            wp_mkdir_p($target);
                        }
                        continue;
                    }

                    // Skip system files
                    if (in_array($file->getBasename(), array('.htaccess', 'index.php'))) {
                        $results['skipped']++;
                        continue;
                    }

                    // Create target directory if needed
                    wp_mkdir_p(dirname($target));

                    // Copy file with error handling
                    if (@copy($file->getPathname(), $target)) {
                        $results['migrated']++;
                        // Match permissions
                        @chmod($target, fileperms($file->getPathname()));
                    } else {
                        $results['errors'][] = 'Failed to copy: ' . $relative_path;
                    }
                }
            }
        } catch (Exception $e) {
            $results['success'] = false;
            $results['errors'][] = 'Migration error: ' . $e->getMessage();
            error_log('DocGen Migration Error: ' . $e->getMessage());
        }

        return $results;
    }
}
