<?php
/**
 * FRL MItest Functions
 *
 * @package Frl_mitest
 **/

 
/**
 * Formatting
 **/ 
function frl_html_for($content, $context = 'print') {
	
	if(!in_array($context, array('save', 'edit', 'print')))
		$context = 'print';
	
	return apply_filters('html_for_'.$context, trim($content));
}


function frl_text_for($content, $context = 'print') {
	
	if(!in_array($context, array('save', 'edit', 'print')))
		$context = 'print';
	
	return apply_filters('text_for_'.$context, trim($content));
}



/**
 * Menu order inline edit markup - shortcut
 **/
function frl_menu_order_inline_markup($post_object) {
    
	if(!class_exists('FRL_Mitest_Admin'))
		return '';
	
    return FRL_Mitest_Admin::menu_order_inline_markup($post_object);   
}




/**
 * Template tags
 **/

function frl_mitest_head_page_title($echo = true){
	
	$term = get_queried_object();
	if(empty($term))
		return;
	
	$title[] = esc_attr(frl_text_for($term->name, 'print'));
	$title[] = esc_attr(__('Interactive test for non-profits websites', 'frl-mitest'));
	$title[] = get_bloginfo('name');
	
	$title = implode(' &ndash; ', $title);
		
	if($echo) 
		echo $title;
	else
		return $title;

}


/**
 * Get plugin option shortcut
 **/

function frl_mitest_get_option($key, $default = ''){
	
	$core = frl_mitest_core();
	return $core->get_option($key, $default);
}


/**
 * Get custom header image
 **/
function frl_mitest_custom_header() {
	
	$h_id = frl_mitest_get_option('header_id', 0);
	if($h_id == 0)
		return '';
	
	return wp_get_attachment_image($h_id, 'full', false, array('alt' => get_bloginfo('name')));	
}


/**
 * Test entry helpers for cross-site usage
 **/
function frl_get_test_entry($entry_id){
	global $wpdb;
	
	$r = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->mtdata WHERE id=%d", $entry_id));
	if(!empty($r))
		return $r[0];
	else
		return $r;
}

function frl_get_test_profile_link($entry_id, $module_id = null) {
	global $wpdb;
	
	if(empty($module_id)){
		$entry = frl_get_test_entry($entry_id);
		if(empty($entry))
			return '';
		
		$module_id = $entry->module_id;
	}
	
	$url = get_term_link(intval($module_id), 'module');
	$id = frl_encrypt($entry_id);
		
	$url = add_query_arg('mitest_profile', $id, $url);
	
	return $url;
}


/**
 * Simple encryption to hide actual ID in profile link
 **/

function frl_encrypt($id) {	
	
	$id = $id * 545896;
	
	return base64_encode($id);
}

function frl_decrypt($id) {
	
	$id = base64_decode($id);
	$id = intval($id/545896);

	return $id;
}
 
?>