<?php
/**
 * WooCommerce product variation filter.
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
 * WooCommerce product variation filter class.
 */
class Filterly_Variation_Filter extends Filterly_Filter_Base {

    /**
     * The variation attribute.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $attribute    The variation attribute.
     */
    protected $attribute;

    /**
     * Initialize the variation filter.
     *
     * @since    1.0.0
     * @param    string    $attribute    The variation attribute.
     * @param    string    $label        Optional. The filter display label.
     * @param    array     $options      Optional. Additional filter options.
     */
    public function __construct( $attribute, $label = '', $options = array() ) {
        $this->attribute = sanitize_key( $attribute );
        $this->type = 'variation';
        
        // If no label is provided, use the attribute label
        if ( empty( $label ) ) {
            $label = wc_attribute_label( $attribute );
        }

        parent::__construct( $attribute, $label, $options );
    }

    /**
     * Get the default options for this filter type.
     *
     * @since    1.0.0
     * @return   array    The default options.
     */
    protected function get_default_options() {
        return array(
            'display_type'      => 'select',    // checkbox, radio, select, multiselect
            'show_count'        => true,        // Show product counts
            'orderby'           => 'name',      // name, count
            'order'             => 'ASC',       // ASC, DESC
            'hide_empty'        => true,        // Hide terms with no products
        );
    }

    /**
     * Get the available choices for this filter.
     *
     * @since    1.0.0
     * @return   array    The available choices.
     */
    public function get_choices() {
        global $wpdb;
        
        // Get all product variation attributes for this attribute name
        $query = $wpdb->prepare(
            "SELECT meta_value, COUNT(DISTINCT post_id) as count
            FROM {$wpdb->postmeta}
            WHERE meta_key = %s
            AND post_id IN (
                SELECT ID FROM {$wpdb->posts}
                WHERE post_type = 'product_variation'
                AND post_status = 'publish'
            )
            GROUP BY meta_value
            ORDER BY meta_value " . $this->options['order'],
            'attribute_' . $this->attribute
        );
        
        $results = $wpdb->get_results( $query );
        
        if ( empty( $results ) ) {
            return array();
        }
        
        $choices = array();
        
        foreach ( $results as $result ) {
            if ( empty( $result->meta_value ) ) {
                continue;
            }
            
            $count_html = '';
            if ( $this->options['show_count'] ) {
                $count_html = ' <span class="filterly-count">(' . absint( $result->count ) . ')</span>';
            }
            
            $choices[ $result->meta_value ] = array(
                'label'  => $result->meta_value . $count_html,
                'value'  => $result->meta_value,
                'count'  => $result->count,
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

        $values = (array) $filter_values;

        // Get existing meta queries
        $meta_query = $query->get( 'meta_query', array() );
        
        // We need to find the product IDs that have variations with these attributes
        $variation_product_ids = $this->get_variation_product_ids( $values );
        
        if ( ! empty( $variation_product_ids ) ) {
            // Add post__in to the query to filter by these products
            $existing_post_in = $query->get( 'post__in', array() );
            
            if ( ! empty( $existing_post_in ) ) {
                $post_in = array_intersect( $existing_post_in, $variation_product_ids );
            } else {
                $post_in = $variation_product_ids;
            }
            
            $query->set( 'post__in', $post_in );
        } else {
            // No products match, force no results
            $query->set( 'post__in', array( 0 ) );
        }

        return $query;
    }

    /**
     * Get parent product IDs for variations that match the given attribute values.
     *
     * @since    1.0.0
     * @param    array    $values    The attribute values to match.
     * @return   array    Array of product IDs.
     */
    private function get_variation_product_ids( $values ) {
        global $wpdb;
        
        $placeholders = implode( ',', array_fill( 0, count( $values ), '%s' ) );
        $prepared_values = array( 'attribute_' . $this->attribute );
        
        foreach ( $values as $value ) {
            $prepared_values[] = $value;
        }
        
        $query = $wpdb->prepare(
            "SELECT DISTINCT p.post_parent
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'product_variation'
            AND p.post_status = 'publish'
            AND pm.meta_key = %s
            AND pm.meta_value IN (" . $placeholders . ")",
            $prepared_values
        );
        
        $product_ids = $wpdb->get_col( $query );
        
        return ! empty( $product_ids ) ? array_map( 'absint', $product_ids ) : array();
    }
}