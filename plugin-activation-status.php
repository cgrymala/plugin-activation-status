<?php
/*
Plugin Name: Plugin Activation Status
Description: This plugin scans an entire WordPress multisite or multi-network installation and identifies which plugins are active (and where they're active) and which plugins are not activated anywhere in the install
Version: 1.999
Author: Curtiss Grymala
Author URI: http://www.umw.edu/
License: GPL2
Network: true
Text Domain: plugin-activation-status
*/

namespace {
spl_autoload_register( function ( $class_name ) {
	if ( ! stristr( $class_name, 'Ten321\Plugin_Activation_Status\\' ) ) {
		return;
	}

	$file = strtolower( str_replace( array( '\\', '_' ), array( '/', '-' ), $class_name ) );
	$fileparts = explode( '/', $class_name );
	$filename = array_pop( $fileparts );
	$filename = 'class-' . $filename . '.php';
	$fileparts[] = $filename;

	$filename = plugin_dir_path( __FILE__ ) . '/lib/' . implode( '/', $fileparts );

	if ( ! file_exists( $filename ) ) {
		return;
	}

	include $filename;
}

if ( ! class_exists( 'Plugin_Activation_Status' ) ) {
	if ( file_exists( plugin_dir_path( __FILE__ ) . 'classes/class-plugin-activation-status.php' ) ) {
		require_once( plugin_dir_path( __FILE__ ) . 'classes/class-plugin-activation-status.php' );
	} elseif ( file_exists( plugin_dir_path( __FILE__ ) . 'plugin-activation-status/classes/class-plugin-activation-status.php' ) ) {
		require_once( plugin_dir_path( __FILE__ ) . 'plugin-activation-status/classes/class-plugin-activation-status.php' );
	}
}

add_action( 'plugins_loaded', 'inst_plugin_activation_status' );
function inst_plugin_activation_status() {
	if ( ! class_exists( 'Plugin_Activation_Status' ) ) {
		return;
	}

	global $plugin_activation_status_obj;
	$plugin_activation_status_obj = new Plugin_Activation_Status;
}
}

namespace Ten321\Plugin_Activation_Status {
	function plugin_dir_path() {
		return \plugin_dir_path( __FILE__ );
	}

	function plugin_basename() {
		return \plugin_basename( __FILE__ );
	}

	function plugins_url( $path ) {
		return \plugins_url( $path, __FILE__ );
	}
}