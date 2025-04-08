<?php
/**
 * Meta filter implementation.
 *
 * @since      1.0.0
 * @package    Filterly
 * @subpackage Filterly/includes/filters
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Meta filter class.
 */
class Filterly_Meta_Filter extends Filterly_Filter_Base {

    /**
     * The meta key.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $meta_key    The meta key.
     */
    protected $meta_key;

    /**
     * Initialize the meta filter.
     *
     * @since    1.0.0
     * @param    string    $meta_key    The meta key.
     * @param    string    $label       The filter display label.
     * @param    array     $options     Optional. Additional filter options.
     */
    public function __construct( $meta_key, $label, $options = array() ) {
        $this->meta_key = sanitize_key( $meta_key );
        $this->type = 'meta';
        
        parent::__construct( $meta_key, $label, $options );
    }

    /**
     * Get the default options for this filter type.
     *
     * @since    1.0.0
     * @return   array    The default options.
     */
    protected function get_default_options() {
        return array(
            'display_type'      => 'checkbox',  // checkbox, radio, select, multiselect, range
            'data_type'         => 'string',    // string, numeric, date
            'comparison'        => '=',         // =, !=, >, <, >=, <=, LIKE, NOT LIKE, IN, NOT IN, BETWEEN
            'show_count'        => true,        // Show post counts
            'orderby'           => 'name',      // name, count
            'order'             => 'ASC',       // ASC, DESC
            'choices'           => array(),     // Predefined choices
            'custom_callback'   => '',          // Custom callback for advanced filtering
            'range_min'         => '',          // For range filters - min value
            'range_max'         => '',          // For range filters - max value
            'range_step'        => 1,           // For range filters - step value
        );
    }

    /**
     * Get the available choices for this filter.
     *
     * @since    1.0.0
     * @return   array    The available choices.
     */
    public function get_choices() {
        // If predefined choices are provided, use those
        if ( ! empty( $this->options['choices'] ) ) {
            return $this->options['choices'];
        }

        // For range filters, return min and max values
        if ( $this->options['display_type'] === 'range' ) {
            return array(
                'min' => $this->options['range_min'] !== '' ? $this->options['range_min'] : $this->get_min_value(),
                'max' => $this->options['range_max'] !== '' ? $this->options['range_max'] : $this->get_max_value(),
                'step' => $this->options['range_step'],
            );
        }

        // Get all unique meta values for this key
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT DISTINCT pm.meta_value, COUNT(pm.post_id) as count
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = %s
            AND p.post_status = 'publish'
            AND pm.meta_value != ''
            GROUP BY pm.meta_value
            ORDER BY " . ( $this->options['orderby'] === 'count' ? 'count' : 'pm.meta_value' ) . " " . $this->options['order'],
            $this->meta_key
        );
        
        $results = $wpdb->get_results( $query );
        
        if ( empty( $results ) ) {
            return array();
        }
        
        $choices = array();
        
        foreach ( $results as $result ) {
            $label = $result->meta_value;
            $count_html = '';
            
            if ( $this->options['show_count'] ) {
                $count_html = ' <span class="filterly-count">(' . absint( $result->count ) . ')</span>';
            }
            
            $choices[ $result->meta_value ] = array(
                'label'  => $label . $count_html,
                'value'  => $result->meta_value,
                'count'  => $result->count,
            );
        }
        
        return $choices;
    }

    /**
     * Get the minimum value for this meta key (for range filters).
     *
     * @since    1.0.0
     * @return   mixed    The minimum value.
     */
    private function get_min_value() {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT MIN(CAST(pm.meta_value AS SIGNED)) as min_val
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = %s
            AND p.post_status = 'publish'
            AND pm.meta_value != ''",
            $this->meta_key
        );
        
        $result = $wpdb->get_var( $query );
        
        return $result ? floatval( $result ) : 0;
    }

    /**
     * Get the maximum value for this meta key (for range filters).
     *
     * @since    1.0.0
     * @return   mixed    The maximum value.
     */
    private function get_max_value() {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT MAX(CAST(pm.meta_value AS SIGNED)) as max_val
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = %s
            AND p.post_status = 'publish'
            AND pm.meta_value != ''",
            $this->meta_key
        );
        
        $result = $wpdb->get_var( $query );
        
        return $result ? floatval( $result ) : 100;
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

        // Get existing meta queries
        $meta_query = $query->get( 'meta_query', array() );
        
        // Handle different display types
        if ( $this->options['display_type'] === 'range' ) {
            // Handle range filter
            if ( isset( $filter_values['min'] ) && isset( $filter_values['max'] ) ) {
                $meta_query[] = array(
                    'key'     => $this->meta_key,
                    'value'   => array( floatval( $filter_values['min'] ), floatval( $filter_values['max'] ) ),
                    'type'    => 'NUMERIC',
                    'compare' => 'BETWEEN',
                );
            }
        } else {
            // Handle checkbox, radio, select filters
            $comparison = $this->options['comparison'];
            $data_type = $this->options['data_type'];
            $values = (array) $filter_values;
            
            // Adjust query based on comparison type
            if ( in_array( $comparison, array( 'IN', 'NOT IN' ) ) ) {
                $meta_query[] = array(
                    'key'     => $this->meta_key,
                    'value'   => $values,
                    'type'    => $data_type === 'numeric' ? 'NUMERIC' : 'CHAR',
                    'compare' => $comparison,
                );
            } else {
                // For multiple values with = comparison, create an OR relation
                if ( count( $values ) > 1 && $comparison === '=' ) {
                    $sub_meta_query = array( 'relation' => 'OR' );
                    
                    foreach ( $values as $value ) {
                        $sub_meta_query[] = array(
                            'key'     => $this->meta_key,
                            'value'   => $value,
                            'type'    => $data_type === 'numeric' ? 'NUMERIC' : 'CHAR',
                            'compare' => '=',
                        );
                    }
                    
                    $meta_query[] = $sub_meta_query;
                } else {
                    // Single value
                    $meta_query[] = array(
                        'key'     => $this->meta_key,
                        'value'   => count( $values ) === 1 ? reset( $values ) : $values,
                        'type'    => $data_type === 'numeric' ? 'NUMERIC' : 'CHAR',
                        'compare' => $comparison,
                    );
                }
            }
        }

        // Make sure the relation is set
        if ( count( $meta_query ) > 1 && ! isset( $meta_query['relation'] ) ) {
            $meta_query['relation'] = 'AND';
        }

        $query->set( 'meta_query', $meta_query );

        return $query;
    }
}