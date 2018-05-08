<?php

/**
 *
 * Loader class
 *
 * This class register all plugins' filters and actions and execute them by the "run" method.
 *
 * @since 1.0.0
 *
 * @package tt-arkam
 *
 */

if ( ! defined( 'WPINC' ) ) { exit; } // Exit if accessed directly

// Bail if class already exists
if ( class_exists( 'EDD2CO_Loader' ) ) {
    return;
}

/**
 * EDD2CO Loader
 *
 * @since 1.0.0
 */
class EDD2CO_Loader {

    protected $actions;
    protected $filters;

    /**
     * Constructor
     * @since 1.0.0
     */
    public function __construct() {

        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Add an action
     *
     * @since 1.0.0
     */
    public function add_action( $hook, $component, $callback, $priority = 10 ) {
        $this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority );
    }

    /**
     * Add a filter
     *
     * @since 1.0.0
     */
    public function add_filter( $hook, $component, $callback, $priority = 10 ) {
        $this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority );
    }

    /**
     * Add a action/filter
     *
     * @access private
     *
     * @since 1.0.0
     */
    private function add( $hooks, $hook, $component, $callback, $priority = 10 ) {

        $hooks[] = array(
            'hook'      => $hook,
            'component' => $component,
            'callback'  => $callback,
            'priority'  => $priority
        );
 
        return $hooks;
    }

    /**
     * Run registered actions & filters
     *
     * @since 1.0.0
     */
    public function run() {

        foreach ( $this->filters as $hook ) {
            add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'] );
        }
 
        foreach ( $this->actions as $hook ) {
            add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'] );
        }
    }
}