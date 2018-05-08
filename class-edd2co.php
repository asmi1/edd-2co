<?php

/**
 *
 * Plugin Main class
 *
 * This is the core plugin class responsible for including and
 * instantiating all of the code that composes the plugin.
 *
 * @since 1.0.0
 *
 * @package edd2co
 *
 */

if ( ! defined( 'WPINC' ) ) { exit; } // Exit if accessed directly

// Bail if class already exists
if ( class_exists( 'EDD2CO' ) ) {
	return;
}

class EDD2CO {

    /**
     * Holds the instance
     *
     * Ensures that only one instance of EDD2CO exists in memory at any one
     * time and it also prevents needing to define globals all over the place.
     *
     * TL;DR This is a static property property that holds the singleton instance.
     *
     * @since 1.0.0
     */
	private static $instance;

    public $version;
    protected $checkout_url;
    protected $checkout_url_sandbox;

    protected $loader;

    /**
     * Get the instance and store the class inside it. This plugin utilises
     * the PHP singleton design pattern.
     *
     * @since 1.0.0
     *
     * @return object self::$instance Instance
     */
	public static function get_instance() {

		if ( !isset( self::$instance ) && !( self::$instance instanceof EDD2CO ) ) {

			// Create instance
			self::$instance = new self();

			// Vars
			self::$instance->version = '1.0.0';
			self::$instance->checkout_url = 'https://www.2checkout.com/checkout/purchase';
			self::$instance->checkout_url_sandbox = 'https://sandbox.2checkout.com/checkout/purchase';

			// Setup globals & load classes
            self::$instance->setup_globals();
            self::$instance->load_classes();

			// Dependencies
			self::$instance->load_dependencies();

            // Register Hooks
            self::$instance->hooks();

            // Run.. run!
            self::$instance->run();
		}

		return self::$instance;
	}

    /**
     * Constructor
     *
     * @since 1.0.0
     */
	public function __construct() {
		
		self::$instance = $this;
	}

    /**
     * Sets up the constants/globals used
     *
     * @since 1.0.0
     */
    private function setup_globals() {

        // File Path and URL Information
        $this->file          = __FILE__;
        $this->basename      = apply_filters( 'edd2co_plugin_basenname', plugin_basename( $this->file ) );
        $this->plugin_url    = plugin_dir_url( __FILE__ );
        $this->plugin_path   = plugin_dir_path( __FILE__ );
        $this->lang_dir      = apply_filters( 'edd2co_lang_dir', trailingslashit( $this->plugin_path . 'languages' ) );

        // Classes
        $this->classes_dir   = apply_filters( 'edd2co_classes_dir', trailingslashit( $this->plugin_path . 'includes' ) );
        $this->classes_url   = apply_filters( 'edd2co_classes_url', trailingslashit( $this->plugin_url  . 'includes' ) );
    }

    /**
     * Loads Classes
     *
     * @since 1.0.0
     */
    private function load_classes() {

        require_once( $this->classes_dir . 'class-loader.php' );
    }

    /**
     * Load plugin dependencies
     *
     * @since 1.0.0
     */
    private function load_dependencies() {

        // Loader
        $this->loader = new EDD2CO_Loader();

        // Load plugin textdomain
        $this->loader->add_action( 'init', $this, 'load_plugin_textdomain' );
    }

    /**
     * Hooks
     *
     * @since 1.0.0
     */
    private function hooks() {

    	// Register EDD gateway (ID "edd2co")
        $this->loader->add_action( 'edd_payment_gateways', $this, 'register_gateway' );

        // Disable CC form
        $this->loader->add_action( 'edd_edd2co_cc_form', $this, 'payment_form' );

        // Process payments
        $this->loader->add_action( 'edd_gateway_edd2co', $this, 'process_payment' );

        // Register settings section
        $this->loader->add_action( 'edd_settings_sections_gateways', $this, 'settings_section' );

        // Register settings
        $this->loader->add_action( 'edd_settings_gateways', $this, 'settings_page' );

        // Payment listener/API hook
        $this->loader->add_action( 'init', $this, 'complete_offsite_payment', 20 );
    }

    /**
     * Run the loader
     *
     * @since 1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * Reset the instance of the class
     *
     * @since 1.0.0
     * @access public
     * @static
     */
    public static function reset() {
        self::$instance = null;
    }

    /**
     * Load Plugin Text Domain
     *
     * @since 1.0.0
     */
    public function load_plugin_textdomain() {

        // Load the default language files
        load_plugin_textdomain( 'edd2co', false, $this->lang_dir );
    }

	/**
	 * This function adds the payment gateway to EDD settings.
	 *
	 * @param array $gateways
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function register_gateway( $gateways ) {

		$gateways['edd2co'] = array(
			'admin_label'    => esc_html__( '2Checkout', 'edd2co' ),
			'checkout_label' => apply_filters( 'edd2co_gateway_label', esc_html__( '2Checkout', 'edd2co' ) ),
		);

		return $gateways;
	}

	/**
	 * Disable CC payment form.
	 *
	 * @since 1.0.0
	 */
	public function payment_form() {
		return;
	}

	/**
	 * Process 2checkout payment.
	 *
	 * @param array $purchase_data
	 *
	 * @since 1.0.0
	 */
	public function process_payment( $purchase_data ) {

		if ( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'edd-gateway' ) ) {
			wp_die( 
				esc_html__( 'Nonce verification has failed', 'edd2co' ),
				esc_html__( 'Error', 'edd2co' ),
				array( 'response' => 403 )
			);
		}

		// make sure we don't have any left over errors present
		edd_clear_errors();

		// validate billing address city, country, post code, country
		$payment = array(
			'price'        => $purchase_data['price'],
			'date'         => $purchase_data['date'],
			'user_email'   => $purchase_data['user_email'],
			'purchase_key' => $purchase_data['purchase_key'],
			'currency'     => edd_get_currency(),
			'downloads'    => $purchase_data['downloads'],
			'cart_details' => $purchase_data['cart_details'],
			'user_info'    => $purchase_data['user_info'],
			'gateway'      => '2Checkout',
			'status'       => 'pending',
		);

		// Record the pending payment
		$payment_id = edd_insert_payment( $payment );

		$this->process_offsite_payment( $payment_id, $purchase_data );
	}

	/**
	 * Process payments made off-site.
	 *
	 * @param int $payment_id
	 * @param array $purchase_data
	 *
	 * @return void
	 */
	public function process_offsite_payment( $payment_id, $purchase_data ) {

		$return_url = add_query_arg( 'edd-tt-api', '2co', edd_get_success_page_uri() );

		$cart = array();
		foreach ( $purchase_data['cart_details'] as $item ) {
			$cart[] = array(
				'product_id' => $item['id'],
				'name'       => $item['name'],
				'quantity'   => $item['quantity'],
				'price'      => $item['price'], // Use 'item_price' instead of 'price' for item price without discounts/taxes.
				'tangible'   => 'N',
				'type'		 => 'product',
			);
		}

		$twoco_args = $this->get_twoco_args( $payment_id, $cart, $return_url );

		if ( $this->is_sandbox() ) {
			$checkout_url =	$this->checkout_url_sandbox;
		} else {
			$checkout_url =	$this->checkout_url;
		}

    	$checkout_url = $checkout_url .'?' . http_build_query( $twoco_args );

		// Fix for some sites that encode the entities
		$checkout_url = str_replace( '&amp;', '&', $checkout_url );

		// Add note
		edd_insert_payment_note( $payment_id, esc_html__( 'Order pending fraud review.', 'edd2co' ) );

		// Redirect
		wp_redirect( $checkout_url );
		exit;
	}

	/**
	 * Complete order made via off-site payment (via API hook)
	 *
	 * @since 1.0.0
	 */
	public function complete_offsite_payment() {

		if ( isset( $_GET['edd-tt-api'] ) && $_GET['edd-tt-api'] == '2co' ) {

			// Prevent the complete offsite payment code from running if this is a 2checkout INS/IPN request.
			// because they both listen to edd-tt-api=2co request.
			if ( isset( $_POST['md5_hash'] ) ) {
				return;
			}

			$order_id = $_REQUEST['merchant_order_id'];
			
			if ( isset( $_REQUEST['demo'] ) && $_REQUEST['demo'] == 'Y' ){
				$compare_string = $this->get_secret_word() . $this->get_account_number() .'1'. $_REQUEST['total'];

			} else {
				$compare_string = $this->get_secret_word() . $this->get_account_number() . $_REQUEST['order_number'] . $_REQUEST['total'];
			}

			$compare_hash1 = strtoupper( md5( $compare_string ) );
			$compare_hash2 = $_REQUEST['key'];

			if ( $compare_hash1 != $compare_hash2 ) {
				edd_set_error( '2co_offsite_error', esc_html__( '2Checkout Hash Mismatch... check your secret word.', 'edd2co' ) );
				edd_send_back_to_checkout();

			// Successful payment
			} else {
				edd_update_payment_status( $order_id, 'complete' );
				edd_send_to_success_page();
			}
		}
	}

	/**
	 * Get 2Checkout Args for passing to 2CO
	 *
	 * @access public
	 * @param mixed $order
	 * @return array
	 */
	public function get_twoco_args( $payment_id, $cart, $return_url ) {

        $data = array();
        $data['sid'] = $this->get_account_number();
        $data['mode'] = '2CO';
        $data['merchant_order_id'] = $payment_id;
        $data['currency_code'] = edd_get_currency();
        $data['x_receipt_link_url'] = $return_url;

        // Do not pass for live sales i.e if its false.
        if ( $this->is_test_mode() ) {
            $data['demo'] = 'Y';
        }

        $i = 0;

        // Setup Products information
        foreach ( $cart as $item ) {

			$data['li_'.$i.'_type'] = $item['type'];
			$data['li_'.$i.'_name'] = $item['name'];
			$data['li_'.$i.'_price'] = $item['price'];
			$data['li_'.$i.'_quantity'] = $item['quantity'];
			$data['li_'.$i.'_tangible'] = $item['tangible'];

			// optional item/product parameters
			if (isset($item['product_id'])) {
				$data['li_'.$i.'_product_id'] = $item['product_id'];
			}
			if (isset($item['description'])) {
				$data['li_'.$i.'description'] = $item['description'];
			}
			if (isset($item['recurrence'])) {
				$data['li_'.$i.'recurrence'] = $item['recurrence'];
			}
			if (isset($item['duration'])) {
				$data['li_'.$i.'duration'] = $item['duration'];
			}
			if (isset($item['startup_fee'])) {
				$data['li_'.$i.'startup_fee'] = $item['startup_fee'];
			}

			++$i;
        }
		
		return $data;
	}

	/**
	 * Register settings section
	 *
	 * @param array $sections
	 *
	 * @since 1.0.0
	 */
	public function settings_section( $sections ) {
		$sections['2checkout'] = esc_html__( '2Checkout', 'edd2co' );

		return $sections;
	}

	/**
	 * Register settings
	 *
	 * @param array $settings
	 *
	 * @since 1.0.0
	 */
	public function settings_page( $settings ) {

		$gateway_settings = array(
			'2checkout' => array(
				array(
					'id'   => 'edd2co_settings',
					'name' => '<strong>' . esc_html__( '2Checkout Settings', 'edd2co' ) . '</strong>',
					'desc' => esc_html__( 'Configure 2Checkout payment gateway settings', 'edd2co' ),
					'type' => 'header',
				),

				array(
					'id'   => 'edd2co_account_number',
					'name' => esc_html__( 'Account Number', 'edd2co' ),
					'desc' => esc_html__( 'Enter your 2Checkout account number.', 'edd2co' ),
					'type' => 'text',
					'size' => 'regular',
				),

				array(
					'id'   => 'edd2co_secret_word',
					'name' => esc_html__( 'Secret Word', 'edd2co' ),
					'desc' => esc_html__( 'Enter your 2checkout secret word.', 'edd2co' ),
					'type' => 'text',
					'size' => 'regular',
				),

				array(
					'id'   => 'edd2co_is_sandbox',
					'name' => esc_html__( 'Enable Sandbox', 'edd2co' ),
					'desc' => esc_html__( 'Check this box if you want to use 2Checkout Sandbox.', 'edd2co' ),
					'type' => 'checkbox',
					'size' => 'regular',
				),
			),
		);

		return array_merge( $settings, $gateway_settings );
	}

	/**
	 * Account number.
	 *
	 * @return string
	 */
	public function get_account_number() {
		return edd_get_option( 'edd2co_account_number' );
	}

	/**
	 * Account secret word.
	 *
	 * @return string
	 */
	public function get_secret_word() {
		return edd_get_option( 'edd2co_secret_word' );
	}

	/**
	 * When EDD demo is activated, the 2co gateway perform test payment by appending demo=Y
	 * to the payment parameter before redirecting offsite to 2co for payment.
	 *
	 * @return bool
	 */
	public function is_test_mode() {
		return apply_filters( 'edd2co_is_test_mode', edd_is_test_mode() );
	}

	/**
	 * Sandbox mode
	 *
	 * @return bool
	 */
	public function is_sandbox() {
		return apply_filters( 'edd2co_is_sandbox', (bool) edd_get_option( 'edd2co_is_sandbox' ) );
	}
}