<?php
/**
 * Template Upload Settings View
 *
 * @package     DocGen_Implementation
 * @subpackage  Admin/Views
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: admin/views/template-settings.php
 * 
 * Description: Template untuk menampilkan form upload template
 *              dan daftar template yang sudah ada.
 *              File ini di-include dari class-settings-page.php
 * 
 * Dependencies:
 * - class-settings-page.php (parent)
 * - class-directory-handler.php (untuk scan template)
 * 
 * Usage:
 * Dipanggil dari DocGen_Implementation_Settings_Page::render_template_settings()
 * 
 * Variables yang tersedia:
 * - $settings (array) Settings plugin dari database
 * - $this     (object) Instance dari class DocGen_Implementation_Settings_Page
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}
?>

<div class="template-upload-tab">
    <div class="card">
        <h2><?php echo esc_html__('Template Upload', 'docgen-implementation'); ?></h2>
        
        <!-- Template directory info -->
        <p><strong><?php echo esc_html__('Template Directory:', 'docgen-implementation'); ?></strong> 
        <?php echo esc_html($settings['template_dir']); ?></p>

        <!-- Upload area -->
        <div class="template-upload-area">
            <input type="file" name="template_file" accept=".docx,.odt" />
            <p class="description"><?php echo esc_html__('Supported formats: DOCX, ODT', 'docgen-implementation'); ?></p>
        </div>

        <?php $this->render_template_list($settings['template_dir']); ?>
        
    </div>
</div>
