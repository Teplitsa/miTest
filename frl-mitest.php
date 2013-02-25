<?php
/*
Plugin Name: miTest
Version: 1.0.0
Author: Anna Ladoshkina
Description: Interactive test in accordance with ONWAM methodology
Author URI: http://www.foralien.com
Text Domain: frl-mitest
Domain Path: /_inc/lang
Copyright (C) 2013 by Teplitsa of Social Technologies (te-st.ru)
License: GNU Public License v2 or later
licenseURL: http://opensource.org/licenses/gpl-2.0.php 
*/ 

define('FRL_MITEST_VERSION', '1.0.0');


/**
 * Paths
 * 
 **/
function frl_mitest_detect_folder() {
	
	$folder = dirname(__FILE__); 
	$bp = WP_PLUGIN_DIR;
	
	return trim(str_replace($bp, '', $folder), '/');	
}

function frl_mitest_plugin_dir(){
	
	$folder = frl_mitest_detect_folder();
	return WP_PLUGIN_DIR . '/'.$folder;
}

function frl_mitest_plugin_url() {
	
	$folder = frl_mitest_detect_folder();
	return WP_PLUGIN_URL . '/'.$folder;
}



/**
 * Textdomain
 * 
 * */
load_plugin_textdomain('frl-mitest', false, '/frl-mitest/_inc/lang');

$translate_plugin_desc = array( /* container to make plugin description translatable */
	'Description' => __('Interactive test in accordance with ONWAM methodology', 'frl-mitest'),
	'Author' => __('Anna Ladoshkina', 'frl-mitest')
);


/**
 * Load componentns
 * 
 **/
function frl_mitest_init(){
	
	//core
	$path = frl_mitest_plugin_dir().'/_inc/core/core.php';
	require_once($path);
	FRL_Mitest::get_instance();
	
	//admin
	if(is_admin()) {
		$admin_path = frl_mitest_plugin_dir().'/_inc/core/admin.php';
		require_once($admin_path);
		FRL_Mitest_Admin::get_instance();
	}
	
	//functions
	$f_path = frl_mitest_plugin_dir().'/_inc/core/functions.php';
	require_once($f_path);
	
	
	//shortcodes
	$sc_path = frl_mitest_plugin_dir().'/_inc/core/shortcodes.php';
	require_once($sc_path);
	FRL_Mitest_Shortcodes::get_instance();
}


/**
 * Get core instance shortcut
 * 
 **/
function frl_mitest_core(){
	
	return FRL_Mitest::get_instance();
}




/**
 * Activation and Deactivation Functions
 * 
 **/
register_activation_hook(__FILE__, 'frl_mitest_activation');

function frl_mitest_activation() {
	//actions to perform once on plugin activation		
	frl_mitest_init();
	do_action('frl_mitest_activation_actions');	
}


register_deactivation_hook(__FILE__, 'frl_mitest_deactivation');

function frl_mitest_deactivation() {
	// actions to perform once on plugin deactivation
	
	do_action('frl_mitest_deactivation_actions');	
	
}


/**
 * Loaded
 **/
function frl_mitest_done() {	
	frl_mitest_init(); 
	
	do_action( 'frl_mitest_initiated' );
}

add_action('plugins_loaded', 'frl_mitest_done', 2);

?>
