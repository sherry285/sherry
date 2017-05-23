<?php
/*
Plugin Name: WooCommerce STPay
Plugin URI: https://Solidtrustpay.com/shopping-carts
Description: Extends WooCommerce with Solid Trust Pay gateway.
Version: 1.0
Author Tech: https://solidtrustpay.com

Copyright: © 2016 STPay.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
add_action('plugins_loaded', 'woocommerce_STPay_init', 0);
#$order->property : $order->get_property();
function woocommerce_STPay_init()
{
	if (!class_exists('WC_Payment_Gateway'))
	{
		return;
	}

	/**
	* Add the Gateway to WooCommerce
	**/
	function woocommerce_add_STPay_gateway($methods)
	{
		$methods[] = 'WC_STPay';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'woocommerce_add_STPay_gateway');

	class WC_STPay extends WC_Payment_Gateway

	{

		var $notify_url;

		public function __construct()
		{

		global $woocommerce;

        $this->id           = 'solidtrust';
   $this->icon = plugins_url( 'images/stp_b88x31.gif', __FILE__ );
        $this->has_fields   = false;
      #  $this->liveurl      = 'https://solidtrustpay.com/handle.php';
		 $this->liveurl      = 'https://solidtrustpay.com/handle.php';
		$this->testurl      = 'https://solidtrustpay.com/handle.php';
        $this->method_title = __( 'SolidTrust', 'woocommerce' );
        $this->notify_url   = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_SolidTrustPay', home_url( '/' ) ) );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

 	   	$this->merchantAccount        = $this->settings['merchantAccount'];
		$this->sci_name        = $this->settings['sci_name'];
 	   	$this->testmode			= $this->settings ['testmode'];
		// Define user set variables
		$this->title 			= $this->get_option( 'title' );
		$this->description 		= $this->get_option( 'description' );
		$this->email 			= $this->get_option( 'email' );
		$this->receiver_email   = $this->get_option( 'receiver_email', $this->email );
	//	$this->testmode			= $this->get_option( 'testmode' );
		$this->send_shipping	= $this->get_option( 'send_shipping' );
		$this->address_override	= $this->get_option( 'address_override' );
		$this->debug			= $this->get_option( 'debug' );
		$this->form_submission_method = $this->get_option( 'form_submission_method' ) == 'yes' ? true : false;
		$this->page_style 		= $this->get_option( 'page_style' );
		$this->invoice_prefix	= $this->get_option( 'invoice_prefix', 'WC-' );

		// Logs
		//if ( 'yes' == $this->debug )
			//$this->log = $woocommerce->logger();

		// Actions
		add_action( 'valid-solidtrust-standard-ipn-request', array( $this, 'successful_request' ) );
		add_action( 'woocommerce_receipt_solidtrust', array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Payment listener/API hook
		//add_action( 'woocommerce_api_WC_Gateway_SolidTrustPay', array( $this, 'check_ipn_response' ) );
		add_action( '$this->get_return_url( $order )', array( $this, 'check_ipn_response' ) );
		$theurl =  $this->get_return_url($order);



		if ( !$this->is_valid_for_use() ) $this->enabled = false;

    }

public function admin_options() {

		?>
		<h3><?php _e( 'SolidTrust standard', 'woocommerce' ); ?></h3>
		<p><?php _e( 'SolidTrust standard works by sending the user to STP to enter their payment information.', 'woocommerce' ); ?></p>

    	<?php if ( $this->is_valid_for_use() ) : ?>

			<table class="form-table">
			<?php
    			// Generate the HTML For the settings form.
    			$this->generate_settings_html();
			?>
			</table><!--/.form-table-->

		<?php else : ?>
            <div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'STP does not support your store currency.', 'woocommerce' ); ?></p></div>
		<?php
			endif;
	}
				function init_form_fields() {

    	$this->form_fields = array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'woocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Enable SolidTrustPay standard', 'woocommerce' ),
							'default' => 'yes'
						),
			'title' => array(
							'title' => __( 'Title', 'woocommerce' ),
							'type' => 'text',
							'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
							'default' => __( 'SolidTrustPay', 'woocommerce' ),
							'desc_tip'      => true,
						),
			'description' => array(
							'title' => __( 'Description', 'woocommerce' ),
							'type' => 'textarea',
							'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
							'default' => __( 'Pay via SolidTrustPay; you can pay with any credit card if you don\'t have a SolidTrustPay account', 'woocommerce' )
						),
			'merchantAccount' => array(
							'title' => __( 'SolidTrustPay Username', 'woocommerce' ),
							'type' 			=> 'text',
							'description' => __( 'Please enter your SolidTrustPay userrname.', 'woocommerce' ),
							'default' => '',
							'desc_tip'      => true,
							'placeholder'	=> 'your username'
						),
			'sci_name' => array(
							'title' => __( 'sci name', 'woocommerce' ),
							'type' 			=> 'text',
							'description' => __( 'Enter your sci  name.', 'woocommerce' ),
							'default' => '',
							'desc_tip'      => true,
							'placeholder'	=> 'sciname'
						),

						'testmode' => array(
										'title' => __( 'test mode', 'woocommerce' ),
										'type' 			=> 'text',
										'description' => __( 'Set ON for test payments', 'woocommerce' ),
										'default' => 'OFF',
										'desc_tip'      => true,
										'placeholder'	=> 'testmode'
									),
			'invoice_prefix' => array(
							'title' => __( 'Invoice Prefix', 'woocommerce' ),
							'type' => 'text',
							'description' => __( 'Please enter a prefix for your invoice numbers.', 'woocommerce' ),
							'default' => 'WC-',
							'desc_tip'      => true,
						),


		/*	'debug' => array(
							'title' => __( 'Debug Log', 'woocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Enable logging', 'woocommerce' ),
							'default' => 'no',
							'description' => sprintf( __( 'Log SolidTrustPay events, such as IPN requests, inside <code>woocommerce/logs/stpay-%s.txt</code>', 'woocommerce' ), sanitize_file_name( wp_hash( 'SolidTrustPay' ) ) ),
						)*/
			);

    }

public function is_valid_for_use()
		{


			return true;
		}



		/**
	solidtrustpay * Get solidtrustpay Args
	 *
	 * @access public
	 * @param mixed $order
	 * @return array
	 */
	function get_solidtrustpay_args( $order ) {
		global $woocommerce;

		$order_id = $order->get_id();

		// solidtrustpay Args
		$solidtrustpay_args = array_merge(
			array(
				'cmd' 					=> '_cart',
				'merchantAccount' 		=> $this->merchantAccount,
				'iswoo' 				=> 1,

				'sci_name'				=> $this->sci_name,
				'testmode'				=> $this->testmode,
				'currency' 		=> get_woocommerce_currency(),
				'charset' 				=> 'UTF-8',
				'rm' 					=> is_ssl() ? 2 : 1,
				'upload' 				=> 1,

				'return_url' 				=> add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ),
				'cancel_url'			=> $order->get_cancel_order_url(),
				'page_style'			=> $this->page_style,

				// Order key + ID
				'item_id'				=> $this->invoice_prefix . ltrim( $order->get_order_number(), '#' ),
				'custom' 				=> serialize( array( $order_id, $order->get_order_key() ) ),

				// IPN
				'notify_url'			=> add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ),
				'amount'         => $order->get_total(),


				)

		);

		// Shipping
		if ( $this->send_shipping=='yes' ) {
			$solidtrustpay_args['address_override'] = ( $this->address_override == 'yes' ) ? 1 : 0;

			$solidtrustpay_args['no_shipping'] = 0;

			// If we are sending shipping, send shipping address instead of billing
			$solidtrustpay_args['first_name']		= $order->shipping_first_name;
			$solidtrustpay_args['last_name']		= $order->shipping_last_name;
			$solidtrustpay_args['company']			= $order->shipping_company;
			$solidtrustpay_args['address1']		= $order->shipping_address_1;
			$solidtrustpay_args['address2']		= $order->shipping_address_2;
			$solidtrustpay_args['city']			= $order->shipping_city;
			$solidtrustpay_args['state']			= $order->shipping_state;
			$solidtrustpay_args['country']			= $order->shipping_country;
			$solidtrustpay_args['zip']				= $order->shipping_postcode;
		} else {
			$solidtrustpay_args['no_shipping'] = 1;
		}

		// If prices include tax or have order discounts, send the whole order as a single item
		if ( get_option( 'woocommerce_prices_include_tax' ) == 'yes' || $order->get_total_discount() > 0 ) {

			// Discount
			$solidtrustpay_args['discount_amount_cart'] = $order->get_total_discount();

			$item_names = array();

			if ( sizeof( $order->get_items() ) > 0 )
				foreach ( $order->get_items() as $item )
					if ( $item['qty'] )
						$item_names[] = $item['name'] . ' x ' . $item['qty'];

			$solidtrustpay_args['item_name_1'] 	= sprintf( __( 'Order %s' , 'woocommerce'), $order->get_order_number() ) . " - " . implode( ', ', $item_names );
			$solidtrustpay_args['quantity_1'] 		= 1;
			#$solidtrustpay_args['amount'] 		= number_format( $order->get_total() - $order->get_shipping() - $order->get_shipping_tax(), 2, '.', '' );
$solidtrustpay_args['amount'] 		= number_format( $order->get_total() - $order->get_shipping_total() - $order->get_shipping_tax(), 2, '.', '' );
			if ( ( $order->get_shipping_total() + $order->get_shipping_tax() ) > 0 ) {
				$solidtrustpay_args['item_name_2'] = __( 'Shipping via', 'woocommerce' ) . ' ' . ucwords( $order->shipping_method_title );
				$solidtrustpay_args['quantity_2'] 	= '1';
				$solidtrustpay_args['amount_2'] 	= number_format( $order->get_shipping_total() + $order->get_shipping_tax() , 2, '.', '' );
			}

		} else {

			// Tax
			$solidtrustpay_args['tax_cart'] = $order->get_total_tax();

			// Cart Contents
			$item_loop = 0;
			if ( sizeof( $order->get_items() ) > 0 ) {
				foreach ( $order->get_items() as $item ) {
					if ( $item['qty'] ) {

						$item_loop++;

						$product = $order->get_product_from_item( $item );

						$item_name 	= $item['name'];

						$item_meta = new WC_Order_Item_Meta( $item['item_meta'] );
						if ( $meta = $item_meta->display( true, true ) )
							$item_name .= ' ( ' . $meta . ' )';

						$solidtrustpay_args[ 'item_name_' . $item_loop ] 	= $item_name;
						$solidtrustpay_args[ 'quantity_' . $item_loop ] 	= $item['qty'];
						$solidtrustpay_args[ 'amount' . $item_loop ] 		= $order->get_item_subtotal( $item, false );

						if ( $product->get_sku() )
							$solidtrustpay_args[ 'item_number_' . $item_loop ] = $product->get_sku();
					}
				}
			}


		}

		$solidtrustpay_args = apply_filters( 'woocommerce_solidtrustpay_args', $solidtrustpay_args );

		return $solidtrustpay_args;
	}


		 /**
	  /**

     *
     * @access public
     * @param mixed $order_id
     * @return stringsolidtrustpay
     */
    function generate_solidtrustpay_form( $order_id ) {
		global $woocommerce;

		$order = new WC_Order( $order_id );

		if ( $this->testmode == 'yes' ):
			$solidtrustpay_adr = $this->testurl . '?test_ipn=1&';
		else :
			$solidtrustpay_adr = $this->liveurl . '?';
		endif;

		$solidtrustpay_args = $this->get_solidtrustpay_args( $order );

		$solidtrustpay_args_array = array();

		foreach ($solidtrustpay_args as $key => $value) {
			$solidtrustpay_args_array[] = '<input type="hidden" name="'.esc_attr( $key ).'" value="'.esc_attr( $value ).'" />';
		}

		$woocommerce->add_inline_js( '
			jQuery("body").block({
					message: "' . __( 'Thank you for your order. We are now redirecting you to STP to make payment.', 'woocommerce' ) . '",
					baseZ: 99999,
					overlayCSS:
					{
						background: "#fff",
						opacity: 0.6
					},
					css: {
				        padding:        "20px",
				        zindex:         "9999999",
				        textAlign:      "center",
				        color:          "#555",
				        border:         "3px solid #aaa",
				        backgroundColor:"#fff",
				        cursor:         "wait",
				        lineHeight:		"24px",
				    }
				});
			jQuery("#submit_solidtrustpay_payment_form").click();
		' );

		return '<form action="'.esc_url( $solidtrustpay_adr ).'" method="post" id="solidtrustpay_payment_form" target="_top">
				' . implode( '', $solidtrustpay_args_array) . '
				<input type="submit" class="button-alt" id="submit_solidtrustpay_payment_form" value="'.__( 'Pay via SolidTrustPay', 'woocommerce' ).'" /> <a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__( 'Cancel order &amp; restore cart', 'woocommerce' ).'</a>
			</form>';

	}


    /**
     * Process the payment and return the result
     *
     * @access public
     * @param int $order_id
     * @return array
     */
	function process_payment( $order_id ) {

		$order = new WC_Order( $order_id );

		if ( ! $this->form_submission_method ) {

			$solidtrustpay_args = $this->get_solidtrustpay_args( $order );

			$solidtrustpay_args = http_build_query( $solidtrustpay_args, '', '&' );

			if ( $this->testmode == 'yes' ):
				$solidtrustpay_adr = $this->testurl . '?test_ipn=1&';
			else :
				$solidtrustpay_adr = $this->liveurl . '?';
			endif;
			$order->payment_complete();

			return array(
				'result' 	=> 'success',
				'redirect'	=> $solidtrustpay_adr . $solidtrustpay_args
			);

		} else {

			return array(
				'result' 	=> 'success',
				'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay' ))))

			);

		}

	}


		function check_ipn_response(){
            global $woocommerce;

            if ($amount >0){
                 $order->payment_complete();
            }else{
                wp_die('IPN Request Failure');
            }


       }

	}
}
