<?php
/**
 * Directory Settings View
 *
 * @package     DocGen_Implementation
 * @subpackage  Admin/Views
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: admin/views/directory-settings.php
 * 
 * Description: Template untuk menampilkan form konfigurasi direktori
 *              termasuk temporary directory dan template directory settings.
 *              File ini di-include dari class-settings-page.php
 * 
 * Dependencies:
 * - class-settings-page.php (parent)
 * - class-directory-handler.php (untuk directory testing)
 * 
 * Usage:
 * Dipanggil dari DocGen_Implementation_Settings_Page::render_directory_settings()
 * 
 * Variables yang tersedia:
 * - $settings (array) Settings plugin dari database
 * - $this     (object) Instance dari class DocGen_Implementation_Settings_Page
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

// Get required base directories
$upload_dir = wp_upload_dir();
$upload_base = $upload_dir['basedir'];
$content_base = $this->get_content_base_dir();

// Get paths from settings
$temp_dir = $settings['temp_dir'] ?? trailingslashit($upload_base) . 'docgen-temp';
$template_dir = $settings['template_dir'] ?? trailingslashit($upload_base) . 'docgen-templates';

?>

<!-- try to fix this -->
<?php if (isset($adapter) && method_exists($adapter, 'get_docgen_temp_path')): ?>
    <code class="base-path"><?php echo esc_html($adapter->get_docgen_temp_path()); ?>/</code>
<?php else: ?>
    <code class="base-path"><?php echo esc_html($upload_base); ?>/</code>
<?php endif; ?>



<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
    <input type="hidden" name="action" value="host_docgen_save_directory">
    <input type="hidden" name="redirect_page" value="<?php echo esc_attr($_GET['page']); ?>">
    <?php wp_nonce_field('docgen_directory_settings', 'docgen_directory_nonce'); ?>


<table class="form-table">
    <!-- Temporary Directory -->
    <tr>
        <th scope="row"><?php echo esc_html__('Temporary Directory', 'docgen-implementation'); ?></th>
        <td>
            <div class="template-dir-input">
                <code class="base-path"><?php echo esc_html($upload_base); ?>/</code>
                <input type="text" 
                       name="temp_dir" 
                       value="<?php echo esc_attr(basename($settings['temp_dir'] ?? 'docgen-temp')); ?>"
                       class="regular-text folder-name" 
                       placeholder="docgen-temp" 
                       style="width: 200px;" />
            </div>
            
            <p class="description"><?php echo esc_html__('Directory for temporary files. Must be writable.', 'docgen-implementation'); ?></p>
            <p class="directory-actions">
                <button type="button" id="test-directory-btn" class="button">
                    <?php echo esc_html__('Test Directory', 'docgen-implementation'); ?>
                    <span class="spinner"></span>
                </button>
                <button type="submit" name="cleanup_temp" class="button">
                    <?php echo esc_html__('Cleanup Temp Files', 'docgen-implementation'); ?>
                </button>
            </p>
            <div id="test-directory-result"></div>
            <div class="temp-dir-status"></div>
        </td>
    </tr>

    <!-- Template Directory -->
    <tr>
        <th scope="row"><?php echo esc_html__('Template Directory', 'docgen-implementation'); ?></th>
        <td>
            <div class="template-dir-input">
                <code class="base-path"><?php echo esc_html($content_base); ?></code>
                <input type="text" 
                       name="template_dir" 
                       value="<?php echo esc_attr(basename($settings['template_dir'] ?? 'docgen-templates')); ?>"
                       class="regular-text folder-name" 
                       placeholder="docgen-templates" 
                       style="width: 200px;" />
            </div>
            
            <p class="description"><?php echo esc_html__('Directory for template files (DOCX/ODT).', 'docgen-implementation'); ?></p>
            <p class="directory-actions">
                <button type="button" id="test-template-dir-btn" class="button">
                    <?php echo esc_html__('Test Template Directory', 'docgen-implementation'); ?>
                    <span class="spinner"></span>
                </button>
            </p>
            <div id="test-template-dir-result"></div>
            <div class="template-dir-status"></div>
        </td>
    </tr>
        <?php 
        // Hook untuk plugin lain menambahkan field tambahan
        do_action('docgen_implementation_directory_settings_fields', $settings); 
        ?>
    </table>

    <?php submit_button(__('Save Directory Settings', 'docgen-implementation')); ?>
</form>
