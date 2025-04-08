<?php
/**
 * Query handler for filtering content.
 *
 * @since      1.0.0
 * @package    Filterly
 * @subpackage Filterly/public
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Query handler class.
 */
class Filterly_Query {

    /**
     * Build a WP_Query with the given filter parameters.
     *
     * @since    1.0.0
     * @param    array    $filter_values    The filter values.
     * @param    array    $query_args       Additional query arguments.
     * @return   array    The final query arguments.
     */
    public function build_query( $filter_values, $query_args = array() ) {
        // Start with default query args
        $default_args = array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => get_option( 'posts_per_page' ),
        );
        
        $query_args = wp_parse_args( $query_args, $default_args );
        
        // If no filter values, return the base query
        if ( empty( $filter_values ) ) {
            return $query_args;
        }
        
        // Initialize tax_query and meta_query if they don't exist
        if ( ! isset( $query_args['tax_query'] ) ) {
            $query_args['tax_query'] = array();
        }
        
        if ( ! isset( $query_args['meta_query'] ) ) {
            $query_args['meta_query'] = array();
        }
        
        // Set relationships for tax_query and meta_query
        if ( ! empty( $query_args['tax_query'] ) && ! isset( $query_args['tax_query']['relation'] ) ) {
            $query_args['tax_query']['relation'] = 'AND';
        }
        
        if ( ! empty( $query_args['meta_query'] ) && ! isset( $query_args['meta_query']['relation'] ) ) {
            $query_args['meta_query']['relation'] = 'AND';
        }
        
        // Process each filter
        foreach ( $filter_values as $filter_key => $filter_value ) {
            if ( empty( $filter_value ) ) {
                continue;
            }
            
            // Determine filter type
            if ( taxonomy_exists( $filter_key ) ) {
                // Taxonomy filter
                $filter = new Filterly_Taxonomy_Filter( $filter_key );
                $query_args = $this->apply_filter_to_query( $filter, $filter_value, $query_args );
            } elseif ( strpos( $filter_key, 'pa_' ) === 0 && taxonomy_exists( $filter_key ) ) {
                // WooCommerce attribute filter
                $filter = new Filterly_Attribute_Filter( str_replace( 'pa_', '', $filter_key ) );
                $query_args = $this->apply_filter_to_query( $filter, $filter_value, $query_args );
            } elseif ( $filter_key === 'price' && class_exists( 'WooCommerce' ) ) {
                // WooCommerce price filter
                $filter = new Filterly_Meta_Filter( '_price', __( 'Price', 'filterly' ), array(
                    'display_type' => 'range',
                    'data_type'    => 'numeric',
                ) );
                $query_args = $this->apply_filter_to_query( $filter, $filter_value, $query_args );
            } else {
                // Try to determine if it's a meta filter
                $filter = new Filterly_Meta_Filter( $filter_key, ucfirst( str_replace( '_', ' ', $filter_key ) ) );
                $query_args = $this->apply_filter_to_query( $filter, $filter_value, $query_args );
            }
        }
        
        // Allow modifications of the final query
        $query_args = apply_filters( 'filterly_query_args', $query_args, $filter_values );
        
        return $query_args;
    }

    /**
     * Apply a filter to a query.
     *
     * @since    1.0.0
     * @param    Filterly_Filter_Base    $filter         The filter object.
     * @param    mixed                   $filter_value   The filter value.
     * @param    array                   $query_args     The current query arguments.
     * @return   array    The modified query arguments.
     */
    private function apply_filter_to_query( $filter, $filter_value, $query_args ) {
        // Create a temporary WP_Query to apply the filter
        $temp_query = new WP_Query();
        
        foreach ( $query_args as $key => $value ) {
            $temp_query->set( $key, $value );
        }
        
        // Apply the filter to the query
        $filtered_query = $filter->apply_to_query( $temp_query, $filter_value );
        
        // Extract the modified query vars
        $filtered_args = $filtered_query->query_vars;
        
        // Remove empty query vars and standard vars
        $standard_vars = array( 'query', 'update_post_term_cache', 'update_post_meta_cache', 'post_type', 'posts_per_page', 'paged' );
        foreach ( $standard_vars as $var ) {
            if ( isset( $filtered_args[ $var ] ) && empty( $filtered_args[ $var ] ) ) {
                unset( $filtered_args[ $var ] );
            }
        }
        
        // Merge the filtered args into the original query args
        foreach ( $filtered_args as $key => $value ) {
            $query_args[ $key ] = $value;
        }
        
        return $query_args;
    }
}