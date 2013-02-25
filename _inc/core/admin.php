<?php
/**
 * FRL MItest Admin UI
 *
 * @package Frl_mitest
 **/


class FRL_Mitest_Admin {	
	
	private static $instance = NULL;
		
    
	/**
	 * Inits
	 **/
    private function __construct() {
                
        add_action('admin_enqueue_scripts', array($this, 'enqueue_cssjs'));
		add_filter('admin_body_class', array($this, 'admin_body_class'));
		
		add_action('admin_menu', array($this, 'change_menu_labels'));
		add_action('admin_menu', array($this, 'admin_menu_setup'));
		add_filter('enter_title_here', array($this, 'enter_title_here_filter'), 2, 2);		
		
		add_action('wp_ajax_update_post_menu_order', array($this, 'update_post_menu_order'));
		
		add_filter('plugin_action_links', array($this, 'settings_action'), 2, 4);		
    }
        
    public static function get_instance(){
        
        if (NULL === self :: $instance)
			self :: $instance = new self;
					
		return self :: $instance;
    }
	
	
	/**
	 * CSS JS */
	function enqueue_cssjs($hook){
		global $post;
		
		//var_dump($hook);
		//test for correct hook
		$test = false;
		if($hook == 'question_page_frl_mitest_options' || $hook == 'question_page_frl_mitest_submissions')
			$test = true;
		elseif(in_array($hook, array('post-new.php', 'edit.php' )) && isset($_GET['post_type']) && $_GET['post_type'] == 'question')
			$test = true;
		elseif($hook == 'post.php' && $post->post_type == 'question')
			$test = true;
		elseif($hook == 'edit-tags.php' && isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'module' )
			$test = true;
		
		if(!$test)
			return;
		
		/* media js for options page */
		if($hook == 'question_page_frl_mitest_options'){
			wp_enqueue_media();
			
			$ch_js = frl_mitest_plugin_url().'/_inc/js/custom-header.js';
			wp_enqueue_script('frl-mitest-cheader', $ch_js, array('jquery'), FRL_MITEST_VERSION, true);
		}
		
		
		$css = frl_mitest_plugin_url().'/_inc/css/admin.css';
		wp_enqueue_style('frl-mitest-admin', $css, array(), FRL_MITEST_VERSION);
		
		$js = frl_mitest_plugin_url().'/_inc/js/admin.js';
		wp_enqueue_script('frl-mitest-admin', $js, array('jquery', 'jquery-ui-dialog'), FRL_MITEST_VERSION, true);
		
		$scodes_data = array(
			'scTitle'     => __('Shortcodes', 'frl-mitest'),
			'scCancel'    => __('Cancel', 'frl-mitest'),
			'scInsert'    => __('Insert', 'frl-mitest'),
			'scError' => __('Content is not avaliable', 'frl-mitest'),
			'moError' => __('Saving is not possible', 'frl-mitest')
		);
		
		wp_localize_script('frl-mitest-admin', 'mitestL10', $scodes_data);
	}
	
	function admin_body_class($class){
		global $hook_suffix;
		
		$screen = get_current_screen();			
		$test_class = preg_replace('/[^a-z0-9_-]+/i', '-', $hook_suffix); //do not duplicate
		
		if($test_class != $screen->id)
			$class = (empty($class)) ? $screen->id : ' '.$screen->id;
		
		return $class;
	}
	
	
	/**
	 * Labels
	 **/
	function change_menu_labels(){ /* change adming menu labels */
		global $menu, $submenu;		
		
		if(!isset($submenu['edit.php?post_type=question']))
			return;
		
		$post_type_object = get_post_type_object('question');
		$label = $post_type_object->labels->name;
		
		$slug = 'edit.php?post_type=question';
		foreach($submenu[$slug] as $i => $menu_obj){
			if($menu_obj[2] == $slug)
				$submenu[$slug][$i][0] = $label;
		}
		
	}
		
	function enter_title_here_filter($label, $post){
	
		if($post->post_type == 'question')
			$label = __('Enter question text', 'frl-mitest');
		
		return $label;
	}
	
	
	/**
	 * Menu order inline
	 **/
	
	/* ajax responser */
	function update_post_menu_order(){
		global $wpdb;
		
		check_ajax_referer('bulk-posts', '_frl_ajax_nonce');
			
		if(!isset($_POST['post_id']) || !isset($_POST['menu_order']))
			die('-1');
		
		$post_id = intval($_POST['post_id']);
		$menu_order = intval($_POST['menu_order']);
		
		if($post_id <= 0)
			die('-1');
			
		/* direct update for not to break some other data */
		$wpdb->update($wpdb->posts, array('menu_order' => $menu_order), array('ID'=> $post_id), array('%d'), array('%d'));
		echo $menu_order;
		die();
	}
		
	/* html in admin */
	static function menu_order_inline_markup($post_object) {
		
		$morder = intval($post_object->menu_order);
		$id = intval($post_object->ID);
		
		$out = '';
		$out .= "<p class='index-inline'>{$morder}</p>";
				
		$out .= "<div class='index-inline-edit'>";
		$out .= "<input type='text' maxlength='4' name='att_morder-{$id}' value='{$morder}' data-post_id='{$id}'/>";
		$out .= "<input name='arr_morder_save' type='button' value='OK' />"; //OK
		$out .= "<input name='arr_morder_cancel' type='button' value='X' />"; //Cancel
		$out .= "</div>";
		
		return $out;
	}
	
	
	/**
	 * Admin pages
	 **/
	
	/* setting link on plugin screen */
	function settings_action($actions, $plugin_file, $plugin_data, $context) {
		
		if(false !== strpos($plugin_file, 'frl-mitest')){
			$url = admin_url('edit.php?post_type=question&page=frl_mitest_options');
			$txt = __('Settings', 'frl-mitest');
			$title = esc_attr(__('Visit plugin\'s settings page', 'frl-mitest'));
			$actions['settings'] = "<a href='{$url}' title='{$title}'>{$txt}</a>";
		}
		
		
		return $actions;
	}
	
	/* configuration presets */
	protected function _config_admin_pages(){
		
		$pages['options'] = array(
			'parent'     => 'edit.php?post_type=question',
			'title'      =>  __('MItest Settings', 'frl-mitest'),
			'menu_title' => __('Settings', 'frl-mitest'),
			'capability' => 'edit_others_posts',
			'slug'       => 'frl_mitest_options'
		);
		
		$pages['submissions'] = array(
			'parent'     => 'edit.php?post_type=question',
			'title'      =>  __('MItest submissions', 'frl-mitest'),
			'menu_title' => __('Submissions', 'frl-mitest'),
			'capability' => 'edit_others_posts',
			'slug'       => 'frl_mitest_submissions'
		);
		
		return $pages;
	}
	
	/* add options page */
	function admin_menu_setup() {
		
		$pages = $this->_config_admin_pages();
		
		if(empty($pages))
			return;
		
		foreach($pages as $id => $page) {
			
			$callback = "{$id}_page_screen";
			add_submenu_page(
						$page['parent'],
						$page['title'],
						$page['menu_title'],
						$page['capability'],
						$page['slug'],
						array($this, $callback)
						);
		}		
	}
	
	/* options page content */
	function options_page_screen() {
		
		$pages = $this->_config_admin_pages();
		$page = $pages['options'];
		
		/* capability test */
		if (!current_user_can($page['capability']) ) 
            wp_die(__('Sorry, but you do not have permissions to access this page.', 'frl-mitest'));
			
		/* submission */
		if(isset($_REQUEST['frl_mitest_options_submit'])) {
			
			check_admin_referer('frl_mitest_options', '_frl_nonce');
			
			$core = frl_mitest_core();
			$core->save_options();
		}
		
		/* output */
		$options_slug = $page['slug'];
		$page_title = $page['title'];
		$parent_slug = $page['parent'];
		$faction = "{$parent_slug}&page={$options_slug}";
		
		/* current stage */
		$current_stage = (isset($_REQUEST['stage']) && !empty($_REQUEST['stage'])) ? trim($_REQUEST['stage']) : 'general';		
	?>
	
		<div class="wrap">
		<?php screen_icon('options-general'); ?>
		<h2><?php echo $page_title;?></h2>
		
		<?php echo $this->options_stages_menu($current_stage);?>
		
	<?php if($current_stage == 'general'):?>
		<form method="post" action="<?php echo admin_url($faction); ?>" id="frl-options-form" class="frl-frame-form">
		
		<?php wp_nonce_field('frl_mitest_options', '_frl_nonce'); ?>
		
		<fieldset class="field text from-email">
            
			<label for="from_email"><?php _e('"From" email', 'frl-mitest');?></label>
            <input type="email" name="from_email" id="from_email" value="<?php echo frl_text_for(frl_mitest_get_option('from_email'), 'edit');?>" class="widefat">            
			<div class="help"><p><?php _e('Email to be used in "from" field in messages sent by plugin', 'frl-mitest');?></p></div>
						
        </fieldset>
		
		<fieldset class="field text notify-email">
            
			<label for="notify_email"><?php _e('Email for notifications', 'frl-mitest');?></label>
            <input type="email" name="notify_email" id="notify_email" value="<?php echo frl_text_for(frl_mitest_get_option('notify_email'), 'edit');?>" class="widefat">            
			<div class="help"><p><?php _e('Email to be notified about test\'s submissions', 'frl-mitest');?></p></div>
						
        </fieldset>
		
		<fieldset class="field textarea email-to-user">
            
			<label for="email_to_user"><?php _e('Email to participant', 'frl-mitest');?></label>
            <textarea name="email_to_user" id="email_to_user" rows="7"><?php echo frl_html_for(frl_mitest_get_option('email_to_user'), 'edit');?></textarea>            
			<div class="help"><p><?php _e('Text of email to user with test\'s results. Use [link] placeholder.', 'frl-mitest');?></p></div>
						
        </fieldset>
		
		<fieldset class="field textarea credentials">
            
			<label for="credentials"><?php _e('Credentials', 'frl-mitest');?></label>
            <textarea name="credentials" id="credentials"><?php echo frl_html_for(frl_mitest_get_option('credentials'), 'edit');?></textarea>            
			<div class="help"><p><?php _e('Text to be displayed as credentials information', 'frl-mitest');?></p></div>
						
        </fieldset>
		
		<fieldset class="field textarea common-rules">
            
			<label for="common_rules"><?php _e('Common Rules', 'frl-mitest');?></label>
            <textarea name="common_rules" id="common_rules" rows="7"><?php echo frl_html_for(frl_mitest_get_option('common_rules'), 'edit');?></textarea>            
			<div class="help"><p><?php _e('Text of common for all modules testing rules to be displayed on start', 'frl-mitest');?></p></div>
						
        </fieldset>
		
		<fieldset class="field custom-header">
			<label><?php _e('Testing page header', 'frl-mitest');?></label>
			
			<?php
				$h_id = intval(frl_mitest_get_option('header_id', 0));
				$img = '';
				
				if($h_id > 0)
					$img = wp_get_attachment_image($h_id, 'full');
					
				$css = (!empty($img)) ? 'header-wrapper has-image' : 'header-wrapper';
			?>
			
			<div class="<?php echo $css;?>">
				<a id="choose-from-library-link" class="button"				
				data-choose="<?php esc_attr_e( 'Choose a Custom Header' ); ?>"
				data-update="<?php esc_attr_e( 'Set as header' ); ?>"><?php _e('Choose Image', 'frl-mitest'); ?></a>
				
				<a id="remove-header-image" class="delete"><?php _e('Remove Image', 'frl-mitest'); ?></a>
				
				<input type="hidden" name="header_id" value="<?php echo $h_id;?>">
				<div id="header-holder"><?php echo $img;?></div>				
				
			</div>
			
		</fieldset>
		
		<p class="submit">
			<input type="submit" name="frl_mitest_options_submit" value="<?php _e('Save options', 'frl-mitest'); ?>" class="button-primary">
		</p>
		
		</form>
		
	<?php else:  ?>
			
		<?php $this->help_iframe(); ?>
			
	<?php endif;?>
	
		</div><!-- close .wrap -->
	<?php 
	}
	
	/* options stages */
	function options_stages_menu($current_stage = 'general'){
				
		$menu['general'] = array(
				'label' => __('General parameters', 'frl-mitest' ),
				'css' => ($current_stage == 'general') ?'nav-tab nav-tab-active':'nav-tab'
		);
			
		$menu['help'] = array(
				'label' => __('Help', 'frl-mitest' ),
				'css' => ($current_stage == 'help') ?'nav-tab nav-tab-active':'nav-tab'
		);
		
		$url = admin_url('edit.php?post_type=question&page=frl_mitest_options');
		
		$out  = '<div class="nav-tab-wrapper menu-tabs">';
			
		foreach($menu as $stage => $item) {
			$css = $item['css'];
			if($stage != 'general')
				$url = add_query_arg('stage', $stage, $url);
							
			$label = $item['label'];
			$out .= "<a href='{$url}' class='{$css}'>{$label}</a>";								
		}
			
		$out .="</div>";	
					
		return $out;
	}
	
	/* print help iframe */
	function help_iframe(){
		
		$src = '';
		$locale = get_locale();
		
		$l_path = frl_mitest_plugin_dir().'/_inc/templates/help-'.$locale.'.html';
		$path = frl_mitest_plugin_dir().'/_inc/templates/help.html';
		
		if(file_exists($l_path)){
			$src = frl_mitest_plugin_url().'/_inc/templates/help-'.$locale.'.html';
			
		} elseif(file_exists($path)) {
			$src = frl_mitest_plugin_url().'/_inc/templates/help.html';
			
		}
		
	?>
		<div id="mitest-help">
		<div class='frame-file'><a href='<?php echo esc_url($src);?>' target='_blank'><?php _e('View in separate window', 'frl-mitest');?></a></div>
		<div class="frame-holder"><iframe src='<?php echo esc_url($src);?>'></iframe></div>		
		</div>
	<?php
	}
	
	
	/* submissions page content */
	function submissions_page_screen() {
		global $message;
		
		$pages = $this->_config_admin_pages();
		$page = $pages['submissions'];
		
		/* capability test */
		if (!current_user_can($page['capability']) ) 
            wp_die(__('Sorry, but you do not have permissions to access this page.', 'frl-mitest'));
		
		/* actions */
		$message = '';
		
		/* output */
		$page_slug = $page['slug'];
		$page_title = $page['title'];
		$parent_slug = $page['parent'];
		
		/* instance of a table view class */
		require_once(dirname(__FILE__).'/list-table.php');
		$list_table = new FRL_Tests_List_Table();
				
		$list_table->prepare_items();
	?>
		<div class="wrap">
			
			<?php screen_icon('users'); ?>
			<h2><?php echo $page_title ?></h2>
			
			<?php if ( !empty($message) ) : ?>
				<div id="message" class="updated"><p><?php echo $message; ?></p></div>
			<?php endif; ?>
						
			<?php $list_table->views(); ?>
		
			<form id="frl-submissions-form" class="frl-frame-form" action="" method="post">
				<input type="hidden" name="page" value="<?php echo $page_slug;?>" />
				   
				<?php $list_table->display(); ?>
			
				<div id="ajax-response"></div>
			   
				<br class="clear" />
		
			</form>
		
		</div>
	
	<?php
	}
	
	
	
 
} //class end
?>