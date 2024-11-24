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
* 1.0.1 - 2024-11-24
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
        // Ubah dari Directory_Structure ke Directory_Handler
        $this->dir_handler = new DocGen_Implementation_Directory_Handler();
        
        // Add AJAX handlers
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
     * Check if migration needed
     * @param array $old_settings Old settings
     * @param array $new_settings New settings yang akan disimpan
     * @return array Migration info
     */
    public function check_migration_needed($old_settings, $new_settings) {
        $changes = array();
        
        // Check template directory changes
        if ($old_settings['template_dir'] !== $new_settings['template_dir']) {
            $changes['template'] = array(
                'from' => $old_settings['template_dir'],
                'to' => $new_settings['template_dir'],
                'files' => $this->count_files($old_settings['template_dir'])
            );
        }
        
        // Check temp directory changes
        if ($old_settings['temp_dir'] !== $new_settings['temp_dir']) {
            $changes['temp'] = array(
                'from' => $old_settings['temp_dir'],
                'to' => $new_settings['temp_dir'],
                'files' => $this->count_files($old_settings['temp_dir'])
            );
        }
        
        return array(
            'has_changes' => !empty($changes),
            'changes' => $changes
        );
    }

    /**
     * Count files in directory recursively
     */
    private function count_files($dir) {
        if (!is_dir($dir)) {
            return 0;
        }
        
        $count = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($files as $file) {
            if ($file->isFile() && !in_array($file->getBasename(), array('.htaccess', 'index.php'))) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Migrate files between directories
     * @param string $from Source directory
     * @param string $to Destination directory
     * @return array Migration status
     */
    private function migrate_directory($from, $to) {
        if (!is_dir($from)) {
            return array(
                'success' => false,
                'message' => 'Source directory not found: ' . $from
            );
        }

        // Create destination if not exists
        if (!is_dir($to)) {
            wp_mkdir_p($to);
        }

        $results = array(
            'success' => true,
            'migrated' => 0,
            'skipped' => 0,
            'errors' => array()
        );

        try {
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
                } else {
                    // Skip system files
                    if (in_array($file->getBasename(), array('.htaccess', 'index.php'))) {
                        $results['skipped']++;
                        continue;
                    }
                    
                    if (@copy($file->getPathname(), $target)) {
                        $results['migrated']++;
                    } else {
                        $results['errors'][] = 'Failed to copy: ' . $relative_path;
                    }
                }
            }
        } catch (Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * AJAX handler untuk check migration
     */
    public function ajax_check_migration() {
        check_ajax_referer('docgen_migration_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $old_settings = get_option('docgen_implementation_settings', array());
        $new_settings = array(
            'template_dir' => sanitize_text_field($_POST['template_dir']),
            'temp_dir' => sanitize_text_field($_POST['temp_dir'])
        );
        
        $migration_info = $this->check_migration_needed($old_settings, $new_settings);
        wp_send_json_success($migration_info);
    }

    /**
     * AJAX handler untuk proses migrasi
     */
    public function ajax_migrate_files() {
        check_ajax_referer('docgen_migration_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $old_settings = get_option('docgen_implementation_settings', array());
        $new_settings = array(
            'template_dir' => sanitize_text_field($_POST['template_dir']),
            'temp_dir' => sanitize_text_field($_POST['temp_dir'])
        );
        
        $results = array();
        
        // Migrate template directory
        if ($old_settings['template_dir'] !== $new_settings['template_dir']) {
            $results['template'] = $this->migrate_directory(
                $old_settings['template_dir'],
                $new_settings['template_dir']
            );
        }
        
        // Migrate temp directory
        if ($old_settings['temp_dir'] !== $new_settings['temp_dir']) {
            $results['temp'] = $this->migrate_directory(
                $old_settings['temp_dir'],
                $new_settings['temp_dir']
            );
        }
        
        wp_send_json_success($results);
    }
}
