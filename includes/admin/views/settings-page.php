<?php
/**
 * Admin settings page template.
 *
 * @since      1.0.0
 * @package    Filterly
 * @subpackage Filterly/admin/views
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get the current page
$current_page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'filterly';

// Get filter settings
$filter_settings = get_option( 'filterly_filter_settings', array() );

// Get general settings
$general_settings = get_option( 'filterly_general_settings', array() );

// Get advanced settings
$advanced_settings = get_option( 'filterly_advanced_settings', array() );

?>
<div class="wrap filterly-admin">
    <h1 class="wp-heading-inline"><?php _e( 'Filterly', 'filterly' ); ?></h1>
    
    <?php if ( $current_page === 'filterly' ) : ?>
        <a href="#" class="page-title-action filterly-add-filter"><?php _e( 'Add New Filter', 'filterly' ); ?></a>
    <?php endif; ?>
    
    <hr class="wp-header-end">
    
    <?php if ( $current_page === 'filterly' ) : ?>
        <!-- Filter Management Tab -->
        <div class="filterly-filters-container">
            <?php if ( empty( $filter_settings ) ) : ?>
                <div class="filterly-empty-state">
                    <div class="filterly-empty-icon">
                        <span class="dashicons dashicons-filter"></span>
                    </div>
                    <h2><?php _e( 'No Filters Found', 'filterly' ); ?></h2>
                    <p><?php _e( 'You haven\'t created any filters yet. Get started by adding your first filter!', 'filterly' ); ?></p>
                    <button class="button button-primary filterly-add-filter">
                        <?php _e( 'Create Your First Filter', 'filterly' ); ?>
                    </button>
                </div>
            <?php else : ?>
                <h2 class="filterly-section-title"><?php _e( 'Manage Filters', 'filterly' ); ?></h2>
                <p class="filterly-section-description">
                    <?php _e( 'Drag and drop to reorder filters. Click on any filter to edit its settings.', 'filterly' ); ?>
                </p>
                
                <ul class="filterly-filters-list" id="filterly-sortable">
                    <?php foreach ( $filter_settings as $filter ) : ?>
                        <?php if ( isset( $filter['id'], $filter['type'], $filter['label'] ) ) : ?>
                            <li class="filterly-filter-item" data-filter-id="<?php echo esc_attr( $filter['id'] ); ?>">
                                <div class="filterly-filter-header">
                                    <span class="filterly-filter-drag dashicons dashicons-menu"></span>
                                    <h3 class="filterly-filter-title"><?php echo esc_html( $filter['label'] ); ?></h3>
                                    <span class="filterly-filter-type"><?php echo esc_html( $this->get_filter_type_label( $filter['type'] ) ); ?></span>
                                    <div class="filterly-filter-actions">
                                        <button type="button" class="filterly-edit-filter button button-small">
                                            <?php _e( 'Edit', 'filterly' ); ?>
                                        </button>
                                        <button type="button" class="filterly-delete-filter button button-small">
                                            <?php _e( 'Delete', 'filterly' ); ?>
                                        </button>
                                    </div>
                                </div>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                
                <div class="filterly-filters-actions">
                    <button class="button button-primary filterly-add-filter">
                        <?php _e( 'Add New Filter', 'filterly' ); ?>
                    </button>
                    
                    <div class="filterly-import-export">
                        <button type="button" class="button filterly-export-filters">
                            <?php _e( 'Export Filters', 'filterly' ); ?>
                        </button>
                        <button type="button" class="button filterly-import-filters">
                            <?php _e( 'Import Filters', 'filterly' ); ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Shortcode Usage Info -->
            <div class="filterly-shortcode-info">
                <h3><?php _e( 'How to Use', 'filterly' ); ?></h3>
                <p>
                    <?php _e( 'Use the following shortcode to display your filters anywhere:', 'filterly' ); ?>
                </p>
                <div class="filterly-shortcode-example">
                    <code>[filterly post_type="post"]</code>
                </div>
                <p>
                    <?php _e( 'Optional attributes:', 'filterly' ); ?>
                </p>
                <ul>
                    <li><code>post_type</code> - <?php _e( 'The post type to filter (default: "post")', 'filterly' ); ?></li>
                    <li><code>filters</code> - <?php _e( 'Comma-separated list of filter IDs to include (default: all)', 'filterly' ); ?></li>
                    <li><code>show_count</code> - <?php _e( 'Whether to show result count (default: true)', 'filterly' ); ?></li>
                    <li><code>show_reset</code> - <?php _e( 'Whether to show reset button (default: true)', 'filterly' ); ?></li>
                    <li><code>ajax</code> - <?php _e( 'Whether to use AJAX filtering (default: true)', 'filterly' ); ?></li>
                </ul>
            </div>
        </div>
        
        <!-- Filter Form Modal -->
        <div class="filterly-modal" id="filterly-filter-modal">
            <div class="filterly-modal-content">
                <div class="filterly-modal-header">
                    <h2 class="filterly-modal-title"><?php _e( 'Filter Settings', 'filterly' ); ?></h2>
                    <button type="button" class="filterly-modal-close dashicons dashicons-no-alt"></button>
                </div>
                <div class="filterly-modal-body">
                    <form id="filterly-filter-form">
                        <input type="hidden" name="filter_id" id="filter_id" value="">
                        
                        <div class="filterly-form-row">
                            <label for="filter_label" class="filterly-form-label"><?php _e( 'Filter Label', 'filterly' ); ?>:</label>
                            <div class="filterly-form-field">
                                <input type="text" name="filter_label" id="filter_label" class="regular-text" required>
                                <p class="description"><?php _e( 'The display name for this filter.', 'filterly' ); ?></p>
                            </div>
                        </div>
                        
                        <div class="filterly-form-row">
                            <label for="filter_type" class="filterly-form-label"><?php _e( 'Filter Type', 'filterly' ); ?>:</label>
                            <div class="filterly-form-field">
                                <select name="filter_type" id="filter_type" class="regular-text" required>
                                    <option value=""><?php _e( 'Select filter type', 'filterly' ); ?></option>
                                    <option value="taxonomy"><?php _e( 'Taxonomy (Categories, Tags, etc.)', 'filterly' ); ?></option>
                                    <option value="meta"><?php _e( 'Custom Field', 'filterly' ); ?></option>
                                    <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                                        <option value="attribute"><?php _e( 'Product Attribute', 'filterly' ); ?></option>
                                        <option value="variation"><?php _e( 'Product Variation', 'filterly' ); ?></option>
                                    <?php endif; ?>
                                </select>
                                <p class="description"><?php _e( 'The type of data this filter will target.', 'filterly' ); ?></p>
                            </div>
                        </div>
                        
                        <!-- Taxonomy-specific options (shown when taxonomy type is selected) -->
                        <div class="filterly-form-row filterly-type-option filterly-type-taxonomy" style="display: none;">
                            <label for="filter_taxonomy" class="filterly-form-label"><?php _e( 'Taxonomy', 'filterly' ); ?>:</label>
                            <div class="filterly-form-field">
                                <select name="filter_taxonomy" id="filter_taxonomy" class="regular-text">
                                    <option value=""><?php _e( 'Select taxonomy', 'filterly' ); ?></option>
                                    <?php foreach ( $this->settings->get_taxonomies() as $taxonomy => $label ) : ?>
                                        <option value="<?php echo esc_attr( $taxonomy ); ?>">
                                            <?php echo esc_html( $label ); ?> (<?php echo esc_html( $taxonomy ); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Meta field-specific options (shown when meta type is selected) -->
                        <div class="filterly-form-row filterly-type-option filterly-type-meta" style="display: none;">
                            <label for="filter_meta_key" class="filterly-form-label"><?php _e( 'Meta Field', 'filterly' ); ?>:</label>
                            <div class="filterly-form-field">
                                <select name="filter_meta_key" id="filter_meta_key" class="regular-text">
                                    <option value=""><?php _e( 'Select meta key', 'filterly' ); ?></option>
                                    <?php foreach ( $this->settings->get_meta_keys() as $meta_key => $label ) : ?>
                                        <option value="<?php echo esc_attr( $meta_key ); ?>">
                                            <?php echo esc_html( $label ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e( 'Or enter custom meta key:', 'filterly' ); ?></p>
                                <input type="text" name="filter_meta_key_custom" id="filter_meta_key_custom" class="regular-text" placeholder="<?php esc_attr_e( 'Custom meta key', 'filterly' ); ?>">
                            </div>
                        </div>
                        
                        <!-- Product attribute-specific options (shown when attribute type is selected) -->
                        <div class="filterly-form-row filterly-type-option filterly-type-attribute" style="display: none;">
                            <label for="filter_attribute" class="filterly-form-label"><?php _e( 'Product Attribute', 'filterly' ); ?>:</label>
                            <div class="filterly-form-field">
                                <select name="filter_attribute" id="filter_attribute" class="regular-text">
                                    <option value=""><?php _e( 'Select attribute', 'filterly' ); ?></option>
                                    <?php foreach ( $this->settings->get_product_attributes() as $attribute => $label ) : ?>
                                        <option value="<?php echo esc_attr( $attribute ); ?>">
                                            <?php echo esc_html( $label ); ?> (<?php echo esc_html( $attribute ); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Product variation-specific options (shown when variation type is selected) -->
                        <div class="filterly-form-row filterly-type-option filterly-type-variation" style="display: none;">
                            <label for="filter_variation" class="filterly-form-label"><?php _e( 'Variation Attribute', 'filterly' ); ?>:</label>
                            <div class="filterly-form-field">
                                <select name="filter_variation" id="filter_variation" class="regular-text">
                                    <option value=""><?php _e( 'Select variation', 'filterly' ); ?></option>
                                    <?php foreach ( $this->settings->get_product_attributes() as $attribute => $label ) : ?>
                                        <option value="<?php echo esc_attr( $attribute ); ?>">
                                            <?php echo esc_html( $label ); ?> (<?php echo esc_html( $attribute ); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Common display options -->
                        <div class="filterly-form-row">
                            <label for="filter_display_type" class="filterly-form-label"><?php _e( 'Display Type', 'filterly' ); ?>:</label>
                            <div class="filterly-form-field">
                                <select name="filter_display_type" id="filter_display_type" class="regular-text">
                                    <option value="checkbox"><?php _e( 'Checkbox', 'filterly' ); ?></option>
                                    <option value="radio"><?php _e( 'Radio Button', 'filterly' ); ?></option>
                                    <option value="select"><?php _e( 'Dropdown', 'filterly' ); ?></option>
                                    <option value="button"><?php _e( 'Button', 'filterly' ); ?></option>
                                    <option value="color" class="filterly-display-option filterly-display-attribute"><?php _e( 'Color Swatch', 'filterly' ); ?></option>
                                    <option value="image" class="filterly-display-option filterly-display-attribute"><?php _e( 'Image', 'filterly' ); ?></option>
                                    <option value="range" class="filterly-display-option filterly-display-meta"><?php _e( 'Range Slider', 'filterly' ); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="filterly-form-row">
                            <label for="filter_multiple" class="filterly-form-label"><?php _e( 'Multiple Selection', 'filterly' ); ?>:</label>
                            <div class="filterly-form-field">
                                <input type="checkbox" name="filter_multiple" id="filter_multiple" value="1" checked>
                                <span class="description"><?php _e( 'Allow users to select multiple values.', 'filterly' ); ?></span>
                            </div>
                        </div>
                        
                        <div class="filterly-form-row">
                            <label for="filter_show_count" class="filterly-form-label"><?php _e( 'Show Counts', 'filterly' ); ?>:</label>
                            <div class="filterly-form-field">
                                <input type="checkbox" name="filter_show_count" id="filter_show_count" value="1" checked>
                                <span class="description"><?php _e( 'Show number of items for each option.', 'filterly' ); ?></span>
                            </div>
                        </div>
                        
                        <!-- Taxonomy specific options -->
                        <div class="filterly-form-row filterly-type-option filterly-type-taxonomy" style="display: none;">
                            <label for="filter_hierarchical" class="filterly-form-label"><?php _e( 'Hierarchical', 'filterly' ); ?>:</label>
                            <div class="filterly-form-field">
                                <input type="checkbox" name="filter_hierarchical" id="filter_hierarchical" value="1" checked>
                                <span class="description"><?php _e( 'Display terms in hierarchical structure.', 'filterly' ); ?></span>
                            </div>
                        </div>
                        
                        <div class="filterly-form-row filterly-type-option filterly-type-taxonomy" style="display: none;">
                            <label for="filter_operator" class="filterly-form-label"><?php _e( 'Query Operator', 'filterly' ); ?>:</label>
                            <div class="filterly-form-field">
                                <select name="filter_operator" id="filter_operator" class="regular-text">
                                    <option value="IN"><?php _e( 'IN - Items in any selected category', 'filterly' ); ?></option>
                                    <option value="AND"><?php _e( 'AND - Items in all selected categories', 'filterly' ); ?></option>
                                    <option value="NOT IN"><?php _e( 'NOT IN - Items not in selected categories', 'filterly' ); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Meta specific options -->
                        <div class="filterly-form-row filterly-type-option filterly-type-meta" style="display: none;">
                            <label for="filter_data_type" class="filterly-form-label"><?php _e( 'Data Type', 'filterly' ); ?>:</label>
                            <div class="filterly-form-field">
                                <select name="filter_data_type" id="filter_data_type" class="regular-text">
                                    <option value="string"><?php _e( 'String (text)', 'filterly' ); ?></option>
                                    <option value="numeric"><?php _e( 'Numeric (numbers)', 'filterly' ); ?></option>
                                    <option value="date"><?php _e( 'Date', 'filterly' ); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Range slider specific options (shown when range display is selected) -->
                        <div class="filterly-form-row filterly-display-option filterly-display-range" style="display: none;">
                            <label for="filter_range_min" class="filterly-form-label"><?php _e( 'Range Minimum', 'filterly' ); ?>:</label>
                            <div class="filterly-form-field">
                                <input type="number" name="filter_range_min" id="filter_range_min" class="small-text" value="0">
                            </div>
                        </div>
                        
                        <div class="filterly-form-row filterly-display-option filterly-display-range" style="display: none;">
                            <label for="filter_range_max" class="filterly-form-label"><?php _e( 'Range Maximum', 'filterly' ); ?>:</label>
                            <div class="filterly-form-field">
                                <input type="number" name="filter_range_max" id="filter_range_max" class="small-text" value="100">
                            </div>
                        </div>
                        
                        <div class="filterly-form-row filterly-display-option filterly-display-range" style="display: none;">
                            <label for="filter_range_step" class="filterly-form-label"><?php _e( 'Range Step', 'filterly' ); ?>:</label>
                            <div class="filterly-form-field">
                                <input type="number" name="filter_range_step" id="filter_range_step" class="small-text" value="1" min="0.01" step="0.01">
                            </div>
                        </div>
                        
                    </form>
                </div>
                <div class="filterly-modal-footer">
                    <div class="filterly-preview-container">
                        <h3><?php _e( 'Filter Preview', 'filterly' ); ?></h3>
                        <div id="filterly-filter-preview">
                            <div class="filterly-preview-placeholder">
                                <?php _e( 'Complete the form to see a preview', 'filterly' ); ?>
                            </div>
                        </div>
                    </div>
                    <div class="filterly-modal-actions">
                        <button type="button" class="button filterly-modal-cancel"><?php _e( 'Cancel', 'filterly' ); ?></button>
                        <button type="button" class="button button-primary filterly-save-filter"><?php _e( 'Save Filter', 'filterly' ); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Import/Export Modal -->
        <div class="filterly-modal" id="filterly-import-modal">
            <div class="filterly-modal-content">
                <div class="filterly-modal-header">
                    <h2 class="filterly-modal-title"><?php _e( 'Import/Export Filters', 'filterly' ); ?></h2>
                    <button type="button" class="filterly-modal-close dashicons dashicons-no-alt"></button>
                </div>
                <div class="filterly-modal-body">
                    <div class="filterly-tabs">
                        <ul class="filterly-tabs-nav">
                            <li><a href="#filterly-export-tab"><?php _e( 'Export', 'filterly' ); ?></a></li>
                            <li><a href="#filterly-import-tab"><?php _e( 'Import', 'filterly' ); ?></a></li>
                        </ul>
                        
                        <div id="filterly-export-tab" class="filterly-tab-content">
                            <p><?php _e( 'Copy this JSON data to back up or transfer your filter settings:', 'filterly' ); ?></p>
                            <textarea id="filterly-export-data" rows="10" class="widefat" readonly></textarea>
                            <p>
                                <button type="button" class="button button-secondary filterly-copy-export">
                                    <?php _e( 'Copy to Clipboard', 'filterly' ); ?>
                                </button>
                                <span class="filterly-copy-status" style="display:none;"></span>
                            </p>
                        </div>
                        
                        <div id="filterly-import-tab" class="filterly-tab-content">
                            <p><?php _e( 'Paste exported JSON data to import filter settings:', 'filterly' ); ?></p>
                            <textarea id="filterly-import-data" rows="10" class="widefat"></textarea>
                            <p class="filterly-warning">
                                <?php _e( 'Warning: Importing will replace all existing filter settings.', 'filterly' ); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="filterly-modal-footer">
                    <div class="filterly-import-message"></div>
                    <div class="filterly-modal-actions">
                        <button type="button" class="button filterly-modal-cancel"><?php _e( 'Close', 'filterly' ); ?></button>
                        <button type="button" id="filterly-do-import" class="button button-primary"><?php _e( 'Import', 'filterly' ); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
    <?php elseif ( $current_page === 'filterly-settings' ) : ?>
        <!-- Settings Tab -->
        <form method="post" action="options.php" class="filterly-settings-form">
            <?php
            settings_fields( 'filterly_settings' );
            do_settings_sections( 'filterly_settings' );
            submit_button();
            ?>
        </form>
        
    <?php elseif ( $current_page === 'filterly-advanced' ) : ?>
        <!-- Advanced Settings Tab -->
        <form method="post" action="options.php" class="filterly-settings-form">
            <?php
            settings_fields( 'filterly_advanced_settings' );
            do_settings_sections( 'filterly_settings' );
            submit_button();
            ?>
        </form>
        
    <?php endif; ?>
</div>