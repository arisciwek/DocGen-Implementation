<?php
/**
 * Directory Handler Class
 *
 * @package     DocGen_Implementation
 * @subpackage  Admin
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: admin/class-directory-handler.php
 * 
 * Description: Utility class untuk menangani operasi directory.
 *              Handles directory testing, creation, cleanup,
 *              dan file management tasks.
 *              Menyediakan fungsi-fungsi helper untuk validasi path,
 *              scanning template, statistik directory dan manajemen file.
 * 
 * Changelog:
 * 1.0.0 - 2024-11-24
 * - Initial implementation with comprehensive directory management
 * - Added validation and security checks
 * - Added template scanning and validation
 * - Added directory statistics and cleanup utilities
 * - Added scheduled cleanup tasks
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

class DocGen_Implementation_Directory_Handler {
    /**
     * Maximum age for temp files in hours
     * @var int
     */
    private $max_temp_age = 24;

    /**
     * Allowed file extensions
     * @var array
     */
    private $allowed_extensions = array('docx', 'odt');

    /**
     * Constructor
     */
    public function __construct() {
        // Schedule cleanup jika belum
        if (!wp_next_scheduled('docgen_implementation_cleanup_temp')) {
            wp_schedule_event(time(), 'daily', 'docgen_implementation_cleanup_temp');
        }
        
        add_action('docgen_implementation_cleanup_temp', array($this, 'cleanup_temp_files'));
    }

    /**
     * Validate directory path
     * @param string $path Directory path to validate
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_directory_path($path) {
        // Check for directory traversal attempts
        if (strpos($path, '..') !== false) {
            return new WP_Error(
                'invalid_path',
                __('Invalid directory path detected', 'docgen-implementation')
            );
        }

        // Check if path is absolute
        if (!path_is_absolute($path)) {
            return new WP_Error(
                'relative_path',
                __('Directory path must be absolute', 'docgen-implementation')
            );
        }

        // Check if path is within WordPress directory
        $wp_root = untrailingslashit(ABSPATH);
        if (strpos($path, $wp_root) !== 0) {
            return new WP_Error(
                'outside_wordpress',
                __('Directory must be within WordPress installation', 'docgen-implementation')
            );
        }

        return true;
    }

    /**
     * Create directory with proper permissions
     * @param string $path Directory path
     * @param int $permissions Directory permissions (octal)
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function create_directory($path, $permissions = 0755) {
        // Validate path first
        $validation = $this->validate_directory_path($path);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Create directory if it doesn't exist
        if (!file_exists($path)) {
            if (!wp_mkdir_p($path)) {
                return new WP_Error(
                    'create_failed',
                    __('Failed to create directory', 'docgen-implementation')
                );
            }

            // Set directory permissions
            if (!chmod($path, $permissions)) {
                return new WP_Error(
                    'chmod_failed',
                    __('Failed to set directory permissions', 'docgen-implementation')
                );
            }
        }

        return true;
    }

    /**
     * Get directory size
     * @param string $path Directory path
     * @return int|WP_Error Size in bytes or WP_Error
     */
    public function get_directory_size($path) {
        if (!is_dir($path)) {
            return new WP_Error(
                'invalid_directory',
                __('Invalid directory', 'docgen-implementation')
            );
        }

        $size = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Get directory statistics
     * @param string $path Directory path
     * @return array|WP_Error Directory stats or error
     */
    public function get_directory_stats($path) {
        if (!is_dir($path)) {
            return new WP_Error(
                'invalid_directory',
                __('Invalid directory', 'docgen-implementation')
            );
        }

        $stats = array(
            'total_files' => 0,
            'total_size' => 0,
            'last_modified' => 0,
            'is_writable' => is_writable($path),
            'free_space' => disk_free_space($path),
            'by_extension' => array()
        );

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $stats['total_files']++;
                $stats['total_size'] += $file->getSize();
                $stats['last_modified'] = max($stats['last_modified'], $file->getMTime());

                $ext = strtolower($file->getExtension());
                if (!isset($stats['by_extension'][$ext])) {
                    $stats['by_extension'][$ext] = 0;
                }
                $stats['by_extension'][$ext]++;
            }
        }

        return $stats;
    }

    /**
     * Scan for template files
     * @param string $path Directory path
     * @return array|WP_Error Array of template files or error
     */
    public function scan_template_files($path) {
        if (!is_dir($path)) {
            return new WP_Error(
                'invalid_directory',
                __('Invalid directory', 'docgen-implementation')
            );
        }

        $templates = array();
        
        foreach ($this->allowed_extensions as $ext) {
            $files = glob($path . '/*.' . $ext);
            if ($files) {
                foreach ($files as $file) {
                    $templates[] = array(
                        'name' => basename($file),
                        'path' => $file,
                        'size' => filesize($file),
                        'modified' => filemtime($file),
                        'type' => $ext,
                        'is_valid' => $this->validate_template_file($file)
                    );
                }
            }
        }

        return $templates;
    }

    /**
     * Validate template file
     * @param string $file File path
     * @return bool True if valid template
     */
    public function validate_template_file($file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $this->allowed_extensions)) {
            return false;
        }

        // Check if file is a valid zip archive (DOCX/ODT are zip files)
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            return false;
        }

        // Check for required files based on type
        $required_files = ($ext === 'docx') ? 
            array('[Content_Types].xml', 'word/document.xml') :
            array('META-INF/manifest.xml', 'content.xml');

        foreach ($required_files as $required) {
            if ($zip->locateName($required) === false) {
                $zip->close();
                return false;
            }
        }

        $zip->close();
        return true;
    }

    /**
     * Clean directory
     * @param string $path Directory path
     * @param array $options Cleanup options
     * @return bool|WP_Error True on success or error
     */
    public function clean_directory($path, $options = array()) {
        $defaults = array(
            'older_than' => 24, // hours
            'extensions' => array(), // empty = all files
            'keep_latest' => 5, // number of latest files to keep
            'recursive' => false
        );

        $options = wp_parse_args($options, $defaults);

        if (!is_dir($path)) {
            return new WP_Error(
                'invalid_directory',
                __('Invalid directory', 'docgen-implementation')
            );
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            $options['recursive'] ? RecursiveIteratorIterator::SELF_FIRST : RecursiveIteratorIterator::LEAVES_ONLY
        );

        $deleted = 0;
        $failed = 0;
        $skipped = 0;

        // Get files list with timestamps
        $file_list = array();
        foreach ($files as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (!empty($options['extensions']) && !in_array($ext, $options['extensions'])) {
                    continue;
                }
                $file_list[$file->getPathname()] = $file->getMTime();
            }
        }

        // Sort by modified time, newest first
        arsort($file_list);

        // Keep latest files if specified
        if ($options['keep_latest'] > 0) {
            $file_list = array_slice($file_list, $options['keep_latest']);
        }

        // Process remaining files
        foreach ($file_list as $file => $mtime) {
            // Skip if file is newer than specified age
            if ((time() - $mtime) < ($options['older_than'] * 3600)) {
                $skipped++;
                continue;
            }

            if (@unlink($file)) {
                $deleted++;
            } else {
                $failed++;
            }
        }

        return array(
            'deleted' => $deleted,
            'failed' => $failed,
            'skipped' => $skipped
        );
    }
}
