<?php
/**
 * Multiple content blocks template tags
 */

/**
 * Display a block
 *
 * @param string $name The name of the block
 * @param string $type optional The name of the style, either 'editor' or 'one-liner' (defaults to 'editor')
 */
function the_block($name,$type='editor') {
	echo get_the_block($name,$type);
}

/**
 * Return a block
 *
 * @param string $name The name of the block
 * @param string $type optional The name of the style, either 'editor' or 'one-liner' (defaults to 'editor')
 */
function get_the_block($name,$type='editor') {
	if(!empty($name)) :
		global $post;
		mcb_register_block($post->ID,$name,$type);
		
		return apply_filters('the_content',get_post_meta($post->ID,'mcb-'.sanitize_title($name),true));
	endif;
}

/**
 * Display a block without applying filters
 *
 * @param string $name The name of the block
 * @param string $type optional The name of the style, either 'editor' or 'one-liner' (defaults to 'editor')
 */
function the_block_without_filters($name,$type='editor') {
	echo get_the_block_without_filters($name,$type);
}

/**
 * Return a block without applying filters
 *
 * @param string $name The name of the block
 * @param string $type optional The name of the style, either 'editor' or 'one-liner' (defaults to 'editor')
 */
function get_the_block_without_filters($name,$type='editor') {
	if(!empty($name)) :
		global $post;
		mcb_register_block($post->ID,$name,$type);
		
		$meta = get_post_meta($post->ID,'mcb-'.sanitize_title($name));
		return ($meta && count($meta) > 0) ? $meta[0] : '';
	endif;
}

/**
 * Register a block if it does not exist already
 *
 * @param int $post_id
 * @param string $name The name of the block
 * @param string $type optional The name of the style, either 'editor' or 'one-liner' (defaults to 'editor')
 */
function mcb_register_block($post_id,$name,$type='editor') {
	if(!mcb_block_exists($post_id,$name,$type)) {
		$blocks = get_post_meta($post_id,'mcb-blocks',true);
		if(!is_array($blocks)) $blocks = array();
		
		$blocks[sanitize_title($name)] = array('name' => $name, 'type' => $type);
		
		update_post_meta($post_id,'mcb-blocks',$blocks);
	}
}

/**
 * Checks if a block already exists
 *
 * @param int $post_id
 * @param string $name The name of the block
 * @param string $type optional The name of the style, either 'editor' or 'one-liner' (defaults to 'editor')
 * @return bool
 */
function mcb_block_exists($post_id,$name,$type='editor') {
	$blocks = get_post_meta($post_id,'mcb-blocks',true);
	if(is_array($blocks)) :
	  if(is_array($blocks[sanitize_title($name)])) :
	    $comparable_name = $blocks[sanitize_title($name)]['name'];
	    $comparable_type = $blocks[sanitize_title($name)]['type'];
	  else :
	    $comparable_name = $blocks[sanitize_title($name)];
	    $comparable_type = 'editor';
	  endif;
	  if($comparable_name == $name && $comparable_type == $type) :
	    return true;
		endif;
	endif;
	
	return false;
}

/**
 * Reset which blocks are used when visiting the page
 */
function mcb_refresh_blocks() {
	global $post;
	if(isset($post)) delete_post_meta($post->ID,'mcb-blocks');
}
add_action('wp_head','mcb_refresh_blocks');
?>