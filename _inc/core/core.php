<?php
/**
 * FRL MItest Core component
 *
 * @package Frl_mitest
 */

class FRL_Mitest {
    
	var $cpt = 'question';
	var $tax = 'module';
	var $object_class = 'FRL_Question_Object';
	var $tax_class = 'FRL_Module_Object';
	
		
	var $options_key = "frl_mitest_options";
	var $options;
	var $table;
	
    private static $instance = NULL;
    
    private function __construct() {
		
		/* inits */
		add_action('init', array($this, 'custom_table'));
		add_action('init', array($this, 'formatting_filters'));		
		
		/* cpt and ct */   
        add_action('init', array($this, 'register_post_type'));
		add_action('init', array($this, 'register_taxonomy'));		
		
		/* default page*/
		add_action('frl_mitest_activation_actions', array($this, 'create_default_page'));
		
		/*cleaning */
		add_action('frl_mitest_activation_actions', array($this, 'rewrites_flush'));
		add_action('frl_mitest_deactivation_actions', array($this, 'rewrites_flush'));
		
		/* ajax */
		add_action('wp_ajax_store_test', array($this, 'store_test_action'));
		add_action('wp_ajax_nopriv_store_test', array($this, 'store_test_action'));
		
		add_action('wp_ajax_submit_test', array($this, 'submit_test_action'));
		add_action('wp_ajax_nopriv_submit_test', array($this, 'submit_test_action'));
    }
    
	
    /* get core instance */
    public static function get_instance(){
        
        if (NULL === self :: $instance)
			self :: $instance = new self;
					
		return self :: $instance;
    }
	
	/**
	 * Activation & Deactivation
	 **/
	
	function rewrites_flush(){
		
		flush_rewrite_rules();
	}
	
	/* default page */
	function is_default_page_created(){
		/* we will check for own mark in DB
		 * user can alter or even delete our page */
		
		$test = get_option('_mitest_default_page', 0);
		if($test > 0)
			return true;
		
		return false;
	}
	
	function create_default_page(){			
		
		if($this->is_default_page_created())
			return;
		
		$content = __('We offer you a series of test to benchmark your website against best practices and approaches for site development', 'frl-mitest');
		$shortcode = "\n\n[frl_mitest_modules]";
		
		$title = __('Tests for non-profit website', 'frl-mitest');
		
		$args = array(
			'post_status' => 'draft',
			'post_type' => 'page',					
			'post_content' => $content.$shortcode,
			'post_title' => $title,
			'post_name' => 'onwam-tests');
		
		$pid = wp_insert_post($args);
		if($pid > 0)
			update_option('_mitest_default_page', 1);
	}
	
	/**
	 * Options
	 **/
	
	/* options configuration */	
	protected function _default_options() {
		
		return array(
			'credentials'  => '',
			'common_rules' => '',
			'header_id'    => 0,
			'from_email'   => '',
			'notify_email' => '',
			'email_to_user' => ''
		);	
	}
	
	/* load options from DB */
    function get_options(){  		
		
        $options = $this->_default_options();		
		$saved_options = get_option($this->options_key);
		
		if(!empty($saved_options)){ foreach ($saved_options as $key => $option) { 
			$options[$key] = $option;			
		}}
		
		if(empty($options['version']) || $options['version'] != FRL_MITEST_VERSION){
			//upgrade code could be here
			
			$options["version"] = FRL_MITEST_VERSION;
			update_option($this->options_key, $options);
		}

		$this->options = $options;
    }
    
    
    /* write into DB */
    function save_options() {
		
		$this->options = $this->_default_options(); //reset
							
		if(isset($_REQUEST['credentials']) && !empty($_REQUEST['credentials']))
			$this->options['credentials'] = frl_html_for($_REQUEST['credentials'], 'save');
		
		if(isset($_REQUEST['common_rules']) && !empty($_REQUEST['common_rules']))
			$this->options['common_rules'] = frl_html_for($_REQUEST['common_rules'], 'save');
			
		if(isset($_REQUEST['email_to_user']) && !empty($_REQUEST['email_to_user']))
			$this->options['email_to_user'] = frl_html_for($_REQUEST['email_to_user'], 'save');
		
		if(isset($_REQUEST['header_id']) && $_REQUEST['header_id'] > 0)
			$this->options['header_id'] = intval($_REQUEST['header_id']);
			
		if(isset($_REQUEST['from_email']) && !empty($_REQUEST['from_email']))
			$this->options['from_email'] = frl_text_for($_REQUEST['from_email'], 'save');
			
		if(isset($_REQUEST['notify_email']) && !empty($_REQUEST['notify_email']))
			$this->options['notify_email'] = frl_text_for($_REQUEST['notify_email'], 'save');
		
		$this->options['version'] = FRL_MITEST_VERSION;
		update_option($this->options_key, $this->options);
		
		//rebuild in fav of filters
		$this->get_options();
	}
	
	/* get single option by key */
	function get_option($key, $default = '') {
		
		if(empty($this->options))
			$this->get_options();
			
		return (isset($this->options[$key])) ? $this->options[$key] : $default;		
	}
	
	/**
	 * Custom table
	 **/
	
	function custom_table(){
		
		//load compoments
		$path = frl_mitest_plugin_dir().'/_inc/core/dbtable.php';
		require_once($path);
		
		//create instance
		$name = 'mtdata';
        $columns = "
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		module_id bigint(20) unsigned NOT NULL DEFAULT '0',
		test_date datetime NOT NULL default '0000-00-00 00:00:00',
		site_url varchar(255) NOT NULL default '',
		site_score int(11) NOT NULL default '0',
		site_profile longtext,
		consult_request tinyint(2) NOT NULL default '0',
        consult_name varchar(255) NOT NULL default '',
		consult_email varchar(255) NOT NULL default '',
		comment_text longtext,
        PRIMARY KEY (id),
		KEY module_id (module_id)
        ";
		
		if(class_exists('FRL_Dbtable'))
			$this->table = new FRL_Dbtable($name, $columns);
	}
	
	
	/**
	 * Post object
	 *
	 * register CPT and perform functionality injections
	 **/
	function register_post_type() {
		
		$labels = array(
			'name' => __('Questions', 'frl-mitest'),
			'singular_name' => __('Question', 'frl-mitest'),
			'add_new' => __('Add new question', 'frl-mitest'),
			'add_new_item' => __('Add question', 'frl-mitest'),
			'edit_item' => __('Edit question', 'frl-mitest'),
			'new_item' => __('New question', 'frl-mitest'),
			'view_item' => __('View question', 'frl-mitest'),
			'search_items' => __('Search questions', 'frl-mitest'),
			'not_found' =>  __('No questions found', 'frl-mitest'),
			'not_found_in_trash' => __('No questions found in Trash', 'frl-mitest'),
			'menu_name' => 'MITEST' 
		);		
		
		$args = array(
			'labels' => $labels,
			'public' => true,    
			'query_var' => true,
			'rewrite' => false,
			'show_in_menu' => true,
			'show_in_admin_bar' => false,
			'show_in_nav_menus' => false,			
			'hierarchical' => false,
			'menu_position' => 20,
			'supports' => array('title','editor'),    
			'taxonomies' => array(),
			'has_archive' => false ,
			'exclude_from_search' => true,
			'register_meta_box_cb' => array($this, 'cpt_meta_boxes')
		);
		
		register_post_type($this->cpt, $args);
		add_filter('post_updated_messages', array($this, 'filter_updated_messages'), 10);
		
		/* filter get_permalink result */
		add_filter('post_link', array($this, 'get_permalink_filter'), 2, 3);
		
		/* edit screen */		
		add_filter('get_sample_permalink_html', array($this, 'sample_permalink_html'), 2, 4);		
		add_action('save_post', array($this, 'on_save_actions'), 2, 2);
		
		/* actions for table UI */		
		add_filter('manage_'.$this->cpt.'_posts_columns', array($this, 'manage_columns_names'));
		add_filter('manage_edit-'.$this->cpt.'_sortable_columns', array($this, 'manage_sortable_columns')); 
		add_action('manage_'.$this->cpt.'_posts_custom_column', array($this, 'manage_columns_content'), 2, 4);		
		add_filter('post_row_actions', array($this, 'filter_inline_actions'), 2, 2);
		
		
		do_action($this->cpt.'_registration_actions');
	}
	
	
	/**
	 * CPT related filters and actions
	 **/
	
	protected function _get_cpt_instance($post_object = null){
		
		$class = $this->object_class;
		if(!class_exists($class))
			return;
		
		return new $class($post_object);
	}	
	
	/* add metaboxes */
	function cpt_meta_boxes() {
		global $post;			
		
		$instance = $this->_get_cpt_instance($post);		
		if(method_exists($instance, 'set_metaboxes'))		
			$instance->set_metaboxes();	
	}
	
	/* correct update messages */
	function filter_updated_messages($messages){
		global $post, $post_ID;
		
		$cpt_messages = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __('Question updated', 'frl-mitest'),
			2 => __('Custom field updated', 'frl-mitest'),
			3 => __('Custom field deleted', 'frl-mitest'),
			4 => __('Question updated', 'frl-mitest'),    
			5 => isset($_GET['revision']) ? __('Question restored to revision', 'frl-mitest') : false,
			6 => __('Question published', 'frl-mitest'),
			7 => __('Question saved', 'frl-mitest'),
			8 => __('Question submitted', 'frl-mitest'),
			9 => __('Question scheduled', 'frl-mitest'),
			10 => __('Question draft updated', 'frl-mitest')
		);
				
		$messages[$this->cpt] = $cpt_messages;
		
		return $messages;		 
	}
	
	/* no permalink for question CPT */
    function get_permalink_filter($permalink, $post, $leavename){
		
		if($post->post_type != $this->cpt)
			return $permalink;
		
		return ''; //no permalink for separate question
	}
	
	/* no permalink for question CPT */
	function sample_permalink_html($return, $id, $new_title, $new_slug){
		
		$post_type = get_post_type($id);
		if($post_type != $this->cpt)
			return $return;
		
		return '';
	}
		
	/* saver */
	function on_save_actions($post_id, $post){
		
		if($post->post_type != $this->cpt)
			return;
		
		$instance = $this->_get_cpt_instance($post);
		if(method_exists($instance, 'save'))
			$instance->save();
	}
		
	/* table UI */
	function manage_columns_names($columns){
		
		$unsort = $columns;
		$columns = array();
		
		if(isset($unsort['cb'])){
			$columns['cb'] = $unsort['cb'];
			unset($unsort['cb']);
		}
		
		if(isset($unsort['title'])){
			$columns['title'] = $unsort['title'];
			unset($unsort['title']);
		}
		
		$columns['menu_order'] = __('Sorting', 'frl-mitest');	
		
		if(!empty($unsort))
			$columns = array_merge($columns, $unsort);
		
		//last one
		if(!isset($columns['id']))
			$columns['id'] = "ID";		
		
		return $columns;
	}
	
	function manage_sortable_columns($columns){
		
		$columns['menu_order'] = 'menu_order';
		
		return $columns;
	}
			
	/* print cell content for posts table column */
	function manage_columns_content($column_name, $post_ID) {
				
		$instance = $this->_get_cpt_instance($post_ID); 
		if(method_exists($instance, 'manage_columns_content'))
			$instance->manage_columns_content($column_name);
	}
	
	/* filter inline actions in cpt table*/
	function filter_inline_actions($actions, $post){
				
		if($this->cpt != $post->post_type)
			return $actions;
				
		//no own page for question CPT
		unset($actions['view']);				
		
		//inline editing for admins only
		if(!current_user_can('manage_options') && isset($actions['inline hide-if-no-js']))
			unset($actions['inline hide-if-no-js']);
			
		return $actions;
	}
	
	
	/**
	 * Register taxonomy
	 **/
	
	function register_taxonomy() {
		
		$labels = array(
			'name' => __('Modules', 'frl-mitest'),
			'singular_name' => __('Module', 'frl-mitest'),
			'search_items' => __('Search modules', 'frl-mitest'),
			'popular_items' => __('Frequent modules', 'frl-mitest'),
			'all_items' => __('All modules', 'frl-mitest'),
			'edit_item' => __('Edit module', 'frl-mitest'),
			'update_item' => __('Update module', 'frl-mitest'),
			'add_new_item' => __('Add new module', 'frl-mitest'),
			'new_item_name' => __('New module name', 'frl-mitest'),
			'separate_items_with_commas' => __('Separate modules with commas', 'frl-mitest'),
			'add_or_remove_items' => __( 'Add or remove modules', 'frl-mitest'),
			'choose_from_most_used' => __('Choose from the most used modules', 'frl-mitest')		
		);
		
		$register_args = array(
			'labels' => $labels,
			'public' => true,
			'show_ui' => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => false,
			'show_tagcloud' =>false,
			'hierarchical' => false,
			'rewrite' => array(
				'slug' => 'modules',
				'with_front' => false,
				'hierarchical' => false
			)
		);
		
		register_taxonomy($this->tax, array($this->cpt), $register_args);
		
		 /* add fields to edit form */
		add_action($this->tax . '_add_form_fields', array($this, 'create_form_fields')); //$taxonomy
		add_action($this->tax . '_edit_form_fields', array($this, 'edit_form_fields'), 2, 2); //$tag, $taxonomy		
		
        /* add actions for CRUD */
		add_action('create_term', array($this, 'create_term_actions'), 2, 3); //$term_id, $tt_id, $taxonomy
		add_action('edit_term', array($this, 'edit_term_actions'), 2, 3); //$term_id, $tt_id, $taxonomy	
		add_action('delete_term', array($this, 'delete_term_actions'), 2, 3);// $term, $tt_id, $taxonomy before delete_terms_meta!!!
		
		/* taxonomy page */
		add_filter('template_include', array($this, 'load_module_template'));
		add_action('pre_get_posts', array($this, 'correct_tax_query'));
		
		/* filter inline actions in table UI */
		add_filter($this->tax."_row_actions", array($this, 'filter_inline_tax_actions'), 2, 2); //$actions, $tag
		
		do_action($this->tax.'_registration_actions');
	}
	
	/** taxonomy form **/	
	protected function _get_tax_instance($term = null){
				
		$class = $this->tax_class;
		if(!class_exists($class))
			return;
		
		return new $class($term);	
	}
	
	/* add term custom fields */
	function create_form_fields(){
		
		$tax_instance = $this->_get_tax_instance();
		$tax_instance->create_form_fields(); 
	}
	
	/* edit term custom fields */
	function edit_form_fields($tag, $taxonomy){
		
		if($taxonomy != $this->tax)
            return;
		
		$tax_instance = $this->_get_tax_instance($tag);
		$tax_instance->edit_form_fields();
	}
	
	/* save actions on term creation */
	function create_term_actions($term_id, $tt_id, $taxonomy){
		
		if($taxonomy != $this->tax)
            return;
		
		$tax_instance = $this->_get_tax_instance($term_id);
		$tax_instance->save();
	}
	
	/* save actions on term update */
	function edit_term_actions($term_id, $tt_id, $taxonomy){
		
		if($taxonomy != $this->tax)
            return;
		
		$tax_instance = $this->_get_tax_instance($term_id);
		$tax_instance->save();
	}
	
	/* actions on term delete */
	function delete_term_actions($term, $tt_id, $taxonomy){
		
		if($taxonomy != $this->tax)
            return;
		
		$tax_instance = $this->_get_tax_instance($taxonomy, $term);
		$tax_instance->delete();
	}
	
	
	
	/** correct query **/
	function correct_tax_query($query){
		
		if(!isset($query->query[$this->tax]))
			return $query;
		
		$query->query['orderby'] = 'menu_order';
		$query->query['order'] = 'ASC';
		$query->query['post_type'] = $this->cpt;
		
		$query->query_vars['post_type'] = $this->cpt;
		$query->query_vars['orderby'] = 'menu_order';
		$query->query_vars['order'] = 'ASC';
		
	}
	
	/** filter inline actions in table UI **/
	function filter_inline_tax_actions($actions, $tag){
		
		$label = __('Preview', 'frl-mitest');
		$link = get_term_link($tag);
		$link = add_query_arg('mitest_profile', 'preview', $link);
		
		$actions['modeview'] = "<a href='{$link}'>{$label}</a>";
		
		return $actions;
	}
	
	
	/**
	 * Formatting filters
	 **/
	
	function htmlspecialchars($output) { //chars -> ent    
    
		return htmlentities($output, ENT_NOQUOTES, 'UTF-8', TRUE);     
	}
	
	function kses_text($output){
		
		return wp_kses($output , 'entities'); 
	}
	
	function kses_html($output){
		
		return wp_kses($output , 'post'); 
	}
	
	
	function formatting_filters(){
	
		//for edit
		add_filter('html_for_edit', 'stripslashes');
		add_filter('html_for_edit', array($this, 'htmlspecialchars'));
		add_filter('text_for_edit', 'stripslashes');
		add_filter('text_for_edit', array($this, 'htmlspecialchars'));		
		
		//for save
		add_filter('text_for_save', 'strip_tags');    
		add_filter('text_for_save', array($this, 'kses_text'));
		add_filter('html_for_save', array($this, 'kses_html'));    
				
		//for print
		add_filter('text_for_print', 'sanitize_text_field');
		add_filter('text_for_print', 'strip_shortcodes');
		add_filter('text_for_print', 'stripslashes');
		add_filter('text_for_print', 'convert_chars');
		add_filter('text_for_print', 'wptexturize');
			
		add_filter('html_for_print', 'stripslashes');	
		add_filter('html_for_print', 'convert_chars');
		add_filter('html_for_print', 'wptexturize');
		add_filter('html_for_print', 'wpautop');		
		add_filter('html_for_print', 'force_balance_tags');
		add_filter('html_for_print', 'shortcode_unautop');	
		add_filter('html_for_print', 'do_shortcode');	
	}
	
	
	
	/**
	 * Frontend
	 **/
		
	/* load taxonomy template */
	function load_module_template($template){
		
		if(!is_tax())
			return $template;
		
		$term = get_queried_object();
		if(isset($term->taxonomy) && $term->taxonomy == $this->tax){
			
			if(isset($_REQUEST['mitest_profile']) && !empty($_REQUEST['mitest_profile']))
				$template = frl_mitest_plugin_dir().'/_inc/templates/profile.php';
			else
				$template = frl_mitest_plugin_dir().'/_inc/templates/module.php';
		}
		
		$this->setup_frontend(); //custom template settings
		
		return $template;
	}
	
	/* initial cleaning */
	function setup_frontend() {
		
		/* cssjs */
		remove_action('wp_head', 'wp_enqueue_scripts', 1);
		add_action('wp_head', array($this, 'enqueue_scripts'), 1);				
		
		
		/* clear header */
		remove_action('wp_head', 'wp_generator');
		remove_action('wp_head', 'locale_stylesheet'); //own stylesheet only
		remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
		remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
		remove_action('wp_head', 'feed_links', 2);
		remove_action('wp_head', 'feed_links_extra', 3);
		add_filter('show_recent_comments_widget_style', '__return_false');
	}
		
	function enqueue_scripts(){
		
		wp_enqueue_style('frl-foundation', frl_mitest_plugin_url().'/_inc/css/foundation.css', array(), FRL_MITEST_VERSION);
		
		if(!isset($_REQUEST['mitest_profile'])){
			wp_enqueue_style('frl-app', frl_mitest_plugin_url().'/_inc/css/app.css', array(), FRL_MITEST_VERSION);
			
			//jquery.total-storage.min.js
			wp_enqueue_script('frl-reveal', frl_mitest_plugin_url().'/_inc/js/jquery.foundation.reveal.js', array('jquery'), '3.2', true);
			wp_enqueue_script('frl-storage', frl_mitest_plugin_url().'/_inc/js/jquery.total-storage.min.js', array('jquery'), '1.1.2', true);
			wp_enqueue_script('frl-front', frl_mitest_plugin_url().'/_inc/js/front.js', array('jquery', 'jquery-ui-progressbar', 'frl-reveal', 'frl-storage'), FRL_MITEST_VERSION, true);
		
			$js_data = array(
				'ajaxurl' => admin_url('admin-ajax.php')
			);
			wp_localize_script('frl-front', 'frlFront', $js_data);
			
		} else {
			
			wp_enqueue_style('frl-prapp', frl_mitest_plugin_url().'/_inc/css/prapp.css', array(), FRL_MITEST_VERSION);
		}
		
		
		do_action('frl_enqueue_scripts');
	}
	
	
	/**
	 * AJAX
	 **/
	
	function store_test_action(){
		global $wpdb;
		
		check_ajax_referer('frl_mitest_module', '_frl_ajax_nonce');
		
		$selection = (isset($_POST['selection'])) ? trim($_POST['selection']) : '';
		$comments = (isset($_POST['comments'])) ? $_POST['comments'] : array();
		
		$module_id = (isset($_POST['module'])) ? intval($_POST['module']) : 0;
		$url = (isset($_POST['url'])) ? trim($_POST['url']) : '';
		$score = (isset($_POST['score'])) ? intval($_POST['score']) : 0;
		
		$insert_id = 0;
		
		
		if(empty($selection) || empty($url))
			die('-1');
		
		if($module_id == 0)
			die('-1');
		
		//parse selection
		$selection = explode(',', $selection);
		$soptions = array();
		foreach($selection as $q){
			
			if(false === strpos($q, 'question_'))
				continue;
			
			$q = str_replace('question_', '', $q);
			$q = explode('_opt_', $q);
			$q = array_map('intval', $q);		
			
			$soptions[$q[0]][] = $q[1];
		}
		
		//parse comments
		$soptions['comments'] = array();
		if(!empty($comments)){ foreach($comments as $i => $text) {
			
			if(false === strpos($i, 'question_comment_'))
				continue;
			
			$i = str_replace('question_comment_', '', $i);
			$i = explode('_opt_', $i);
			$i = array_map('intval', $i);
			
			$soptions['comments'][$i[0]][$i[1]] = frl_text_for($text, 'save');
		}}
		
		//prepare data		
		$data = array(
			'module_id' => $module_id,
			'test_date' => current_time('mysql'),
			'site_url' => esc_url_raw($url),
			'site_score' => $score,
			'site_profile' => maybe_serialize($soptions),
			'consult_request' => 0,
			'consult_name' => '',
			'consult_email' => ''
		);
		
		$format = array('%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s');
		
		//save
		if(false !== $wpdb->insert($wpdb->mtdata, $data, $format))
			$insert_id = $wpdb->insert_id;
		
		echo $insert_id;	
		die();
	}
	
	function submit_test_action() {		
		global $wpdb;
		
		check_ajax_referer('frl_mitest_module', '_frl_ajax_nonce');
		
		$entry_id = isset($_POST['entry']) ? intval($_POST['entry']) : 0;		
		if($entry_id == 0)
			die('-1');
		
		$data = isset($_POST['test_data']) ? $_POST['test_data'] : array();
		if(empty($data))
			die('-1');
		
		$consult = (isset($data['consult'])) ? intval($data['consult']) : 0;		
		$cname = (isset($data['consultName'])) ? frl_text_for($data['consultName'], 'save') : '';
		$cemail = (isset($data['consultEmail'])) ? sanitize_email($data['consultEmail'], 'save') : '';
		$comment = (isset($data['commentText'])) ? frl_text_for($data['commentText'], 'save') : '';
		$error = false;
		
		if($consult > 0 || !empty($comment)){
			$upd_data = array(
				'consult_request' => $consult,
				'consult_name' => $cname,
				'consult_email' => $cemail,
				'comment_text' => $comment
			);
			if(!$wpdb->update($wpdb->mtdata, $upd_data, array('id' => $entry_id), array('%d', '%s', '%s', '%s'), array('%d')))
				$error = true;
		}
		
		//email to user
		$send_req = (isset($data['sendReq'])) ? intval($data['sendReq']) : 0;
		$remail = (isset($data['sendEmail'])) ? sanitize_email($data['sendEmail']) : '';		
		if($send_req > 0 && !empty($remail)){
			
			if(!$this->email_to_user($remail, $entry_id))
				$error = true;
		}
		
		//email to admin
		if(!$this->email_to_admin($entry_id))
			$error = true;
		
		if($error)
			echo "-1";
		else
			echo "OK";
			
		die();
	}
	
	
	/** email helpers **/
	function email_to_user($email, $entry_id){
		
		//profile link
		$url = frl_get_test_profile_link($entry_id);
		if(empty($url))
			return false;
		
		$headers = array();
		$from_email = frl_text_for(frl_mitest_get_option('from_email'), 'print');
		$from_name = frl_text_for(get_bloginfo('name'), 'print');
		
		if(!empty($from_email))
			$headers[] = "From: {$from_name} <{$from_email}>";
			
		$subject = __('Website test results', 'frl-mitest');		
		$txt = __('Test\'s results', 'frl-mitest');		
		$link = "<a href='{$url}' target='_blank'>{$txt}</a>";
		
		$content = frl_html_for(frl_mitest_get_option('email_to_user'), 'print');
		$content = str_replace('[link]', $link, $content);
		
		add_filter('wp_mail_content_type', create_function('', 'return "text/html";'));
		
		return wp_mail($email, $subject, $content, $headers);
	}
	
	function email_to_admin($entry_id){
				
		$url = frl_get_test_profile_link($entry_id);
		if(empty($url))
			return false;
		
		$headers = array();
		$from_email = frl_text_for(frl_mitest_get_option('from_email'), 'print');
		$from_name = frl_text_for(get_bloginfo('name'), 'print');
		
		if(!empty($from_email))
			$headers[] = "From: {$from_name} <{$from_email}>";
		
		$to = frl_text_for(frl_mitest_get_option('notify_email'), 'print');
		if(empty($to))
			$to = get_option('admin_email', '');
		
		$subject = __('Interactive test submission', 'frl-mitest');
		
				
		$txt = __('Test profile', 'frl-mitest');		
		$link = "<a href='{$url}' target='_blank'>{$txt}</a>";
		
		
		$content = "<p>".__('The new interactive test submission has been recorded today.', 'frl-mitest')."</p>";
		$content .= "<p>".sprintf(__('ID of record: %d.', 'frl-mitest'), $entry_id)."</p>";
		$content .= "<p>".sprintf(__('Link to profile: %s.', 'frl-mitest'), $link)."</p>";
		
		add_filter('wp_mail_content_type', create_function('', 'return "text/html";'));
		
		return wp_mail($to, $subject, $content, $headers);		
	}
	
    
} //class end


class FRL_Question_Object {
		
	var $excerpt_length = 55;	
	var $type_class = 'FRL_Question_Type';
	
	var $ID;
	var $post_object;	
	
	var $post_title;
	var $post_type;
	var $post_content;
	
	var $mdata;
	var $empty_mark;
	
	
	/**
	 * Construction
	 **/
	function __construct($post=null){		
		
		if(is_object($post)){
			
			$this->ID = $post->ID;
			$post_object = $post;
			
		} elseif(is_numeric($post)) {
			$this->ID = intval($post);
			$post_object = get_post($this->ID);
			
		} else {
			$post_object = '';
		}
		
		
		//populate object		
		if(!empty($post_object))
			$this->populate($post_object);		
		
		$this->empty_mark = __('Not set', 'frl-mitest');
	}
	
	/**
	 * Populate properties
	 * function divided in two part in favour of class that need separate meta handling
	 **/
	function populate($post_object){		
		
		//native post properties
		$this->populate_post_object($post_object);
		
		//post metas part
		$this->populate_post_metas($post_object);		
	}
	
	function populate_post_object($post_object){
		
		$this->post_object = $post_object;
		
		if(isset($post_object->post_title))
			$this->post_title = $post_object->post_title;
		
		if(isset($post_object->post_content))
			$this->post_content = $post_object->post_content;
			
		if(isset($post_object->post_type))
			$this->post_type = $post_object->post_type;
	}
	
	function populate_post_metas($post_object) {
		
		/* try to get metas from object */
		if(isset($post_object->metas))
			$metas = $post_object->metas;
		elseif(isset($post_object->ID) && $post_object->ID !== 0)
			$metas = get_post_custom($this->ID);
			
		$meta_key = $this->post_type.'_data';
		
		if(!empty($metas) && isset($metas[$meta_key][0])) {
			$this->mdata = maybe_unserialize($metas[$meta_key][0]);
		}		
	}
	
	
	/**
	 * Medata helpers
	 **/
	
	/**
	 * get specific metadata by key
	 **/
	function get_data($key){
		
		if(empty($key))
			return;
		
		$data = '';
		if(is_array($this->mdata) && key_exists($key, $this->mdata))
			$data = $this->mdata[$key];
			
		return $data;		
	}
	
	/**
	 * shorthand for common data update
	 **/
	function update_data() {
		
		$meta_key = $this->post_type.'_data';
		update_post_meta($this->ID, $meta_key, $this->mdata);
	}
	
	
	/**
	 * Injectors
	 **/
	
	function set_metaboxes(){
				
		add_meta_box('questiondata', __('Additional Data', 'frl-mitest'), array($this, 'data_meta_box'), $this->post_type, 'normal', 'high');	
	}
	
	function data_meta_box() {
		
		$q_type = trim($this->get_data('q_type'));
		$q_linklist = $this->get_data('q_linklist');
		$q_comment = $this->get_data('q_comment');
		
		$menu_order = 0;
		if(isset($this->post_object) && !empty($this->post_object->menu_order))
			$menu_order = intval($this->post_object->menu_order);
			
		//editor settings
		$settings = array(
			'wpautop' => true, 
			'media_buttons' => false,			
			'textarea_rows' => 7,
			'tabindex' => '',
			'teeny' => false, 
			'dfw' => false, 
			'tinymce' => false, 
			'quicktags' => array('buttons' => 'strong,em,link,ul,li') 
		);
	?>
		<fieldset class="field select">
			<label for="q_type" class="wlimit"><?php _e('Type of question', 'frl-mitest');?></label>
			<select id="q_type" name="q_type">
				<option><?php _e('Select type', 'frl-mitest');?></option>
				<option value="type_checkbox" <?php selected('type_checkbox', $q_type);?>><?php _e('Multi-choice', 'frl-mitest');?></option>
				<option value="type_radio" <?php selected('type_radio', $q_type);?>><?php _e('One variant', 'frl-mitest');?></option>
			</select>
		</fieldset>
		
		<fieldset class="field text">
			<label for="menu_order" class="wlimit"><?php _e('Sorting', 'frl-mitest');?></label>
			<input type="text" id="menu_order" name="menu_order" value="<?php echo $menu_order;?>" size="4">
			<span class='help'>
			<?php _e('The index number of question inside the module', 'frl-mitest');?>
			</span>
		</fieldset>
		
		
		<fieldset class="field textarea">		
			<label for="q_comment"><?php _e('Explanation', 'frl-mitest');?></label>
		<?php
			$settings['textarea_name'] = 'q_comment';
			wp_editor($q_comment, 'q_comment', $settings);
		?>
			<p class='help'>
			<?php _e('The text of explanation', 'frl-mitest');?>
			</p>
		</fieldset>
		
		
		<fieldset class="field textarea">
			<label for="q_linklist"><?php _e('Links', 'frl-mitest');?></label>
		<?php
			$settings['textarea_name'] = 'q_linklist';
			wp_editor($q_linklist, 'q_linklist', $settings);
		?>
			<p class='help'>
			<?php _e('The list of related links', 'frl-mitest');?>
			</p>
		</fieldset>	
		
	<?php
	}
	
		
	/**
	 * on save actions
	 **/
	function save(){		
		
		$this->reset_data();
		if(isset($_REQUEST['q_type']) && !empty($_REQUEST['q_type']))
			$this->mdata['q_type'] = frl_text_for($_REQUEST['q_type'], 'save');
			
		if(isset($_REQUEST['q_comment']) && !empty($_REQUEST['q_comment']))
			$this->mdata['q_comment'] = frl_html_for($_REQUEST['q_comment'], 'save');
			
		if(isset($_REQUEST['q_linklist']) && !empty($_REQUEST['q_linklist']))
			$this->mdata['q_linklist'] = frl_html_for($_REQUEST['q_linklist'], 'save');
		
		$this->update_data();
	}	
	
	function reset_data() {
		
		$this->mdata = array(
			'q_type' => '',
			'q_comment' => '',
			'q_linklist' => ''
		);		
	}
	
	/**
	 * posts table columns content print
	 **/
	function manage_columns_content($column_name) {
		
		if($column_name == 'menu_order'){
			echo frl_menu_order_inline_markup($this->post_object);
			
		} elseif($column_name == 'id') {			
			
			echo intval($this->ID);			
		}
	}
	
	/**
	 * Getters
	 **/
	
	function get_question_comment(){
		
		return $this->get_data('q_comment');
	}
	
	function get_question_type_comment() {
		
		$type = $this->get_data('q_type');
		$txt = '';
		
		if($type == 'type_checkbox'){
			
			$txt = __('Select options - everything that suits', 'frl-mitest');
			
		} elseif($type == 'type_radio') {
			
			$txt = __('Select only one option that suits', 'frl-mitest');
		}
		
		return $txt;
	}
	
	function get_question_links(){
		
		return $this->get_data('q_linklist');
	}
	
} //class end


/**
 * Taxonomy
 **/

class FRL_Module_Object {
	
	var $term_id;
	var $term_object;	
	
	var $name;
	var $taxonomy = 'module';
	var $description;
	
	var $mdata;	
	var $empty_mark;
	
	
	/**
	 * Constructor
	 *
	 * @param str $taxonomy - a taxonomy name
	 * @param mixes $term - could be term's object, id or slug
	 **/
	function __construct($term = null){				
		
		
		if(is_object($term)){
			
			$this->term_id = $term->term_id;
			$term_object = $term;
			
		} elseif(is_numeric($term)) {
			$this->term_id = intval($term);
			$term_object = get_term($term, $this->taxonomy);
			
		} elseif(is_string($term)){
			
			$term_object = '';
			$test_term = get_term_by('slug', $term, $this->taxonomy);
			
			if(!empty($test_term)){
				$this->term_id = intval($test_term->term_id);
				$term_object = $test_term;				
			} 
			
		} else {
			$term_object = '';
		}
			
		
		if(!empty($term_object))
			$this->populate($term_object);
			
		$this->empty_mark = __('Not set', 'frl-engine');
	}
	
	/**
	 * Populate properties
	 **/
	function populate($term_object){		
				
		$this->name = $term_object->name;
		$this->description = $term_object->description;
		$this->term_object = $term_object;
		
		//metas 
		$this->mdata = $this->get_term_data();
	}
		
	
	/**
	 * Medata helpers
	 * metadata will be stored as option
	 * term meta - are too heavy in this case
	 **/
	
	/* get all data from options */
	function get_term_data() {
		
		$meta_key = $this->taxonomy.'_data';
		$metas = get_option($meta_key, array());
		$data = (isset($metas[$this->term_id])) ? $metas[$this->term_id] : array();
		
		return $data;
	}
	
	/**
	 * get specific metadata by key
	 **/
	function get_data($key){
		
		if(empty($key))
			return;
		
		$data = (isset($this->mdata[$key])) ? $this->mdata[$key] : '';
		
		return $data;		
	}
	
	/**
	 *	save mdata
	 **/
	function update_data() {
		
		$meta_key = $this->taxonomy.'_data';
		$metas = get_option($meta_key, array());
		$metas[$this->term_id] = $this->mdata;
		
		update_option($meta_key, $metas);		
	}		
		
		
	
	/**
	 * Objectal functions
	 **/
	
	/* fields for create form */
	function create_form_fields(){
		
		$settings = array(
			'wpautop' => true,
			'media_buttons' => true, 
			'textarea_name' => 'module_rules', 
			'textarea_rows' => 4,
			'tabindex' => '',
			'teeny' => false,
			'dfw' => false, 
			'tinymce' => false,
			'quicktags' => array('buttons' => 'strong,em,link') 
		);
	?>
		<tr class="form-field">
        <th scope="row" valign="top"><label><?php _e('Rules', 'frl-mitest'); ?></label></th>
		<td>  
			<?php wp_editor('', 'module_rules', $settings); ?>
			<p><?php _e('Some aditional information to display before test', 'frl-mitest'); ?></p>
        </td>
        </tr>
	
	<?php
		$settings['textarea_name'] = 'module_results';
	?>
		<tr class="form-field">
        <th scope="row" valign="top"><label><?php _e('Scale of results ', 'frl-mitest'); ?></label></th>
		<td>  
			<?php wp_editor('', 'module_results', $settings); ?>
			<p><?php _e('The intervals of scale with description', 'frl-mitest'); ?></p>
        </td>
        </tr>
	<?php
	}
	
	/* fields for edit form */
	function edit_form_fields(){
		
		$rules = $this->get_data('module_rules');
		$results = $this->get_data('module_results');
		
		$settings = array(
			'wpautop' => true,
			'media_buttons' => true, 
			'textarea_name' => 'module_rules', 
			'textarea_rows' => 5,
			'tabindex' => '',
			'teeny' => false,
			'dfw' => false, 
			'tinymce' => false,
			'quicktags' => array('buttons' => 'strong,em,link') 
		);
	?>
		<tr class="form-field">
        <th scope="row" valign="top"><label><?php _e('Rules', 'frl-mitest'); ?></label></th>
		<td>  
			<?php wp_editor(frl_html_for($rules, 'edit'), 'module_rules', $settings); ?>
			<p><?php _e('Some aditional information to display before test', 'frl-mitest'); ?></p>
        </td>
        </tr>
	
	<?php
		$settings['textarea_name'] = 'module_results';
		$settings['textarea_rows'] = 10;
	?>
		<tr class="form-field">
        <th scope="row" valign="top"><label><?php _e('Scale of results', 'frl-mitest'); ?></label></th>
		<td>  
			<?php wp_editor(frl_html_for($results, 'edit'), 'module_results', $settings); ?>
			<p><?php _e('The intervals of scale with description', 'frl-mitest'); ?></p>
        </td>
        </tr>
	<?php
	}
	
	
	/* on save actions */
	function save(){
		
		$this->mdata['module_rules'] = '';
		if(isset($_REQUEST['module_rules']) && !empty($_REQUEST['module_rules']))
			$this->mdata['module_rules'] = frl_html_for($_REQUEST['module_rules'], 'save');
			
		$this->mdata['module_results'] = '';
		if(isset($_REQUEST['module_results']) && !empty($_REQUEST['module_results']))
			$this->mdata['module_results'] = frl_html_for($_REQUEST['module_results'], 'save');
		
		$this->update_data();
	}
	
	/* on delete actions */
	function delete() {
		
	}
	
	/* shortcut for meta */
	function get_rules() {
		
		return $this->get_data('module_rules');
	}
	
	function get_intervals() {
		
		return $this->get_data('module_results');
	}
	
	
	
} //class end




 
?>
