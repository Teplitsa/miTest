<?php
/**
 * FRL List tables for test submissions
 *
 * @package Frl_mitest
 **/

if(!class_exists('WP_List_Table'))
    require_once(ABSPATH.'/wp-admin/includes/class-wp-list-table.php');
    
	
	
class FRL_Tests_List_Table extends WP_List_Table {
	
	var $per_page = 25;
	var $capability = 'edit_others_posts';
	var $empty_mark = "&ndash;";
	
	var $test_count;
	
	/* constructor */
    function __construct() {
						
		parent::__construct(array('singular' => 'submission', 'plural'=>'submissions'));
		$this->_get_count();
	}
	
	
	/**
	 * Obligatory
	 **/
	function ajax_user_can() {
		return current_user_can($this->capability);
	}
		
	function prepare_items() {
		global $wpdb;
		
		//pagination		
		$pag_args = array(
			'total_items' => $this->test_count->total,
			'total_pages' => ceil($this->test_count->total/$this->per_page),
			'per_page' => $this->per_page,
		);
		$this->set_pagination_args($pag_args);
		
		//selection
		$pagenum = $this->get_pagenum();
		$from = intval(($pagenum-1)*$this->per_page);
		$per_page = intval($this->per_page);
		
		$filter = $this->current_filter();
		
		$select = "SELECT * FROM $wpdb->mtdata";
		$sorting = " ORDER BY test_date DESC LIMIT $from, $per_page";		
		$where = '';
		
		switch($filter){
			case 'consult':
				$where = " WHERE consult_request > 0";
				break;
			
			case 'plain':
				$where = " WHERE consult_request = 0";
				break;
		}
		
		
		$items = $wpdb->get_results($select.$where.$sorting);
		
		//var_dump($items);
		
		$this->items = $items;
	}
	
	
	/**
	 * Table nav
	 **/
	function get_views() {		
		
		$views = array();			
		$page_url = 'edit.php?post_type=question&page=frl_mitest_submissions';
		$vars = array(
			'all'     => __('All %s', 'frl-mitest'),
			'consult' => __('With consultation %s', 'frl-mitest'),
			'plain'   => __('Without consultation %s', 'frl-mitest')
		);
		
		$current_status = $this->current_filter();		
		
		foreach($vars as $status => $label){
			
			if($status != 'all')
				$url = $page_url.'&consult_status='.$status;
			else
				$url = $page_url;
			
			$css = '';
			if($status == $current_status)
				$css = ' class="current"';
			
			$count = 0;
			switch($status){
				case 'all':
					$count = $this->test_count->total;
					break;
				
				case 'consult':
					$count = $this->test_count->consult;
					break;
				
				case 'plain':
					$count = $this->test_count->plain;
					break;
			}
						
			if($count){
				$title = sprintf($label, "<span class='count'>($count)</span>");
				$views[$status] = "<a href='".admin_url($url)."'$css>{$title}</a>";
			}
		}
		
		return $views;		
		
	}
		
	
	function current_filter(){
		
		if(isset($_REQUEST['consult_status']) && !empty($_REQUEST['consult_status']))
			return trim(stripslashes($_REQUEST['consult_status']));
		
		return 'all';
	}
	
	/**
	 * Table
	 **/
	
	function get_columns() {
		
		$columns = array();
		$columns['id']      = 'ID';
		$columns['module']  = __('Module', 'frl-mitest');
		$columns['date']    = __('Date', 'frl-mitest');
		$columns['url']     = __('Website', 'frl-mitest');
		$columns['score']   = __('Score', 'frl-mitest');
		$columns['profile'] = __('Profile', 'frl-mitest');
		$columns['consult'] = __('Consultation', 'frl-mitest');
		
		return $columns;
	}
	
	/* override this method to be able print columns */
	function get_column_info() {
		if ( isset( $this->_column_headers ) )
			return $this->_column_headers;

		$screen = get_current_screen();

		$columns = get_column_headers( $screen ); 
		$hidden = get_hidden_columns( $screen );
		
		if(empty($columns))
			$columns = $this->get_columns();
			
		$_sortable = apply_filters( "manage_{$screen->id}_sortable_columns", $this->get_sortable_columns() );

		$sortable = array();
		foreach ( $_sortable as $id => $data ) {
			if ( empty( $data ) )
				continue;

			$data = (array) $data;
			if ( !isset( $data[1] ) )
				$data[1] = false;

			$sortable[$id] = $data;
		}

		$this->_column_headers = array( $columns, $hidden, $sortable );

		return $this->_column_headers;
	}
	
	
	/**
	 * Row
	 **/
	
	function column_default($item, $colname){
		//fallback
		
		return apply_filters('frl_tests_column_'.$colname, '', $item);
	}
	
	function column_id($item) {
		
		if(isset($item->id))
			return intval($item->id);			
	}
	
	function column_date($item) {
		
		$date = date_i18n('d.m.Y', strtotime($item->test_date));
		echo "<time>{$date}</time>";
	}
	
	function column_module($item) {
		
		$module_id = intval($item->module_id);
		$term = get_term($module_id, 'module');
		if(empty($term))
			return $this->empty_mark;
		
		return frl_text_for($term->name, 'print');		
	}
	
	function column_url($item) {
		
		if(!isset($item->site_url) || empty($item->site_url))
			return $this->empty_mark;
		
		$url = esc_url($item->site_url);
		$parts = parse_url($url);
		$txt = frl_text_for($parts['host'], 'print');
		
		return "<a href='{$url}' target='_blank'>{$txt}</a>";
	}
	
	function column_score($item) {
		
		if(!isset($item->site_score) || empty($item->site_score))
			return $this->empty_mark;
		
		return intval($item->site_score);
	}
	
	function column_profile($item) {
		
		$txt = __('View response profile', 'frl-mitest');		
		$url = frl_get_test_profile_link($item->id, $item->module_id);	
		
		return "<a href='{$url}' target='_blank'>{$txt}</a>";
	}
	
	function column_consult($item) {
		
		$optin = intval($item->consult_request);
		if($optin == 0)
			return "<p>".__('No request for consultation', 'frl-mitest')."</p>";
		
		$out = "<p>".__('Request for consultation', 'frl-mitest')."</p>";
		
		$name = frl_text_for($item->consult_name, 'print');
		$email = frl_text_for($item->consult_email, 'print');
		
		$out .= "<p><b>".__('Contact data', 'frl-mitest')."</b></p>";
		$out .= "<p>".sprintf(__('Name: %s', 'frl-mitest'), $name)."<br>".sprintf(__('Email: %s', 'frl-mitest'), $email)."</p>";
		
		return $out;
	}
	
	
	
	/**
	 * Helpers
	 **/
	
	protected function _get_count() {
		global $wpdb;
		
		$this->test_count = new stdClass();
		
		$this->test_count->total = intval($wpdb->get_var("SELECT COUNT(id) FROM $wpdb->mtdata"));
		$this->test_count->consult = intval($wpdb->get_var("SELECT COUNT(id) FROM $wpdb->mtdata WHERE consult_request > 0"));
		$this->test_count->plain = $this->test_count->total - $this->test_count->consult;
	}
	
	
} //class end
 
 
?>