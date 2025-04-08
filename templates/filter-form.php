<?php
/**
 * Template for rendering the filter form.
 *
 * @since      1.0.0
 * @package    Filterly
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Variables expected to be available:
// $filter_ids, $selected_values, $form_atts, $target_id, $attributes
?>

<div class="filterly-container">
    <form <?php foreach ( $form_atts as $attr_key => $attr_val ) : ?>
        <?php echo esc_attr( $attr_key ) . '="' . esc_attr( $attr_val ) . '" '; ?>
    <?php endforeach; ?>>

        <div class="filterly-filters">
            <?php foreach ( $filter_ids as $filter_id ) : ?>
                <?php if ( isset( $this->filters[$filter_id] ) ) : 
                    $filter = $this->filters[$filter_id];
                    $current_values = isset( $selected_values[$filter_id] ) ? $selected_values[$filter_id] : array();
                    $filter_choices = $filter->get_choices();
                    
                    if ( empty( $filter_choices ) ) {
                        continue;
                    }
                ?>
                <div class="filterly-filter-group" data-filter-id="<?php echo esc_attr( $filter_id ); ?>">
                    <h4 class="filterly-filter-title"><?php echo esc_html( $filter->get_label() ); ?></h4>
                    
                    <div class="filterly-filter-content">
                        <?php 
                        $filter_options = $filter->get_options();
                        $display_type = isset( $filter_options['display_type'] ) ? $filter_options['display_type'] : 'checkbox';
                        
                        // Special handling for different display types
                        if ( $filter instanceof Filterly_Meta_Filter && $display_type === 'range' ) {
                            echo $filter->render_range_slider( $current_values );
                        } elseif ( $filter instanceof Filterly_Attribute_Filter && $display_type === 'color' ) {
                            echo $filter->render_color_swatches( $filter_choices, $current_values );
                        } elseif ( $filter instanceof Filterly_Attribute_Filter && $display_type === 'image' ) {
                            echo $filter->render_image_swatches( $filter_choices, $current_values );
                        } else {
                            // Standard display types (checkbox, radio, select)
                            switch ( $display_type ) {
                                case 'select':
                                case 'multiselect':
                                    $multiple = ( $display_type === 'multiselect' ) ? ' multiple' : '';
                                    ?>
                                    <select name="filterly[<?php echo esc_attr( $filter_id ); ?>][]" class="filterly-select"<?php echo $multiple; ?>>
                                        <option value=""><?php esc_html_e( 'Select option', 'filterly' ); ?></option>
                                        <?php foreach ( $filter_choices as $choice ) : ?>
                                            <option value="<?php echo esc_attr( $choice['slug'] ?? $choice['id'] ); ?>" 
                                                <?php selected( in_array( $choice['slug'] ?? $choice['id'], $current_values ) ); ?>>
                                                <?php echo esc_html( $choice['name'] ); ?>
                                                <?php if ( $filter_options['show_count'] && isset( $choice['count'] ) ) : ?>
                                                    (<?php echo esc_html( $choice['count'] ); ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php
                                    break;

                                case 'radio':
                                    ?>
                                    <div class="filterly-radio-list">
                                        <?php foreach ( $filter_choices as $choice ) : 
                                            $choice_id = $filter_id . '-' . sanitize_title( $choice['name'] );
                                            $choice_val = $choice['slug'] ?? $choice['id'];
                                            ?>
                                            <div class="filterly-radio-item">
                                                <input type="radio" id="<?php echo esc_attr( $choice_id ); ?>" 
                                                    name="filterly[<?php echo esc_attr( $filter_id ); ?>][]" 
                                                    value="<?php echo esc_attr( $choice_val ); ?>" 
                                                    <?php checked( in_array( $choice_val, $current_values ) ); ?>>
                                                <label for="<?php echo esc_attr( $choice_id ); ?>">
                                                    <?php echo esc_html( $choice['name'] ); ?>
                                                    <?php if ( $filter_options['show_count'] && isset( $choice['count'] ) ) : ?>
                                                        <span class="count">(<?php echo esc_html( $choice['count'] ); ?>)</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php
                                    break;

                                case 'checkbox':
                                default:
                                    ?>
                                    <div class="filterly-checkbox-list">
                                        <?php foreach ( $filter_choices as $choice ) : 
                                            $choice_id = $filter_id . '-' . sanitize_title( $choice['name'] );
                                            $choice_val = $choice['slug'] ?? $choice['id'];
                                            ?>
                                            <div class="filterly-checkbox-item">
                                                <input type="checkbox" id="<?php echo esc_attr( $choice_id ); ?>" 
                                                    name="filterly[<?php echo esc_attr( $filter_id ); ?>][]" 
                                                    value="<?php echo esc_attr( $choice_val ); ?>" 
                                                    <?php checked( in_array( $choice_val, $current_values ) ); ?>>
                                                <label for="<?php echo esc_attr( $choice_id ); ?>">
                                                    <?php echo esc_html( $choice['name'] ); ?>
                                                    <?php if ( $filter_options['show_count'] && isset( $choice['count'] ) ) : ?>
                                                        <span class="count">(<?php echo esc_html( $choice['count'] ); ?>)</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php
                                    break;
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="filterly-actions">
            <?php if ( $attributes['reset_button'] === 'yes' ) : ?>
                <button type="reset" class="filterly-reset"><?php esc_html_e( 'Reset Filters', 'filterly' ); ?></button>
            <?php endif; ?>
            
            <?php if ( $attributes['ajax'] !== 'yes' ) : ?>
                <button type="submit" class="filterly-submit"><?php esc_html_e( 'Apply Filters', 'filterly' ); ?></button>
            <?php endif; ?>
        </div>

    </form>
</div>