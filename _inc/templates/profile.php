<?php
/**
 * Test results profile template
 *
 * @package Frl_mitest
 **/

 
global $wp_query, $post, $mitest_entry;

$term = get_queried_object();
$module = new FRL_Module_Object($term);
$module_slug = esc_attr($term->slug);
$mode = false;

if(isset($_REQUEST['mitest_profile']) && $_REQUEST['mitest_profile'] == 'preview'){
	
	$mode = 'preview';
	$entry_id = 0;
	$mitest_entry = new stdClass();
	
} else {
	$entry_id = (isset($_REQUEST['mitest_profile'])) ? trim($_REQUEST['mitest_profile']) : '';
	$entry_id = intval(frl_decrypt($entry_id));
	$mitest_entry = frl_get_test_entry($entry_id);
	
	if(!empty($mitest_entry))
		$mode = 'entry';
}

if(!$mode)
	wp_die(__('Incorrect profile data', 'frl-mitest'));	

	
/**
 * Some data
 * 
 **/
if($mode == 'entry'){

	$site_link = '&ndash';
	if(!empty($mitest_entry->site_url)){
		$url = esc_url($mitest_entry->site_url);
		$parts = parse_url($url);
		$txt = frl_text_for($parts['host'], 'print');		
		$site_link = "<a href='{$url}' target='_blank'>{$txt}</a>";
	}
	
	$site_score = 0;
	if(!empty($mitest_entry->site_score))
		$site_score = intval($mitest_entry->site_score);	
	
	$consult = 0;
	$consult_name = $consult_email = '';
	if($mitest_entry->consult_request > 0){
		$consult = intval($mitest_entry->consult_request);		
		$consult_name = frl_text_for($mitest_entry->consult_name, 'print');
		$consult_email = frl_text_for($mitest_entry->consult_email, 'print');
	}
	
	$comment = '';
	if(!empty($mitest_entry->comment_text))
		$comment = frl_text_for($mitest_entry->comment_text, 'print');	
	

} // if mode
	

?>

<!doctype html>

<!--[if lt IE 7 ]> <html <?php language_attributes(); ?> class="no-js ie6" xmlns:fb="http://ogp.me/ns/fb#"> <![endif]-->
<!--[if IE 7 ]>    <html <?php language_attributes(); ?> class="no-js ie7" xmlns:fb="http://ogp.me/ns/fb#"> <![endif]-->
<!--[if IE 8 ]>    <html <?php language_attributes(); ?> class="no-js ie8" xmlns:fb="http://ogp.me/ns/fb#"> <![endif]-->
<!--[if IE 9 ]>    <html <?php language_attributes(); ?> class="no-js ie9" xmlns:fb="http://ogp.me/ns/fb#"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html <?php language_attributes(); ?> class="no-js"> <!--<![endif]-->
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta charset="<?php bloginfo('charset'); ?>">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    
	<title><?php frl_mitest_head_page_title();?></title>
    <?php wp_head(); ?>
    
    <!--  Mobile optimization -->
    <meta name="HandheldFriendly" content="false">    
    <meta name="viewport" content="width=800, user-scalable=yes"> 
   
    <!--iOS  -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <!--<link rel="apple-touch-startup-image" href="img/splash.png">-->
        
    <!--Microsoft -->
    <meta http-equiv="cleartype" content="on">    
	
</head>
<?php flush(); ?>

<body class='frl-module <?php echo $module_slug.' '.$mode;?>' id="top">
	
<header role="banner">
	<div class="row">
		<div class="twelve columns">
		<?php
			$name = get_bloginfo('name');
			$url = home_url();
			$img = frl_mitest_custom_header();
			
			if(!empty($img)):
		?>
			<a href="<?php echo esc_url($url);?>" class="custom-header"><?php echo $img;?></a>
		<?php else :?>
			<h1><a href="<?php echo esc_url($url);?>"><?php echo frl_text_for($name, 'print');?></a></h1>
		<?php endif;?>
		
		</div>
	</div>
</header>

<header id="module-title">
	<div class="row">
		<div class="twelve columns">
			
		<?php if($mode == 'entry') :?>	
			<h2><?php _e('Testing results', 'frl-mitest');?></h2>
			
			<table cellspacing="0" cellpadding="0">
			<tbody>
				<tr class="module">
					<th><?php _e('Module', 'frl-mitest');?></th>
					<td>
						<p><b><?php echo frl_text_for($module->name, 'print');?></b></p>
						<?php echo frl_html_for($module->description, 'print');?>
					</td>
				</tr>				
				<tr class="site-url">
					<th><?php _e('Testing site', 'frl-mitest');?></th>
					<td><p><?php echo $site_link; ?></p></td>
				</tr>
				<tr class="site-score">
					<th><?php _e('Total Score', 'frl-mitest');?></th>
					<td>
						<p class="score-holder"><b><?php echo $site_score;?></b></p>
						<?php echo do_shortcode(stripcslashes($module->get_intervals()));?>
					</td>
				</tr>
				<tr class="consult">
					<th><?php _e('Consultation', 'frl-mitest');?></th>
					<td>
					<?php
						if($consult == 0){
							echo "<p>".__('The consultation has not been requested', 'frl-mitest')."</p>";
							
						} else {
							echo "<p>".__('The consultation has been requested', 'frl-mitest')."</p>";
							echo "<h6 class='subheader'>".__('Contact data', 'frl-mitest')."</h6>";
							echo "<p>".sprintf(__('Name: %s', 'frl-mitest'), $consult_name)."<br>".sprintf(__('Email: %s', 'frl-mitest'), $consult_email)."</p>";
						}
					?>
					</td>
				</tr>
				<tr class="comment">
					<th><?php _e('Comment on test', 'frl-mitest');?></th>
					<td><p><?php echo $comment; ?></p></td>
				</tr>
			</tbody>
			</table>
			
		<?php elseif($mode == 'preview'):?>
		
			<h2><?php _e('Module Preview', 'frl-mitest');?></h2>
			
			<table cellspacing="0" cellpadding="0">
			<tbody>
				<tr class="module-title">
					<th><?php _e('Title', 'frl-mitest');?></th>
					<td><p><b><?php echo frl_text_for($module->name, 'print');?></b></p></td>
				</tr>				
				<tr class="module-desc">
					<th><?php _e('Short description', 'frl-mitest');?></th>
					<td><?php echo frl_html_for($module->description, 'print');?></td>
				</tr>
				<tr class="module-rules">
					<th><?php _e('Full description', 'frl-mitest');?></th>
					<td><?php echo frl_html_for($module->get_rules(), 'print');?></td>
				</tr>
				
				<tr class="module-results">
					<th><?php _e('Intervals', 'frl-mitest');?></th>
					<td><?php echo do_shortcode(stripcslashes($module->get_intervals()));?></td>
				</tr>
			</tbody>
			</table>
			
		<?php endif;?>
		
		</div>
	</div>
</header><!-- #module-title -->

<div role="main">
<?php if(have_posts()): ?>
	<div class="row questions-list"><div class="twelve columns">
	
<?php if($mode == 'entry'): ?>
	<h2 class="subheader"><?php _e('Profile\'s replys', 'frl-mitest');?></h2>
<?php endif;

	while(have_posts()): the_post();
	
	$question = new FRL_Question_Object($post);

?>	
	<article class="question">	
	
		<hgroup class="question-title">
			<h3><?php echo frl_text_for($question->post_title, 'print');?></h3>
			<h4 class="subheader"><?php echo frl_text_for($question->get_question_type_comment(), 'print');?></h4>
		</hgroup>	
	
		<table class="question-options" cellspacing="0" cellpadding="0"><tbody>
			<?php echo do_shortcode($question->post_content) ;?>
		</tbody></table>
		
		<section class="question-comments">
			<h4><?php _e('Why is it important', 'frl-mitest');?></h4>
				
			<div class="row">			
				<div class="eight columns">
					<?php echo frl_html_for($question->get_question_comment()); ?>
				</div>
				<div class="four columns">
					<h5><?php _e('Relevant materials', 'frl-mitest');?></h5>
					<?php echo frl_html_for($question->get_question_links()); ?>
				</div>
			</div>
		</section>
		
	</article>
<?php endwhile; ?>

</div></div>
	
<?php endif;?>
</div>

<!-- credits -->	
<footer role="contentinfo">
	<div class="row">	
		<div class="columns twelve">
			<div class="credits"><?php echo frl_html_for(frl_mitest_get_option('credentials'), 'print');?></div>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>