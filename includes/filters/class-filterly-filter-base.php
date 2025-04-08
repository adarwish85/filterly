<?php
/**
 * Base filter class that all specific filter types will extend.
 *
 * @since      1.0.0
 * @package    Filterly
 * @subpackage Filterly/includes/filters
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Base filter class.
 */
abstract class Filterly_Filter_Base {

    /**
     * The filter ID.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $id    The filter's ID.
     */
    protected $id;

    /**
     * The filter label.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $label    The filter's display label.
     */
    protected $label;

    /**
     * The filter type.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $type    The filter type (taxonomy, meta, etc.).
     */
    protected $type;

    /**
     * The filter options.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $options    The filter options and settings.
     */
    protected $options;

    /**
     * Initialize the filter.
     *
     * @since    1.0.0
     * @param    string    $id       The filter ID.
     * @param    string    $label    The filter display label.
     * @param    array     $options  Additional filter options.
     */
    public function __construct( $id, $label, $options = array() ) {
        $this->id = sanitize_key( $id );
        $this->label = sanitize_text_field( $label );
        $this->options = wp_parse_args( $options, $this->get_default_options() );
    }

    /**
     * Get the default options for this filter type.
     *
     * @since    1.0.0
     * @return   array    The default options.
     */
    abstract protected function get_default_options();

    /**
     * Get the available choices for this filter.
     *
     * @since    1.0.0
     * @return   array    The available choices.
     */
    abstract public function get_choices();

    /**
     * Apply this filter to a WP_Query.
     *
     * @since    1.0.0
     * @param    WP_Query    $query         The query to modify.
     * @param    array       $filter_values The selected filter values.
     * @return   WP_Query    The modified query.
     */
    abstract public function apply_to_query( $query, $filter_values );

    /**
     * Render the filter HTML.
     *
     * @since    1.0.0
     * @param    array    $selected_values    The currently selected values.
     * @return   string   The filter HTML.
     */
    public function render( $selected_values = array() ) {
        $choices = $this->get_choices();
        
        if ( empty( $choices ) ) {
            return '';
        }
        
        $display_type = isset( $this->options['display_type'] ) ? $this->options['display_type'] : 'checkbox';
        
        ob_start();
        include FILTERLY_PLUGIN_DIR . 'templates/filter-item.php';
        return ob_get_clean();
    }

    /**
     * Get the filter ID.
     *
     * @since    1.0.0
     * @return   string    The filter ID.
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get the filter label.
     *
     * @since    1.0.0
     * @return   string    The filter label.
     */
    public function get_label() {
        return $this->label;
    }

    /**
     * Get the filter type.
     *
     * @since    1.0.0
     * @return   string    The filter type.
     */
    public function get_type() {
        return $this->type;
    }

    /**
     * Get the filter options.
     *
     * @since    1.0.0
     * @return   array    The filter options.
     */
    public function get_options() {
        return $this->options;
    }
}