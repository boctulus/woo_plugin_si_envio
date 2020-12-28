<?php
/*
Plugin Name: Si Envio
Plugin URI: https://www.sienvio.online/
Description: integración de SiEnvio con WooCommerce
Version: 1.0.0
Author: boctulus@sienvio.online <Pablo>
Author URI: https://www.sienvio.online/
*/

include __DIR__ . '/debug.php';
require __DIR__ . '/config.php';
require __DIR__ . '/cotizaciones.php';
require __DIR__ . '/recoleccion.php';


/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	function after_place_order($order_id, $old_status, $new_status, $order)
	{
		//var_dump($order_id);
		//var_dump ([$old_status, $new_status]);

		if( $new_status == "completed" ) 
		{
			$alert0x4 = false;
			$notas = [];						
		
			$order = new WC_Order($order_id);	
		
			if(!isset($_SESSION)) {
     			session_start();
			}
			
			if (time() < $_SESSION['server_not_before']){
				$order->update_status(STATUS_IF_ERROR, SERVER_ERROR_MSG . 'Code g500. Technical detail: waiting for server recovery');
				return;
			}
				
			$items = [];
			foreach ($order->get_items() as $item_key => $item ){
				$item_id = $item->get_id();
				$product_id   = $item->get_product_id(); 

				$meta = get_post_meta($product_id);
				
				$l = $meta['_length'][0] ?? 0;
				$w = $meta['_width'][0] ?? 0;
				$h = $meta['_height'][0] ?? 0;
				$W = $meta['_weight'][0] ?? 0;
				$cant =  $item->get_quantity();
				
				if ($l == 0 && $w == 0 && $h == 0 && $W == 0){
					$alert0x4 = true;	
				}
				
				$items['data'][] = [
					'l' => $l,
					'w' => $w,
					'h' => $h,
					'W' => $W,
					'qty' => $cant
				];
				
				//debug([$l, $w, $h, $W], 'Dimmensions');
				//debug($meta, "meta para prod id = $item_id");
			}

			$timezone = date_default_timezone_get();
			
			
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
			
			if ($alert0x4){
				$notas[] = NO_DIM;
				
				if ($shipping_note == null){
					$shipping_note = NO_DIM;
				}
			}
			
						
			/*
				Cotizacion
			*/
			
		
			try {
				$cotizacion_res = getCotizacion($items);
			} catch (\Exception $e){
				$_SESSION['server_error_time'] = time();
				$_SESSION['server_not_before'] = $_SESSION['server_error_time'] + SERVER_TIME_BEFORE_RETRY;
				$order->update_status(STATUS_IF_ERROR, SERVER_ERROR_MSG . 'Code c001. Technical detail: '. $e->getMessage());
				return;
			}
				
			if (empty($cotizacion_res)){
				$order->update_status(STATUS_IF_ERROR, SERVER_ERROR_MSG . 'Code c001B');
				return;
			}
		
			$cotizacion = json_decode($cotizacion_res, true);
			
			//debug(json_encode($items, JSON_PRETTY_PRINT));
			//debug(json_encode($cotizacion, JSON_PRETTY_PRINT)); exit; ///
			
			if (!isset($cotizacion['data']['boxes'])){
				$order->update_status(STATUS_IF_ERROR, SERVER_ERROR_MSG . 'Code c002');
				return; //
			}		
			
			$data = [
						"calle"    => "$shipping_address_1, $shipping_address_2",
						"ciudad"   => "$shipping_city, $shipping_country",
						"notas"    =>  implode(' | ', $notas),
						"boxes"    => $cotizacion['data']['boxes'],
						"entregas" => [
							"nombre"   => "$shipping_last_name, $shipping_first_name ",
							"email"    => $billing_email,
							"telefono" => $billing_phone,
							"notas"    => $shipping_note
						]
			];		
			
			
			try {
				$recoleccion_res = recoleccion($data);
			} catch (\Exception $e){
				$_SESSION['server_error_time'] = time();
				$_SESSION['server_not_before'] = $_SESSION['server_error_time'] + SERVER_TIME_BEFORE_RETRY;
				$order->update_status(STATUS_IF_ERROR, SERVER_ERROR_MSG . 'Code r001. Technical detail: '. $e->getMessage());
				return;
			}
					
			//debug(json_encode($data, JSON_PRETTY_PRINT)); exit; ///
			
			if (empty($recoleccion_res)){	
				//debug(json_encode($data, JSON_PRETTY_PRINT)); exit; ///				
				$order->update_status(STATUS_IF_ERROR, SERVER_ERROR_MSG. 'Code r001B');
				return; //
			}
			
			$recoleccion = json_decode($recoleccion_res, true);
			
			if (!isset($recoleccion['data']['id'])){
				$order->update_status(STATUS_IF_ERROR, SERVER_ERROR_MSG.  'Code r002');
				return; //
			}	
			
			
			$order->update_status('completed', $shipping_note == null ? TODO_OK : $shipping_note);
			
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
					$this->method_title       = __( 'SiEnvio' ); 
					$this->method_description = __( 'Integración con SiEnvio.online' ); 

					$this->enabled            = "yes"; 
					$this->title              = "Si Envio"; 

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
					/*
						'label' => SHIPPING_METHOD_LABEL,
						'cost' => '20',
						'calc_tax' => 'per_order'
					*/
					$rate = array(
						'label' => SHIPPING_METHOD_LABEL
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