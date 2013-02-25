<?php
/**
 * Main test template
 *
 * @package Frl_mitest
 **/

global $wp_query, $post;

$term = get_queried_object();
$module = new FRL_Module_Object($term);
$module_slug = esc_attr($term->slug);

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

<body class='frl-module <?php echo $module_slug;?>' id="top">
	
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
			<h2><?php echo frl_text_for($module->name, 'print');?> </h2>
			
			<div class="row">
				<div class="columns nine">
					<h2><small><?php _e('Progress', 'frl-mitest');?></small></h2>
					<div id="progressbar" class="radius progress success"></div>
				</div>
		
				<div class="columns three">
					<h2><small><?php _e('Test score', 'frl-mitest');?></small></h2>
					<div id="total-score" class="total-score">0</div>
				</div>
			</div>
		</div>
	</div>
</header>


<div role="main">
	
<?php if(have_posts()): ?>	
	
	<div class="row questions-list"><div class="twelve columns">
		
		<noscript><div class="alert-box alert">
			<p><?php _e('To be able perform testing, please enable JavaScript in your browser settings and refresh the page. Thank you!', 'frl-mitest');?></p>
		</div></noscript>
		
	<form id="module-test" action="" method="post">
	
	<article id="before-start" class="question" data-qorder='0'>		
				
		<hgroup class="question-title">
			<h3><?php _e('The initial information', 'frl-mitest');?></h3>
			<?php echo frl_html_for($module->get_rules(), 'print');?>
			
			<div class="common-rules"><?php echo frl_html_for(frl_mitest_get_option('common_rules'), 'print');?></div>
		</hgroup>
			
		<fieldset>				
			<label><?php _e('Please specify the URL of your website', 'frl-mitest');?></label>
			<input name="tsite_url" id="tsite_url" value="" placeholder="http://" type="url" required="required">				
			<small class="error"><?php _e('Please specify the URL of your website to start', 'frl-mitest');?></small>
			
			<!-- hidden fields -->
			<input type="hidden" name="module_id" id="module_id" value="<?php echo intval($module->term_id);?>">
			<?php wp_nonce_field('frl_mitest_module', '_frl_nonce'); ?>
			<input type="hidden" name="total_questions" value="<?php echo intval($wp_query->found_posts);?>">
			<input type="hidden" name="submition_id" id="submition_id" value="0">
			
		</fieldset>
		
		<div class='button-holder'><a href="#" class='next button radius'><?php _e('Start testing', 'frl-mitest');?></a></div>				
			
	</article>
	
<?php /** Loop */

	$counter = 1;
	while(have_posts()): the_post();
	
	$question = new FRL_Question_Object($post);
	
	$a_id = "question_".intval($question->ID);	
?>
	<article id="<?php echo $a_id;?>" class="question" data-qorder='<?php echo $counter;?>'>	
		
		<hgroup class="question-title">
			<h3><?php echo frl_text_for($question->post_title, 'print');?></h3>
			<h4 class="subheader"><?php echo frl_text_for($question->get_question_type_comment(), 'print');?></h4>
		</hgroup>			
		
		<div class="alert-box alert"><p><?php _e('Please choose at least one of the provided options', 'frl-mitest');?></p></div>
		
		<table class="question-options" cellspacing="0" cellpadding="0"><tbody>
			<?php echo do_shortcode($question->post_content) ;?>
		</tbody></table>
					
		<div class="button-holder">
			<a href="#" class="ready button radius"><?php _e('Ready', 'frl-mitest');?></a>
			<a href="#" class="cancel button radius secondary" data-reveal-id="cancel-warning"><?php _e('Cancel', 'frl-mitest');?></a>
		</div>
		
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
			
		<?php
			$btn_label = ($counter != intval($wp_query->found_posts)) ? __('Go further', 'frl-mitest') : __('Get results', 'frl-mitest');
			$css = ($counter == intval($wp_query->found_posts)) ? ' get-results' : '';
		?>
			
			<div class='button-holder'><a href="#" class='next button radius<?php echo $css;?>'><?php echo $btn_label;?></a></div>
		</section>		
	</article>
<?php
	$counter++;
	endwhile;
	
	endif;
	
	
	/* final part */
	
?>
	
	<article id="after-test" class="question" data-qorder='<?php echo $counter;?>'>
		<h2><?php _e('Test is finished', 'frl-mitest');?></h2>
		
		<section class="results">
						
			<div class="score-holder">
				<h6><?php _e('Test score', 'frl-mitest');?></h6>
				<div id="total-score-end" class="total-score">0</div>
			</div>
			
			<?php echo do_shortcode(stripcslashes($module->get_intervals()));?>
			
		</section>
		
		<section class="results-comments">
			<h4><?php _e('Now you can do', 'frl-mitest');?></h4>
			
			<fieldset class="toggle-area">
				<label class="checkbox-label">
					<input type="checkbox" name="comment" value="1" id="comment" class="toggle-trigger">
					<?php _e('Send a comment for test\'s authors', 'frl-mitest');?>
				</label>
				<div class="toggle-content">
					<p>
					<?php _e('Please tell us what you think about the test. Has it been useful for you? Do you agree with results?', 'frl-mitest');?>
					</p>			
					<textarea name="comment_text"></textarea>			
				</div>				
			</fieldset>
			
			<fieldset class="toggle-area">
				<label class="checkbox-label">
					<input type="checkbox" name="consultation" value="1" id="consultation" class="toggle-trigger">
					<?php _e('Ask for consultation', 'frl-mitest');?>
				</label>
				<div class="toggle-content">
					<p><?php _e('Please provide your contact information and our spesialists will handle the details', 'frl-mitest');?></p>
					
					<input type="text" placeholder="<?php _e('Your name', 'frl-mitest');?>" name="consult_name" value="">
					<small class="cname-error error"><?php _e('Please specify the name of the contact person', 'frl-mitest');?></small>
					<input type="email" placeholder="<?php _e('your@email.com', 'frl-mitest');?>" name="consult_email" value="">
					<small class="cemail-error error"><?php _e('Please specify the correct email address', 'frl-mitest');?></small>
					
				</div>				
			</fieldset>
			
			<fieldset class="toggle-area">
				<label class="checkbox-label">
					<input type="checkbox" name="send_results" value="1" id="send_results" class="toggle-trigger">
					<?php _e('Send results by email', 'frl-mitest');?>
				</label>
				<div class="toggle-content">
					<p><?php _e('Please specify the email address where the test results will be send', 'frl-mitest');?></p>
					
					<input type="email" placeholder="<?php _e('your@email.com', 'frl-mitest');?>" name="send_results_contacts" value="">
					<small class="semail-error error"><?php _e('Please specify the correct email address', 'frl-mitest');?></small>
					
				</div>				
			</fieldset>
			
		</section>
		
		<section id="thankyou">
			<div class="panel"><p><?php _e('Your data submit successfully. Thank you for using this test!', 'frl-mitest');?></p></div>
			<div class="alert-box alert"><p><?php _e('There was an error during submitting your data. Please contacts us for details.', 'frl-mitest');?></p></div>
		</section>
	
		<div class='button-holder'><input type="submit" name="module_submit" id="module_submit" value="<?php _e('Submit', 'frl-mitest');?>" class="button radius"></div>
	</article>
	</form>
	
	</div></div><!-- question-list -->
</div> <!-- role=main -->

<!-- credits -->	
<footer role="contentinfo">
	<div class="row">	
		<div class="columns twelve">
			<div class="credits"><?php echo frl_html_for(frl_mitest_get_option('credentials'), 'print');?></div>
		</div>
	</div>
</footer>

<!-- info about local data -->
<div id="localdata" class="reveal-modal medium">
	<p><?php _e('It seems that you\'ve already tried to make this test. Would you like to populate your results?', 'frl-mitest');?></p>
	<div class="button-holder">
		<button id="local-yes" class="button medium radius"><?php _e('Yes, show my data restored', 'frl-mitest');?></button>
		<button id="local-no" class="button medium radius secondary"><?php _e('No, show blank canvas', 'frl-mitest');?></button>
	</div>
</div>

<!-- cancel warning -->
<div id="cancel-warning" class="reveal-modal medium">
	<p><?php _e('It seems that you\'re not satisfied with the scores? Please, be sure that your answers represent the real situation on your site. Otherwise you may get a wrong results at the end.', 'frl-mitest');?></p>
	<div class="button-holder"><button class="button medium radius close-reveal-modal">OK</button></div>
</div>

<!-- loader -->
<div id="loading"></div>
<?php wp_footer(); ?>
</body>
</html>