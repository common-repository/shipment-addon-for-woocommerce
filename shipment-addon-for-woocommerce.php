<?php

/**
 * Plugin Name: Shipment addon for WooCommerce
 * Plugin URI: #
 * Description: Shipment addon for WooCommerce
 * Tags: shipment, addon, woocommerce
 * Version: 1.0.1
 * Author: HITStacks
 * Author URI: https://hitstacks.com
 * Requires at least: 4.7
 * Tested up to: 5.7
 * WC requires at least: 2.6.14
 * WC tested up to: 4.3.1
 *
 * Text Domain: hitshipment
 *
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 */
if (!defined('ABSPATH')) {
	exit;
}

if (!defined("SAFW_HITShipmentModuleVersion")) {
	define("SAFW_HITShipmentModuleVersion", "1.0.0");
}
if (!defined("SAFW_SHIPPED_EMAIL_PATH")) {
	define('SAFW_SHIPPED_EMAIL_PATH', plugin_dir_path(__FILE__));
}


if (!class_exists('SAFW_HITShipmentManager')) {

	class SAFW_HITShipmentManager
	{


		/**
		 * Itembase extension constructor.
		 */
		public function __construct()
		{
			add_action('plugins_loaded', array($this, 'SAFW_load_plugin'));
		}

		/**
		 * Loading current extension and adding extension menu into WooCommerce navigation.
		 */
		public function SAFW_load_plugin()
		{
			add_action('init', array($this, 'SAFW_register_shipped_order_status'));
			add_filter('wc_order_statuses', array($this, 'SAFW_add_shipped_to_order_statuses'));
			add_filter('woocommerce_email_classes', array($this, 'SAFW_register_email'), 90, 1);
			add_action('add_meta_boxes', array($this, 'SAFW_add_meta_boxes'));
			add_action('admin_menu', array($this, 'SAFW_register_shipment_addon_submenu_page'));
			add_action( 'save_post', array($this, 'SAFW_shipment_addon_update_order'), 10, 1 );
		}
		function SAFW_shipment_addon_update_order(){
			if(isset($_POST['shipment_addon_carr'])){
				global $post;
				$order = '';
				$order = wc_get_order($post->ID);
				// echo "<pre>";
				// print_r($post->ID);
				// die();
				$shipping_item = $order->get_items('shipping');
				$methods = WC()->shipping->get_shipping_methods();
				$method_ar =array();
				foreach ($methods as $method => $val) {
					$method_ar[$method] = $val->method_title;
				}
				$mth_title = isset($method_ar[$_POST['shipment_addon_carr']]) ? sanitize_text_field($method_ar[$_POST['shipment_addon_carr']]) : sanitize_text_field($_POST['shipment_addon_carr']) ;
				$mth_id = isset($method_ar[$_POST['shipment_addon_carr']]) ? sanitize_text_field($_POST['shipment_addon_carr']) : sanitize_text_field($_POST['shipment_addon_carr']);
				// echo "<pre>";
				// print_r($mth_id);
				// die();
				foreach ($shipping_item as $item_id => $item) {
					// print_r($item);
					$order_item_name             = $item->get_name();
					$order_item_type             = $item->get_type();
					$carrier       				 = $item->get_method_title();
					$item->set_method_id($mth_id);
					$item->set_method_title( str_replace('id_','',$mth_title));
					$item->save();
					$shipping_method_id          = $item->get_method_id(); // The method ID
					$shipping_method_instance_id = $item->get_instance_id(); // The instance ID
					$shipcost       			 = $item->get_total();
					$shipping_method_total_tax   = $item->get_total_tax();
					$shipping_method_taxes       = $item->get_taxes();
				}
				// die();
			}
			if(isset($_POST['shipment_addon_track'])){
					
				$track = sanitize_text_field($_POST['shipment_addon_track']);
				update_option('hit_track_'.$post->ID, $track);
			}
			
		}

		function SAFW_register_shipment_addon_submenu_page()
		{
			add_submenu_page('woocommerce', 'Shipment Addon', 'Shipment Addon', 'manage_options', 'shipment-addon', array($this, 'SAFW_shipment_addon_page_callback'));
		}

		function SAFW_shipment_addon_page_callback()
		{
			include_once('views/shipment_addon_setting_view.php');
		}
		public function SAFW_add_meta_boxes()
		{
			add_meta_box('hitshipment', __('Shipment', 'hitshipment'), array($this, 'SAFW_hitshipmentView'), 'shop_order', 'normal', 'high');
		}
		public function SAFW_hitshipmentView()
		{
			global $post;
			$order = '';
			$date = '';
			$carrier = '-';
			$weight = 0;
			$shipcost = '0.00';
			$tracking_num = '';
			$currency = '';
			$weg_unit = '';
			$optn = '';

			if (isset($post->ID)) {
				$order = wc_get_order($post->ID);
				$date = date('d-D-Y', strtotime($post->post_modified));
				$methods = WC()->shipping->get_shipping_methods();
				$general_settings = get_option('shipment_addon_main_settings');
				
			
				// die();
				// foreach ($method_arr as $method=>$title) {
				// 	$optn .= "<option value=" . $method . ">" . $title . "</option>";
				// }
			}
			if (isset($order) && $order != '') {
				// Iterating through order shipping items
				$shipping_item = $order->get_items('shipping');
				$order_item = $order->get_items();
				$order_data = $order->get_data();
				$currency = $order_data['currency'];
				$weg_unit = get_option('woocommerce_weight_unit');
				foreach ($shipping_item as $item_id => $item) {
					$order_item_name             = $item->get_name();
					$order_item_type             = $item->get_type();
					$carrier       				 = $item->get_method_title();
					$shipping_method_id          = $item->get_method_id(); // The method ID
					$shipping_method_instance_id = $item->get_instance_id(); // The instance ID
					$shipcost       			 = $item->get_total();
					$shipping_method_total_tax   = $item->get_total_tax();
					$shipping_method_taxes       = $item->get_taxes();
				}

				if (sizeof($order_item) > 0) {
					foreach ($order_item as $item) {

						$_product_id = $item->get_id();
						$prod = $item->get_product();
						if(empty($prod)){
							return false;
						}
// echo "<pre>";
// print_r($prod);
// die();
						if (!$prod->is_virtual()) {

							$weight += is_numeric($prod->get_weight() * $item['qty']) ? $prod->get_weight() * $item['qty'] : 0;
						}
					}
					if ($weight == 0) {
						$weight = '-';
						$weg_unit = '';
					}
					if ($shipcost <= 0) {
						$shipcost = "-";
						$currency = "";
					}
				}
				// print_r();
				// die();
				// echo "<pre>";
				// print_r($prod);

				// print_r($currency);
				// die();
			}
			if(isset($post->ID)){
				$added_optn = array();
				foreach ($methods as $method => $val) {
					$method_ar[$method] = $val->method_title;
					$is_checked = '';
					if($method == $shipping_method_id){
						$is_checked = 'selected';
					}
					$optn .= '<option value="' . $method .'"'. $is_checked .'>' . $val->method_title . '</option>';
				}
				if(isset($general_settings['shipment_addon_added_carr'])){
				foreach($general_settings['shipment_addon_added_carr'] as $ky=>$val){
					$is_checked = '';
					if('id_'.$val == $shipping_method_id){
						$is_checked = 'selected';
					}
					$optn .= '<option value="id_' . $val .'"'. $is_checked .'>' . $val. '</option>';
				}
			}
			}
?>

			<style>
				div.tab-frame input {
					display: none;
				}

				div.tab-frame label {
					display: block;
					float: left;
					padding: 10px 20px;
					cursor: pointer;
					background: #f8f8f8;
					width: 17%;
				}

				div.tab-frame input:checked+label {
					background: #f0f0f0;
					color: black;
					cursor: default;
					padding: 9px 20px;
					border: 1px solid gainsboro;
					border-radius: 7px;
					border-bottom: 0px;
				}

				div.tab-frame div.tab {
					display: none;
					padding: 5px 10px;
					clear: left
				}

				td {
					text-align: center;
				}

				div.tab-frame input:nth-of-type(1):checked~.tab:nth-of-type(1),
				div.tab-frame input:nth-of-type(2):checked~.tab:nth-of-type(2),
				div.tab-frame input:nth-of-type(3):checked~.tab:nth-of-type(3),
				div.tab-frame input:nth-of-type(4):checked~.tab:nth-of-type(4) {
					display: block;
				}
			</style>
			<div class="tab-frame">
				<input type="radio" checked name="tab" id="tab1">
				<label for="tab1">Carrier</label>

				<input type="radio" name="tab" id="tab2">
				<!-- <label for="tab2">Documents</label>
			<input type="radio" name="tab" id="tab3">
			<label for="tab3">Status</label>
			<input type="radio" name="tab" id="tab4">
			<label for="tab4">Merchandise returns</label> -->

				<div class="tab">
					<table style="width:100%;" style="border-collapse: collapse;border: 1px solid;">
						<tr>
							<th style="text-align:center;">Date</th>
							<th style="text-align:center;">Carrier</th>
							<th style="text-align:center;">Weight</th>
							<th style="text-align:center;">Shipping cost</th>
							<th style="text-align:center;">Tracking number</th>
							<th></th>
						</tr>
						<tr>
							<td style="text-align:center;"><?php _e($date,'hitshipment') ?></td>
							<td id="edt_carr"><?php  _e($carrier,'hitshipment') ?></td>
							<td><?php _e($weight . ' ' . $weg_unit,'hitshipment') ?></td>
							<td><?php _e($shipcost . ' ' . $currency,'hitshipment') ?></td>
							<td id="edt_trk"><a href="<?php if (isset($general_settings['shipment_addon_added_carr'])) {
															foreach ($general_settings['shipment_addon_added_carr'] as $ky => $added_carrier) {

																if ($carrier == $added_carrier) {
																	 _e($general_settings['shipment_addon_added_track_url'][$ky],'hitshipment');
																}
															}
														}
														if ($carrier != '' || $carrier != '-') {
															_e($general_settings['shipment_addon_track_url'][$shipping_method_id],'hitshipment');
														} else {
															 _e('#','hitshipment');
														} ?>"><?php  _e(get_option('hit_track_'.$post->ID),'hitshipment')?></a></td>
							<td><a style="text-decoration: underline; cursor: pointer;" onclick="edit_odr_info()">edit</a></td>

						</tr>
					</table>
				</div>
				<div class="tab">sample content 2</div>
				<div class="tab">sample content 3</div>
				<div class="tab">sample content 4</div>
			</div>
			<script>
				function edit_odr_info() {
					var edt_trk = document.createElement("input");
					edt_trk.type = "text";
					edt_trk.name = "shipment_addon_track";
					edt_trk.value = "1234567890";
					// document.getElementById("edt_trk").insertAdjacentHTML('afterbegin', '<input type="text" name="shipment_addon_track" value="1234567890">');
					// document.body.appendChild(edt_trk);
					// document.getElementById("edt_carr").innerHTML = '<input type="text" name="shipment_addon_track" value="1234567890">';
					jQuery("#edt_carr").html('<select name="shipment_addon_carr" style="display:block;margin: 0px -75px 0px 0px;"><?php _e($optn,'hitshipment'); ?></select>');
					jQuery("#edt_trk").html('<input type="text" name="shipment_addon_track" value="" placeholder="1234567890" style="display:block !important;margin: 0px -97px 0px 0px;">');

				}
			</script>
<?php
		}

		public function SAFW_register_email($emails)
		{
			require_once 'email/wc-shipped-email.php';

			$emails['WC_Order_Shipped'] = new SAFW_Order_Shipped_Email();

			return $emails;
		}

		public function SAFW_register_shipped_order_status()
		{
			register_post_status('wc-shipped', array(
				'label'                     => 'Shipped',
				'public'                    => true,
				'show_in_admin_status_list' => true,
				'show_in_admin_all_list'    => true,
				'exclude_from_search'       => false,
				'label_count'               => _n_noop('Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>')
			));
		}
		public function SAFW_add_shipped_to_order_statuses($order_statuses)
		{
			$new_order_statuses = array();

			foreach ($order_statuses as $key => $status) {
				$new_order_statuses[$key] = $status;
				if ('wc-processing' === $key) {
					$new_order_statuses['wc-shipped'] = 'Shipped';
				}
			}

			return $new_order_statuses;
		}
		/**
		 * Method is used to determine if WooCommerce is installed before allowing extension activation.
		 */
		public static function SAFW_activate()
		{
			if (!in_array(
				'woocommerce/woocommerce.php',
				apply_filters('active_plugins', get_option('active_plugins'))
			)) {
				wp_die(
					'<h1>WooCommerce required</h1><p>WooCommerce extension must be installed and activated first!</p>',
					'Plugin Activation Error',
					array('response' => 200, 'back_link' => true)
				);
			}
		}

		public static function SAFW_uninstallAndCleanUp()
		{
			global $wpdb;
		}
	}

	$HITShipmentManager = new SAFW_HITShipmentManager(__FILE__);

	register_activation_hook(__FILE__, array('SAFW_HITShipmentManager', 'SAFW_activate'));
	register_uninstall_hook(__FILE__, array('SAFW_HITShipmentManager', 'SAFW_uninstallAndCleanUp'));
}
