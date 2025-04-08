<?php
/**
 * WooCommerce product attribute filter type implementation.
 *
 * @since      1.0.0
 * @package    Filterly
 * @subpackage Filterly/includes/filters
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Check if WooCommerce is active before defining the class
 */
if ( ! class_exists( 'WooCommerce' ) ) {
    return;
}

/**
 * WooCommerce attribute filter class.
 */
class Filterly_Attribute_Filter extends Filterly_Filter_Base {

    /**
     * The attribute name.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $attribute    The product attribute name.
     */
    protected $attribute;

    /**
     * Initialize the attribute filter.
     *
     * @since    1.0.0
     * @param    string    $attribute    The product attribute (taxonomy).
     * @param    string    $label        The filter display label (optional).
     * @param    array     $options      Additional filter options.
     */
    public function __construct( $attribute, $label = '', $options = array() ) {
        $this->attribute = wc_sanitize_taxonomy_name( $attribute );
        $this->type = 'attribute';
        
        // Use the attribute label if no custom label is provided
        if ( empty( $label ) ) {
            $attribute_obj = wc_get_attribute( wc_attribute_taxonomy_id_by_name( $attribute ) );
            $label = $attribute_obj ? $attribute_obj->name : $attribute;
        }
        
        parent::__construct( 'pa_' . $attribute, $label, $options );
    }

    /**
     * Get the default options for this filter type.
     *
     * @since    1.0.0
     * @return   array    The default options.
     */
    protected function get_default_options() {
        return array(
            'display_type'       => 'checkbox', // checkbox, radio, select, multiselect, color, image
            'show_count'         => true,
            'hide_empty'         => true,
            'orderby'            => 'name',
            'order'              => 'ASC',
            'include'            => array(),
            'exclude'            => array(),
            'search_box'         => false,
        );
    }

    /**
     * Get the available term choices for this attribute.
     *
     * @since    1.0.0
     * @return   array    The available choices.
     */
    public function get_choices() {
        $taxonomy = 'pa_' . $this->attribute;
        
        // Check if the attribute taxonomy exists
        if ( ! taxonomy_exists( $taxonomy ) ) {
            return array();
        }
        
        // Set up args for get_terms()
        $args = array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => $this->options['hide_empty'],
            'orderby'    => $this->options['orderby'],
            'order'      => $this->options['order'],
        );

        if ( ! empty( $this->options['include'] ) ) {
            $args['include'] = $this->options['include'];
        }

        if ( ! empty( $this->options['exclude'] ) ) {
            $args['exclude'] = $this->options['exclude'];
        }

        // Get the terms
        $terms = get_terms( $args );
        
        if ( is_wp_error( $terms ) ) {
            return array();
        }
        
        // Format the terms for choices
        $choices = array();
        
        foreach ( $terms as $term ) {
            $choice = array(
                'id'    => $term->term_id,
                'slug'  => $term->slug,
                'name'  => $term->name,
                'count' => $term->count,
            );
            
            // Add color/image data if needed
            if ( in_array( $this->options['display_type'], array( 'color', 'image' ) ) ) {
                $term_meta = get_term_meta( $term->term_id );
                
                if ( $this->options['display_type'] === 'color' ) {
                    $choice['color'] = isset( $term_meta['product_attribute_color'] ) ? $term_meta['product_attribute_color'][0] : '';
                } else {
                    $choice['image'] = isset( $term_meta['product_attribute_image'] ) ? wp_get_attachment_image_url( $term_meta['product_attribute_image'][0], 'thumbnail' ) : '';
                }
            }
            
            $choices[$term->term_id] = $choice;
        }
        
        return $choices;
    }

    /**
     * Apply this filter to a WP_Query.
     *
     * @since    1.0.0
     * @param    WP_Query    $query         The query to modify.
     * @param    array       $filter_values The selected filter values.
     * @return   WP_Query    The modified query.
     */
    public function apply_to_query( $query, $filter_values ) {
        if ( empty( $filter_values ) ) {
            return $query;
        }

        $taxonomy = 'pa_' . $this->attribute;
        
        // Get existing tax queries if any
        $tax_query = $query->get( 'tax_query', array() );
        
        // Prepare the values - could be term IDs or slugs
        $values = array();
        foreach ( $filter_values as $value ) {
            if ( is_numeric( $value ) ) {
                $values[] = absint( $value );
            } else {
                $values[] = sanitize_title( $value );
            }
        }
        
        // Determine the field to use (term_id or slug)
        $field = is_numeric( reset( $values ) ) ? 'term_id' : 'slug';
        
        // Add our taxonomy query
        $tax_query[] = array(
            'taxonomy'         => $taxonomy,
            'field'            => $field,
            'terms'            => $values,
            'operator'         => 'IN',
        );

        // Set the tax_query back to the WP_Query
        $query->set( 'tax_query', $tax_query );
        
        return $query;
    }
    
    /**
     * Render color swatches for color-type attribute display.
     *
     * @since    1.0.0
     * @param    array    $choices         The attribute choices.
     * @param    array    $selected_values The currently selected values.
     * @return   string   The color swatches HTML.
     */
    public function render_color_swatches( $choices, $selected_values = array() ) {
        $html = '<div class="filterly-color-swatches">';
        
        foreach ( $choices as $choice ) {
            $is_selected = in_array( $choice['slug'], $selected_values );
            $html .= sprintf(
                '<label class="filterly-color-swatch %1$s">
                    <input type="%2$s" name="filterly[%3$s][]" value="%4$s" %5$s>
                    <span class="swatch-color" style="background-color:%6$s" title="%7$s"></span>
                    %8$s
                </label>',
                $is_selected ? 'selected' : '',
                $this->options['display_type'] === 'radio' ? 'radio' : 'checkbox',
                esc_attr( $this->id ),
                esc_attr( $choice['slug'] ),
                checked( $is_selected, true, false ),
                esc_attr( $choice['color'] ),
                esc_attr( $choice['name'] ),
                $this->options['show_count'] ? '<span class="count">(' . esc_html( $choice['count'] ) . ')</span>' : ''
            );
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Render image swatches for image-type attribute display.
     *
     * @since    1.0.0
     * @param    array    $choices         The attribute choices.
     * @param    array    $selected_values The currently selected values.
     * @return   string   The image swatches HTML.
     */
    public function render_image_swatches( $choices, $selected_values = array() ) {
        $html = '<div class="filterly-image-swatches">';
        
        foreach ( $choices as $choice ) {
            $is_selected = in_array( $choice['slug'], $selected_values );
            $html .= sprintf(
                '<label class="filterly-image-swatch %1$s">
                    <input type="%2$s" name="filterly[%3$s][]" value="%4$s" %5$s>
                    <img src="%6$s" alt="%7$s" title="%7$s">
                    %8$s
                </label>',
                $is_selected ? 'selected' : '',
                $this->options['display_type'] === 'radio' ? 'radio' : 'checkbox',
                esc_attr( $this->id ),
                esc_attr( $choice['slug'] ),
                checked( $is_selected, true, false ),
                esc_url( $choice['image'] ),
                esc_attr( $choice['name'] ),
                $this->options['show_count'] ? '<span class="count">(' . esc_html( $choice['count'] ) . ')</span>' : ''
            );
        }
        
        $html .= '</div>';
        return $html;
    }
}