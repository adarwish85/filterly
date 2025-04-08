<?php
/**
 * WooCommerce product attribute filter.
 *
 * @since      1.0.0
 * @package    Filterly
 * @subpackage Filterly/includes/filters
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Make sure WooCommerce is active
if ( ! class_exists( 'WooCommerce' ) ) {
    return;
}

/**
 * WooCommerce product attribute filter class.
 */
class Filterly_Attribute_Filter extends Filterly_Filter_Base {

    /**
     * The attribute name.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $attribute    The attribute name.
     */
    protected $attribute;

    /**
     * Initialize the attribute filter.
     *
     * @since    1.0.0
     * @param    string    $attribute   The attribute name (slug).
     * @param    string    $label       Optional. The filter display label.
     * @param    array     $options     Optional. Additional filter options.
     */
    public function __construct( $attribute, $label = '', $options = array() ) {
        $this->attribute = sanitize_key( $attribute );
        $this->type = 'attribute';
        
        // If no label is provided, use the attribute label
        if ( empty( $label ) ) {
            $taxonomy = wc_attribute_taxonomy_name( $attribute );
            $attribute_obj = taxonomy_exists( $taxonomy ) ? get_taxonomy( $taxonomy ) : null;
            $label = $attribute_obj ? $attribute_obj->labels->singular_name : wc_attribute_label( $attribute );
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
            'display_type'      => 'checkbox',  // checkbox, radio, select, multiselect
            'show_count'        => true,        // Show product counts
            'orderby'           => 'name',      // name, count
            'order'             => 'ASC',       // ASC, DESC
            'hide_empty'        => true,        // Hide terms with no products
            'include'           => array(),     // Terms to include
            'exclude'           => array(),     // Terms to exclude
        );
    }

    /**
     * Get the available choices for this filter.
     *
     * @since    1.0.0
     * @return   array    The available choices.
     */
    public function get_choices() {
        $taxonomy = wc_attribute_taxonomy_name( str_replace( 'pa_', '', $this->attribute ) );
        
        if ( ! taxonomy_exists( $taxonomy ) ) {
            return array();
        }
        
        $args = array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => $this->options['hide_empty'],
            'orderby'    => $this->options['orderby'],
            'order'      => $this->options['order'],
        );

        // Include specific terms if set
        if ( ! empty( $this->options['include'] ) ) {
            $args['include'] = array_map( 'absint', (array) $this->options['include'] );
        }

        // Exclude specific terms if set
        if ( ! empty( $this->options['exclude'] ) ) {
            $args['exclude'] = array_map( 'absint', (array) $this->options['exclude'] );
        }

        $terms = get_terms( $args );

        if ( is_wp_error( $terms ) ) {
            return array();
        }

        $choices = array();

        foreach ( $terms as $term ) {
            $count_html = '';
            if ( $this->options['show_count'] ) {
                $count_html = ' <span class="filterly-count">(' . absint( $term->count ) . ')</span>';
            }
            
            $choices[ $term->term_id ] = array(
                'label'    => $term->name . $count_html,
                'value'    => $term->term_id,
                'slug'     => $term->slug,
                'count'    => $term->count,
                'taxonomy' => $taxonomy,
            );
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

        $taxonomy = wc_attribute_taxonomy_name( str_replace( 'pa_', '', $this->attribute ) );
        
        if ( ! taxonomy_exists( $taxonomy ) ) {
            return $query;
        }

        // Convert string values to integers for term IDs
        $term_ids = array_map( 'absint', (array) $filter_values );

        // Get existing tax queries
        $tax_query = $query->get( 'tax_query', array() );
        
        // Add our taxonomy query
        $tax_query[] = array(
            'taxonomy' => $taxonomy,
            'field'    => 'id',
            'terms'    => $term_ids,
            'operator' => 'IN',
        );

        // Make sure the relation is set
        if ( count( $tax_query ) > 1 && ! isset( $tax_query['relation'] ) ) {
            $tax_query['relation'] = 'AND';
        }

        $query->set( 'tax_query', $tax_query );

        return $query;
    }
}