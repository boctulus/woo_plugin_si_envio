<?php
/*
Plugin Name: Si Envio
Plugin URI: https://www.sienvio.online/
Description: integración de SiEnvio con WP
Version: 1.0.0
Author: boctulus@sienvio.online
Author URI: https://www.sienvio.online/
*/

include __DIR__ . '/debug.php';

global $api_key_sienvio;


/*
	Settings
*/

$api_key_sienvio = 'm7aXx04GOe9EePbiVSpWgR3EG6L20F';


/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	function after_place_order($order_id, $old_status, $new_status, $order)
	{
		global $api_key_sienvio;

		var_dump($order_id);
		var_dump ([$old_status, $new_status]);

		if( $new_status == "completed" ) {

			debug($api_key_sienvio, 'API KEY');

			foreach ($order->get_items() as $item_key => $item ){
				$item_id = $item->get_id();
				$product_id   = $item->get_product_id(); // the Product id

				$meta = get_post_meta($product_id);
				
				$l = $meta['_length'] ?? 0;
				$w = $meta['_width'] ?? 0;
				$h = $meta['_weight'] ?? 0;
				$W = $meta['_weight'] ?? 0;
				
				debug([$l, $w, $h, $W], 'Dimmensions');
			}

			$timezone = date_default_timezone_get();
			
			/*
				https://stackoverflow.com/questions/22843504/how-can-i-get-customer-details-from-an-order-in-woocommerce
			*/
			
			// Get the Customer billing email
			$billing_email  = $order->get_billing_email();

			// Get the Customer billing phone
			$billing_phone  = $order->get_billing_phone();
			
			// Customer billing information details
			$billing_first_name = $order->get_billing_first_name();
			$billing_last_name  = $order->get_billing_last_name();
			$billing_company    = $order->get_billing_company();
			$billing_address_1  = $order->get_billing_address_1();
			$billing_address_2  = $order->get_billing_address_2();
			$billing_city       = $order->get_billing_city();
			$billing_state      = $order->get_billing_state();
			$billing_postcode   = $order->get_billing_postcode();
			$billing_country    = $order->get_billing_country();

			// Customer shipping information details
			$shipping_first_name = $order->get_shipping_first_name();
			$shipping_last_name  = $order->get_shipping_last_name();
			$shipping_company    = $order->get_shipping_company();
			$shipping_address_1  = $order->get_shipping_address_1();
			$shipping_address_2  = $order->get_shipping_address_2();
			$shipping_city       = $order->get_shipping_city();
			$shipping_state      = $order->get_shipping_state();
			$shipping_postcode   = $order->get_shipping_postcode();
			$shipping_country    = $order->get_shipping_country();
			$shipping_note       = NULL;
			
			//debug($order, 'ORDER OBJECT');
			
			$box_id = 6;
			
			$data = [
						"nombre" => "$shipping_last_name, $shipping_first_name ",
						"calle" =>  "$shipping_address_1 - $shipping_address_2",
						"ciudad"  => "$shipping_city, $shipping_state, $shipping_country",
						"notas"  => $shipping_note,
						"box_id"  => $box_id				
			];		
			
			// simulo fallo en el servidor
			$ok = false;
			
			if (!$ok){
				debug("Updating order # $order_id");
				$order = new WC_Order($order_id);				
				$order->update_status('processing', 'Falla en el servidor');
			}
			
			
			exit; ///
		}	
		
		
	}

	add_action( 'woocommerce_order_status_changed','after_place_order', 99, 4);


	function si_envio_shipping_method_init() {
		if ( ! class_exists( 'WC_SiEnvio_Shipping_Method' ) ) {
			class WC_SiEnvio_Shipping_Method extends WC_Shipping_Method {
				/**
				 * Constructor for your shipping class
				 *
				 * @access public
				 * @return void
				 */
				public function __construct() {
					$this->id                 = 'si_envio_shipping_method'; 
					$this->method_title       = __( 'SiEnvio' );  // Title shown in admin
					$this->method_description = __( 'Integración con SiEnvio.online' ); // Description shown in admin

					$this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled
					$this->title              = "Si Envio"; // This can be added as an setting but for this example its forced.

					$this->init();
				}

				/**
				 * Init your settings
				 *
				 * @access public
				 * @return void
				 */
				function init() {
					// Load the settings API
					$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
					$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

					// Save settings in admin if you have any defined
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
				}

				/**
				 * calculate_shipping function.
				 *
				 * @access public
				 * @param mixed $package
				 * @return void
				 */
				public function calculate_shipping( $package = array()) {
					$rate = array(
						'label' => "Envio Pancho",
						'cost' => '20',
						'calc_tax' => 'per_order'
					);

					// Register the rate
					$this->add_rate( $rate );
				}
			}
		}
	}

	add_action( 'woocommerce_shipping_init', 'si_envio_shipping_method_init' );

	function add_si_envio_shipping_method( $methods ) {
		$methods['si_envio_shipping_method'] = 'WC_SiEnvio_Shipping_Method';
		return $methods;
	}

	add_filter( 'woocommerce_shipping_methods', 'add_si_envio_shipping_method' );
}