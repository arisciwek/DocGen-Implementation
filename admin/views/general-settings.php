<?php
/**
 * General Settings View
 *
 * @package     DocGen_Implementation
 * @subpackage  Admin/Views
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: admin/views/general-settings.php
 * 
 * Description: Template untuk menampilkan form konfigurasi umum
 *              termasuk output format dan debug mode settings.
 *              File ini di-include dari class-settings-page.php
 * 
 * Dependencies:
 * - class-settings-page.php (parent)
 * 
 * Usage:
 * Dipanggil dari DocGen_Implementation_Settings_Page::render_general_settings()
 * 
 * Variables yang tersedia:
 * - $settings (array) Settings plugin dari database
 * - $this     (object) Instance dari class DocGen_Implementation_Settings_Page
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}
?>

<table class="form-table">
    <!-- Output Format -->
    <tr>
        <th scope="row"><?php echo esc_html__('Default Output Format', 'docgen-implementation'); ?></th>
        <td>
            <select name="output_format">
                <option value="docx" <?php selected($settings['output_format'] ?? 'docx', 'docx'); ?>>
                    <?php echo esc_html__('DOCX', 'docgen-implementation'); ?>
                </option>
                <option value="pdf" <?php selected($settings['output_format'] ?? 'docx', 'pdf'); ?>>
                    <?php echo esc_html__('PDF', 'docgen-implementation'); ?>
                </option>
            </select>
        </td>
    </tr>

    <!-- Debug Mode -->
    <tr>
        <th scope="row"><?php echo esc_html__('Debug Mode', 'docgen-implementation'); ?></th>
        <td>
            <label>
                <input type="checkbox" 
                       name="debug_mode" 
                       value="1" 
                       <?php checked($settings['debug_mode'] ?? false, true); ?> />
                <?php echo esc_html__('Enable debug mode', 'docgen-implementation'); ?>
            </label>
        </td>
    </tr>

        
    <tr>
        <th scope="row"><?php echo esc_html__('Clean Uninstall', 'docgen-implementation'); ?></th>
        <td>
            <label>
                <input type="checkbox" 
                       name="clean_uninstall" 
                       value="1" 
                       <?php checked($settings['clean_uninstall'] ?? false, true); ?> />
                <?php echo esc_html__('Remove all plugin data when uninstalling', 'docgen-implementation'); ?>
            </label>
            <p class="description">
                <?php echo esc_html__('This will delete all settings and generated files when plugin is deactivated.', 'docgen-implementation'); ?>
            </p>
        </td>
    </tr>
</table>
