<?php
/**
 * Taxonomy filter implementation.
 *
 * @since      1.0.0
 * @package    Filterly
 * @subpackage Filterly/includes/filters
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Taxonomy filter class.
 */
class Filterly_Taxonomy_Filter extends Filterly_Filter_Base {

    /**
     * The taxonomy slug.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $taxonomy    The taxonomy slug.
     */
    protected $taxonomy;

    /**
     * Initialize the taxonomy filter.
     *
     * @since    1.0.0
     * @param    string    $taxonomy   The taxonomy slug.
     * @param    string    $label      Optional. The filter display label. If not provided, the taxonomy label will be used.
     * @param    array     $options    Optional. Additional filter options.
     */
    public function __construct( $taxonomy, $label = '', $options = array() ) {
        $this->taxonomy = sanitize_key( $taxonomy );
        $this->type = 'taxonomy';
        
        // If no label is provided, use the taxonomy label
        if ( empty( $label ) ) {
            $taxonomy_obj = get_taxonomy( $taxonomy );
            $label = $taxonomy_obj ? $taxonomy_obj->labels->singular_name : $taxonomy;
        }

        parent::__construct( $taxonomy, $label, $options );
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
            'hierarchical'      => true,        // Show terms in hierarchy
            'show_count'        => true,        // Show post counts
            'collapse_inactive' => false,       // Collapse inactive terms
            'search_box'        => false,       // Show search box
            'orderby'           => 'name',      // name, count
            'order'             => 'ASC',       // ASC, DESC
            'hide_empty'        => true,        // Hide terms with no posts
            'limit'             => 0,           // 0 = no limit
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
        $args = array(
            'taxonomy'   => $this->taxonomy,
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
                'parent'   => $term->parent,
                'count'    => $term->count,
                'taxonomy' => $this->taxonomy,
            );
        }

        // Apply limit if set
        if ( $this->options['limit'] > 0 && count( $choices ) > $this->options['limit'] ) {
            $choices = array_slice( $choices, 0, $this->options['limit'], true );
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

        // Convert string values to integers for term IDs
        $term_ids = array_map( 'absint', (array) $filter_values );

        // Get existing tax queries
        $tax_query = $query->get( 'tax_query', array() );
        
        // Add our taxonomy query
        $tax_query[] = array(
            'taxonomy' => $this->taxonomy,
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