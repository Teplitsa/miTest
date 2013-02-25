<?php
/**
 * Shortcodes Module  
 *
 * @package Frl_mitest
 */

global $frl_shortcode_tags;
class FRL_Mitest_Shortcodes  {
	
	private static $instance = NULL;
	
	
	/**
	 * Inits
	 **/
	function __construct() {
        
        $shortcodes = array(			
			'q_opt' => array('format' => 'closed', 'modal'=> true),
			'm_result' => array('format' => 'closed', 'modal'=> true),
			'res_scale' => array('format' => 'single', 'modal'=> true)	
		);
		
		foreach($shortcodes as $code => $settings){
			
			$this->register_shortcode($code, $settings);
		}
		
		add_action('media_buttons', array($this, 'shortcodes_dropdown'), 15);
		add_action('admin_footer', array($this, 'shortcodes_modals'));
		add_action('wp_ajax_shortcode_modal', array($this, 'shortcode_modal_ajax'));
		
		//shortcodes outside dropdown
		add_shortcode('frl_mitest_modules', array($this, 'frl_mitest_modules_screen'));
    }
		
    public static function get_instance(){
        
        if (NULL === self :: $instance)
			self :: $instance = new self;
					
		return self :: $instance;
    }
        
    
	function register_shortcode($code, $settings) {
		
		$modal_method = $code."_modal";
		if(isset($settings['modal']) && $settings['modal'] && method_exists($this, $modal_method))
			$settings['modal_callback'] = array($this, $modal_method);
		else
			$settings['modal'] = false;
		
		//register
		$method = $code."_screen";
		if(method_exists($this, $method))
			frl_add_shortcode($code, array($this, $method), $settings);
			//add_shortcode($code, array($this, $method), 90);		
	}
	
	
	/**
	 * Shortcodes' Output & Settings
	 * */
	
	/* question options displaying */
	function q_opt_screen($atts, $content = null) {
		global $post, $mitest_entry;
		
		$out = '';
		$defaults = array('id' => 0, 'score' => 0, 'cfield'=> 'no');
		extract(shortcode_atts($defaults, $atts));
		
		if($id == 0)
			return '';
		
		$id = intval($id);
		$score = intval($score);
		$content = frl_text_for($content, 'print');
		$post_id = (isset($post->ID)) ? intval($post->ID) : 0;
		
		$question = new FRL_Question_Object($post);
		$type = $question->get_data('q_type');
		$input_type = ($type == 'type_checkbox') ? 'checkbox' : 'radio';
			
		if($id == 0 || empty($content) || $post_id == 0)
			return ''; //not enough data
		
		$opt_name = "question[{$post_id}][]";
		$opt_id = "question_{$post_id}_opt_{$id}";		
		
		$css = 'q-option';
		$cfield_out = '';
		$checkbox = $textatea_val = '';
		
		if(isset($_REQUEST['mitest_profile'])){ //profile case
			
			if($_REQUEST['mitest_profile'] == 'preview'){				
				$css .= " active";
				
			} else {
			
				$data = maybe_unserialize($mitest_entry->site_profile);
				$post_data = (isset($data[$post->ID])) ? $data[$post->ID] : array();
				$comments = (isset($data['comments'][$post->ID])) ? $data['comments'][$post->ID] : array();
				
				//comments
				if($cfield == 'yes' && isset($comments[$id]))				
					$textatea_val = frl_text_for($comments[$id], 'edit');					
			
				//selection
				if(in_array($id, $post_data)){				
					$css .= " active";
					$checkbox = "<input type='{$input_type}' name='{$opt_name}' id='{$opt_id}' value='{$id}' date-score='{$score}' checked='checked'>";
					
				} else {
					$css .= " inactive";
				}
			}
			
		}// $_REQUEST	
		
		
		if($cfield == 'yes'){
			$css .= " has-comment";
			$cf_name = "question_comment[{$post_id}][$id]";
			$cf_id = "question_comment_{$post_id}_opt_{$id}";
			
			$cfield_out = "<div class='q-option-comment'>";
			$cfield_out .= "<label for='{$cf_id}'>".__('Please, describe your case', 'frl-mitest')."</label>";
			$cfield_out .= "<textarea name='{$cf_name}' id='{$cf_id}'>{$textatea_val}</textarea></div>";			
		}
					
		$out = "<tr class='{$css}'>";
		$out .= "<td class='checkbox'>";
		
		//build checkbox
		if(empty($checkbox))
			$checkbox = "<input type='{$input_type}' name='{$opt_name}' id='{$opt_id}' value='{$id}' date-score='{$score}'>";		
		
		$out .= $checkbox;
		$out .= "</td><td class='q-text'><span>{$content}</span>{$cfield_out}</td><td class='sep'></td>";
		$out .= "<td class='score'><span>{$score}</span></td></tr>";
		
			
		
		return $out;
	}
	
	/* question options modal markup */
	function q_opt_modal() {
	
	?>
		<form><h5><?php _e('Question\'s Option', 'frl-mitest'); ?></h5>
		
		<fieldset class='field text'>
			<label for='id'><?php _e('Ordered number', 'frl-mitest');?></label>
			<input name='id' class='widefat' value=''>			
		</fieldset>
		
		<fieldset class='field text'>
			<label for='score'><?php _e('Score', 'frl-mitest');?></label>
			<input name='score' class='widefat' value=''>
			<p class='help'><?php _e('Score to be assigned with the option', 'frl-mitest');?></p>
		</fieldset>
		
		<fieldset class='field  select'>
			<label for='cfield'><?php _e('Comment\'s field', 'frl-mitest');?></label>
			<select name='cfield'>
				<option value="no"><?php _e('No', 'frl-mitest');?></option>
				<option value="yes"><?php _e('Yes', 'frl-mitest');?></option>
			</select>
			<p class='help'><?php _e('Presence of additional field for comment', 'frl-mitest');?></p>
		</fieldset>
		</form>
	<?php
	}
	
	/* results interval displaying */
	function m_result_screen($atts, $content = null){
		global $mitest_entry;
		
		$out = '';
		$defaults = array('bottom' => '0', 'top' => '0');
		extract(shortcode_atts($defaults, $atts));
		
		$content = frl_html_for($content, 'print');
		
		$bottom = intval($bottom);
		$top = intval($top);
		
		if($bottom >= $top)
			return ''; //invalid interval
		
		if(isset($_REQUEST['mitest_profile']) && $_REQUEST['mitest_profile'] != 'preview'){
			//we're on profile page
			$css = 'interval';
			$score = (isset($mitest_entry->site_score)) ? intval($mitest_entry->site_score) : 0;
			if($score > $bottom && $score < $top)
				$css .= " active";
			
			$out = "<div class='{$css}'><div class='grade-mark'></div>";
			$out .= $content;
			$out .= "</div>";
		
		} elseif(isset($_REQUEST['mitest_profile']) && $_REQUEST['mitest_profile'] == 'preview'){
						
			$out = "<div class='interval'>";
			$out .= "<p class='range'><b>{$bottom}</b> &ndash; <b>{$top}</b></p><div class='grade-mark'></div>";
			$out .= $content;
			$out .= "</div>";
			
		} else {
			$suffix = rand(1,100);
		
			$out = "<div class='interval'>";
			$out .= "<input type='hidden' name='bottom_{$suffix}' value='{$bottom}' class='bottom'>";
			$out .= "<input type='hidden' name='top_{$suffix}' value='{$top}' class='top'>";
			$out .= "<div class='grade-mark'></div>";
			$out .= $content;
			$out .= "</div>";
		}
		
		return $out;
	}
	
	/* results interval modal markup */
	function m_result_modal() {
	
	?>
		<form><h5><?php _e('Interval of scale options', 'frl-mitest'); ?></h5>
		
		<fieldset class='field text'>
			<label for='bottom'><?php _e('Bottom value', 'frl-mitest');?></label>
			<input name='bottom' class='widefat' value=''>
			<p class='help'><?php _e('Bottom level of the interval', 'frl-mitest');?></p>
		</fieldset>
		
		<fieldset class='field text'>
			<label for='top'><?php _e('Top value', 'frl-mitest');?></label>
			<input name='top' class='widefat' value=''>
			<p class='help'><?php _e('Top level of the interval', 'frl-mitest');?></p>
		</fieldset>
		
		</form>
	<?php
	}
	
	function frl_mitest_modules_screen(){			
		
		$out = '';
		
		$modules = get_terms('module', array('orderby' => 'name'));
		if(empty($modules))
			return $out;
		
		$list = array();
		foreach($modules as $i => $m){
			
			$name = frl_text_for($m->name, 'print');
			$url = get_term_link($m, $m->taxonomy);
			$desc = frl_html_for($m->description, 'print');
			
			$list[$i] = "<li class='module'><h4>{$name}</h4>{$desc}";
			$list[$i] .= "<p class='start-link'><a href='{$url}' target='_blank'>".__('Start testing', 'frl-mitest')." &gt;&gt;</a></p>";
			$list[$i] .= "</li>";
		}
		
		$out = "<ul class='mitest-modules-list'>".implode('', $list)."</ul>";
		
		/* HTML5 allows styles in bottom */
		wp_enqueue_style('frl-mitest-page', frl_mitest_plugin_url().'/_inc/css/page.css', array(), FRL_MITEST_VERSION);
		
		/** add credentials */
		$cred = frl_html_for(frl_mitest_get_option('credentials'), 'print');
		if(!empty($cred))
			$out .= "<div class='devinfo'>{$cred}</div>";
		
		return $out;
	}
	
	function res_scale_screen($atts) {
		
		$defaults = array('grades' => '');
		extract(shortcode_atts($defaults, $atts));
		
		if(empty($grades))
			return '';
		
		$grades = explode(',', $grades);
		$grades = array_map('trim', $grades);
		$grades = array_map('intval', $grades);
		if($grades[0] == 0)
			unset($grades[0]);
		
		$list = array();
		foreach($grades as $i => $grade){
			
			$index = $i+1;
			$list[] = "<li class='grade-{$index}'><div class='mark'>{$grade}</div></li>";			
		}
		
		
		$out = "<div class='module-scale'><ul>";
		$out .= implode('', $list);
		$out .= "</ul></div>";
		
		return $out;
	}
	
	function res_scale_modal() {
		
	?>
		<form><h5><?php _e('Scale options', 'frl-mitest'); ?></h5>
		
		<fieldset class='field text'>
			<label for='grades'><?php _e('Interval grades', 'frl-mitest');?></label>
			<input name='grades' class='widefat' value=''>
			<p class='help'><?php _e('Comma-separated list of numbers representing scale intervals. The starting "0" should be omitted', 'frl-mitest');?></p>
		</fieldset>
		
		
		</form>
	<?php
	}
	
	/**
	 * Dropdown
	 **/
	
	function is_mitest_screen(){
		
		$screen = get_current_screen(); 
		
		if(!in_array($screen->id, array('question', 'edit-module')))
			return false;
		
		return true;
	}
	
	function shortcodes_dropdown(){		
		global $frl_shortcode_tags;
				
		if(empty($frl_shortcode_tags) || !$this->is_mitest_screen())
			return;
				
		$tags = $frl_shortcode_tags;
		ksort($tags);
		    
		$shortcodes_list = '';
		echo '&nbsp;<select class="frl_shortcodes"><option value="0">'.__('Test\'s shortcodes', 'frl-mitest').'</option>';
		foreach ($tags as $key => $tag_args){
            
			$modal = ($tag_args['modal']) ? 1 : 0;
			$format = esc_attr($tag_args['format']);			
			$shortcodes_list .= "<option value='{$key}' data-modal='{$modal}' data-format='{$format}'>{$key}</option>";
            
        }
		echo $shortcodes_list;
		echo '</select>';
	}
	
	function shortcodes_modals(){		
		
		if(!$this->is_mitest_screen())
			return;
		
		echo "<div id='frl-shortcode-modal' class='frl-shortcode-modal'></div>";
		wp_nonce_field('shortcode_modal', '_frl_shortcode_nonce', false);
	}
	
	function shortcode_modal_ajax() {
		global $frl_shortcode_tags;
		
		check_admin_referer('shortcode_modal', '_frl_ajax_nonce');
		
		if(!isset($_REQUEST['shortcode']) || empty($_REQUEST['shortcode']))
			die('-1'); //no shortcode
			
		$scode = trim($_REQUEST['shortcode']);
		$callback = '';
		
		if(isset($frl_shortcode_tags[$scode]['modal_callback']))
			$callback = $frl_shortcode_tags[$scode]['modal_callback'];
		
		if(empty($callback) || !is_callable($callback))
			die('-1');
		
		call_user_func($callback);
		die();
	}
	
	
} //class end


/** 
 * Register Shortcode
 *
 * helpers to be used outside the class
 **/

function frl_add_shortcode($tag, $callback, $args = array()) {
	global $frl_shortcode_tags;
	
	$defaults = array(
		'format' => 'single',
		'modal'  => false
	);
	$args = wp_parse_args($args, $defaults);
	$args['callback'] = $callback;
	
	add_shortcode($tag, $callback);
	if(is_callable($callback))
		$frl_shortcode_tags[$tag] = $args;
}

function frl_remove_shortcode($tag) {
	global $frl_shortcode_tags;
	
	remove_shortcode($tag);
	
	if(isset($frl_shortcode_tags[$tag]))
		unset($frl_shortcode_tags[$tag]);
}

