<?php
/**
 * URL handler for pretty filter URLs.
 *
 * @since      1.0.0
 * @package    Filterly
 * @subpackage Filterly/public
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * URL handler class.
 */
class Filterly_URL_Handler {

    /**
     * Generate a filter URL based on filter values.
     *
     * @since    1.0.0
     * @param    array     $filter_values    The filter values.
     * @return   string    The filter URL.
     */
    public function generate_filter_url( $filter_values ) {
        $base_url = $this->get_current_base_url();
        $query_args = array();
        
        // Add filter values to query args
        foreach ( $filter_values as $filter_key => $filter_value ) {
            if ( empty( $filter_value ) ) {
                continue;
            }
            
            if ( is_array( $filter_value ) ) {
                // Handle arrays (multiple values)
                $query_args[ 'filter_' . $filter_key ] = implode( ',', array_map( 'sanitize_text_field', $filter_value ) );
            } else {
                // Handle single values
                $query_args[ 'filter_' . $filter_key ] = sanitize_text_field( $filter_value );
            }
        }
        
        // Keep other relevant query args that are not filter related
        $preserved_args = array( 's', 'post_type', 'orderby', 'order' );
        foreach ( $preserved_args as $arg ) {
            if ( isset( $_GET[ $arg ] ) ) {
                $query_args[ $arg ] = sanitize_text_field( $_GET[ $arg ] );
            }
        }
        
        // Generate the URL
        if ( empty( $query_args ) ) {
            return $base_url;
        } else {
            return add_query_arg( $query_args, $base_url );
        }
    }

    /**
     * Get the current base URL without filter parameters.
     *
     * @since    1.0.0
     * @return   string    The base URL.
     */
    public function get_current_base_url() {
        global $wp;
        $base_url = home_url( $wp->request );
        
        // Handle pagination in URLs
        if ( is_paged() ) {
            $base_url = get_pagenum_link( 1, false );
            
            // Remove query args
            $base_url = strtok( $base_url, '?' );
        }
        
        return $base_url;
    }

    /**
     * Get current filter values from URL.
     *
     * @since    1.0.0
     * @return   array    The current filter values.
     */
    public function get_current_filter_values() {
        $filter_values = array();
        
        foreach ( $_GET as $key => $value ) {
            if ( strpos( $key, 'filter_' ) === 0 ) {
                // Extract the filter key without the prefix
                $filter_key = substr( $key, 7 );
                
                if ( strpos( $value, ',' ) !== false ) {
                    // Handle comma-separated values
                    $filter_values[ $filter_key ] = array_map( 'sanitize_text_field', explode( ',', $value ) );
                } else {
                    $filter_values[ $filter_key ] = sanitize_text_field( $value );
                }
            }
        }
        
        return $filter_values;
    }
}