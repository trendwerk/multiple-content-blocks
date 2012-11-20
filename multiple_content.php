<?php
/*
Plugin Name: Multiple content blocks
Plugin URI: http://plugins.trendwerk.nl/documentation/multiple-content-blocks/
Description: Lets you use more than one content "block" on a template. You only have to insert one tag inside the template, so it's easy to use.
Version: 2.2.1
Author: Ontwerpstudio Trendwerk
Author URI: http://plugins.trendwerk.nl/
*/

function init_multiplecontent() {
	$posttypes = get_post_types();
	
	foreach($posttypes as $posttype) {
		add_meta_box('multi_content',__('Multiple content blocks','trendwerk'),'add_multiplecontent_box',$posttype,'normal','high');
	}
}

add_action('admin_init','init_multiplecontent');


function multiplecontent_css() {
	echo '
	<style type="text/css">
		.js .theEditor, #editorcontainer #content {
			color: #000 !important;
			width: 100%;
		}
	</style>
	';
}

add_action('admin_head','multiplecontent_css');


function add_multiplecontent_box() {
	//check which template is used
	global $post;
	
	if(isset($post->page_template)) {
		$fileToRead = get_template_directory_uri().'/'.$post->page_template;
	} else {
		$fileToRead = get_template_directory_uri().'/';
	}
	
	//read the template
	$fileToRead = strstr($fileToRead,'/themes/');
	if(substr(strrchr($fileToRead,'/'),1) == 'default' || substr(strrchr($fileToRead,'/'),1) == '') { //fix for 2.9
		if(substr(strrchr($fileToRead,'/'),1)) {
			if($post->ID == get_option('page_on_front')) {
				$fileToRead = substr($fileToRead, 0 ,-7) . 'front-page.php';
			} else if($post->post_type == 'post') {
				$fileToRead = substr($fileToRead, 0 ,-7) . 'single.php';
			} else if($post->post_type == 'page') {
				$fileToRead = substr($fileToRead, 0 ,-7) . 'page.php';
			} else {
				$fileToRead = substr($fileToRead, 0 ,-7) . 'single-'.$post->post_type.'.php';
			}
		} else {
			if($post->ID == get_option('page_on_front')) {
				$fileToRead .= 'front-page.php';
			} else if($post->post_type == 'post') {
				$fileToRead .= 'single.php';
			} else if($post->post_type == 'page') {
				$fileToRead .= 'page.php';
			} else {
				$fileToRead .= 'single-'.$post->post_type.'.php';
			}
		}
	}
	$fileToRead = validate_file_to_edit($fileToRead);
	$fileToRead = get_real_file_to_edit($fileToRead);

	//first try to read the child theme, otherwise use the normal theme
	$themes = get_themes();
	$theme = $themes[get_current_theme()];
	$current_theme_url = $theme['Template'];
	$child_theme_url = str_replace('themes/','',strstr(get_stylesheet_directory_uri(),'themes/'));
	
	if(file_exists(str_replace($current_theme_url,$child_theme_url,$fileToRead))) {
		if(fopen(str_replace($current_theme_url,$child_theme_url,$fileToRead), 'r')) { //child theme exists
			$fileToRead = str_replace($current_theme_url,$child_theme_url,$fileToRead);
			$f = fopen($fileToRead, 'r');
		} else {
			$f = fopen($fileToRead, 'r');
		}
		$contents = fread($f, filesize($fileToRead));
		$contents = htmlspecialchars( $contents );
	}
	
	//read the templates header, sidebar and footer, added in v1.1
		$headercontents = read_tag('header',$contents);
		$footercontents = read_tag('footer',$contents);
		
		//multiple sidebars, v1.2
		$sidebarcontents = '';
		$amount_sidebars = substr_count($contents,'get_sidebar(');
		$nextContent = $contents;
		for($i=0;$i<$amount_sidebars;$i++) {
			$sidebarcontents .= read_tag('sidebar',$nextContent);
			$nextContent = substr(strstr($contents,'get_sidebar('),13);
		}
		
		$contents = $headercontents.$contents.$sidebarcontents.$footercontents;
		
	//check how many content field there have to be
	$editors = substr_count($contents,"the_block(");
	
	$nextString = $contents;	
	for($i=0;$i<$editors;$i++) {
		//check whether the next one is a get_the_block or the_block
		$get = false;
		
		$firstThe = strpos($nextString,' the_block');
		$firstGet = strpos($nextString,'get_the_block');
		if(($firstThe > $firstGet && $firstGet != 0) || $firstThe == 0) { //get_the_block is first
			$get = true;
		}
		
		//get the name from it
		$stringFirst = strstr($nextString,' the_block(');		
		if($get) {
			$stringFirst = " ".strstr($nextString,'get_the_block(');
		}
		
		$stringFirst = substr($stringFirst,1);
		$stringLast = strstr($stringFirst,')');
		//remove single and double quotes
		if(!$get) {
			$editorName = str_replace('\'','', str_replace('&quot;','',str_replace('the_block(','',str_replace($stringLast,'',$stringFirst))));
		} else {
			$editorName = str_replace('\'','', str_replace('&quot;','',str_replace('get_the_block(','',str_replace($stringLast,'',$stringFirst))));
		}
		
		//Support for different code annotation
		//Possibly remove the first and last space. You would be an idiot to WANT them there.
		if(substr($editorName,0,1) == ' ') $editorName = substr($editorName,1);
		if(substr($editorName,strlen($editorName)-1) == ' ') $editorName = substr($editorName,0,strlen($editorName)-1);
		
		$nextString = $stringLast;
		
		//add editor
		$fieldName = str_replace(' ','-',$editorName);
		
		echo '<p><strong>'.ucfirst($editorName).'</strong></p>';
		echo '<input type="hidden" name="multiplecontent_box-'.$i.'" value="'.$fieldName.'" />';
		
		global $current_user;
		get_currentuserinfo();
		
		echo '<input type="hidden" name="multiplecontent_box-'.$fieldName.'-nonce" id="multiplecontent_box-'.$fieldName.'-nonce" value="'.wp_create_nonce("multiplecontent_box-".$fieldName."-nonce").'" />'."\n";  //nonce
		
		wp_editor( get_post_meta($post->ID, '_ot_multiplecontent_box-'.$fieldName , true), 'multiplecontent_box-'.$fieldName, $settings = array() );
		
		echo '<p>&nbsp;</p>';
	}
	
	if($editors == 0) {
		_e('There are no content blocks in this template.','cms');
	}
}

function read_tag($tag,$contents) {
	$theTag = strstr($contents,'get_'.$tag.'(');
	//when the tag doesnt exist, return nothing, or it will take the standard file
	if(!$theTag) {
		return '';
	}
	
	$theTag = str_replace('get_'.$tag.'( ','',$theTag); //Different annotation (eg. get_header( 'name '))
	$theTag = str_replace('get_'.$tag.'(','',$theTag);
	
	if(strpos($theTag,' )') != 0) { //Different annotation (eg. get_header( 'name '))
		$theTag = substr($theTag,0, strpos($theTag,' )'));
	} 
	
	if(strpos($theTag,')') != 0) {
		$theTag = substr($theTag,0, strpos($theTag,')'));
	} else {
		$theTag = '';
	}
	
	$theTag = str_replace('\'','',$theTag); //remove '
	$theTag = str_replace('&quot;','',$theTag); //remove "
		
	$fileToRead = get_template_directory_uri().'/'; 
	$fileToRead .= $tag;
	if($theTag) {
		$fileToRead .= '-'.$theTag;
	}
	$fileToRead .= '.php';
	$fileToRead = strstr($fileToRead,'/themes/');
	$fileToRead = validate_file_to_edit($fileToRead);
	$fileToRead = get_real_file_to_edit($fileToRead);


	//first try to read the child theme, otherwise use the normal theme
	$themes = get_themes();
	$theme = $themes[get_current_theme()];
	$current_theme_url = $theme['Template'];
	$child_theme_url = str_replace('themes/','',strstr(get_stylesheet_directory_uri(),'themes/'));

	if(file_exists(str_replace($current_theme_url,$child_theme_url,$fileToRead))) {
		if(fopen(str_replace($current_theme_url,$child_theme_url,$fileToRead), 'r')) { //child theme exists
			$fileToRead = str_replace($current_theme_url,$child_theme_url,$fileToRead);
			$f = fopen($fileToRead, 'r');
		} else {
			$f = fopen($fileToRead, 'r');
		}
		$tagContents = fread($f, filesize($fileToRead));
		$tagContents = htmlspecialchars( $tagContents );
	}
	
	if(!isset($tagContents)) $tagContents = '';
	
	return $tagContents;
}

function save_multiplecontent_box($id) {
	for($i=0;$i>-1;$i++) {
		$fieldName = ''; //reset fieldName
		if(!isset($_POST['multiplecontent_box-'.$i])) break;
		$fieldName = $_POST['multiplecontent_box-'.$i];
		
		if (!wp_verify_nonce($_POST['multiplecontent_box-'.$fieldName.'-nonce'],"multiplecontent_box-".$fieldName."-nonce")) return $id; //nonce
		
		if(isset($_POST['multiplecontent_box-'.$fieldName])) {
			
			$contents = '';
			$contents = apply_filters('content_save_pre',$_POST['multiplecontent_box-'.$fieldName]);
			
			$field = "_ot_multiplecontent_box-".$fieldName;
			
			if($contents) update_post_meta($id, $field, $contents);
			else delete_post_meta($id,"_ot_multiplecontent_box-".$fieldName);
			
		} else {
			break;
		}
	}

}

add_action('save_post', 'save_multiplecontent_box');


//front end

function the_block($blockName) {
	if($blockName) {
		global $post;
		$blockName = str_replace(' ','-',$blockName);
		$content =  get_post_meta($post->ID, '_ot_multiplecontent_box-'.$blockName , true);
		echo apply_filters('the_content', $content);
	}
}

function get_the_block($blockName) {
	if($blockName) {
		global $post;
		$blockName = str_replace(' ','-',$blockName);
		$content =  get_post_meta($post->ID, '_ot_multiplecontent_box-'.$blockName , true);
		return apply_filters('the_content', $content);
	}
}
?>