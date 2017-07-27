<?php
/**
 * Main plugin file
 *
 * @package svbk-rcp-getresponse
 */

/*
Plugin Name: Restrict Content Pro - GetResponse
Description: Integrate Restrict Content Pro with GetResponse
Author: Silverback Studio
Version: 1.1
Author URI: http://www.silverbackstudio.it/
Text Domain: svbk-rcp-getresponse
*/

use Svbk\WP\Plugins\RCP\GetResponse;

define( 'SVBK_RCP_GETRESPONSE_PLUGIN_FILE', __FILE__ );

/**
 * Loads textdomain and main initializes main class
 *
 * @return void
 */
function svbk_rcp_getresponse_init() {
	load_plugin_textdomain( 'svbk-rcp-countdown', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	if ( ! class_exists( 'Svbk\WP\Plugins\RCP\Countdown\Integration' ) ) {
		require_once 'src/Integration.php';
	}

	$svbk_rcp_getresponse = new GetResponse\Integration( env('GETRESPONSE_APIKEY') );

	add_action( 'rcp_add_subscription_form', array( $svbk_rcp_getresponse, 'admin_subscirption_form' ) );
	add_action( 'rcp_edit_subscription_form', array( $svbk_rcp_getresponse, 'admin_subscirption_form' ) );

	add_action( 'rcp_add_subscription', array( $svbk_rcp_getresponse, 'admin_subscirption_form_save' ), 10, 2 );
	add_action( 'rcp_pre_edit_subscription_level', array( $svbk_rcp_getresponse, 'admin_subscirption_form_save' ), 10, 2 );

	add_action( 'rcp_member_post_set_subscription_id', array( $svbk_rcp_getresponse, 'update') , 10, 3);
}

add_action( 'plugins_loaded', 'svbk_rcp_getresponse_init' );

