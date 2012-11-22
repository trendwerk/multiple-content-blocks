<?php
/**
 * This class allows Multiple Content Blocks in WordPress
 */
 
class MCB {
	/**
	 * Constructor
	 */
	function __construct() {
		//Initialize
		add_action('add_meta_boxes',array($this,'add_meta_box'));
		
		//Save the blocks
		add_action('save_post',array($this,'save_blocks'));
		
		//Admin CSS
		add_action('admin_enqueue_scripts',array($this,'add_css'));
	}
	
	/**
	 * Add CSS
	 */
	function add_css() {
		wp_register_style('multiple-content-blocks',MCB_URL.'assets/css/admin.css');
		wp_enqueue_style('multiple-content-blocks');
	}
	
	/**
	 * Add meta box when the post has a block 
	 */
	function add_meta_box() {
		global $post;
		
		if($this->get_blocks($post->ID)) add_meta_box('multiple-content-blocks-box',__('Multiple content blocks','mcb'),array($this,'meta_box'),$post->post_type,'normal','high');
	}
	
	/**
	 * Show meta box
	 */
	function meta_box() {
		global $post;
		
		if($blocks = $this->get_blocks($post->ID)) :
			foreach($blocks as $id=>$name) :
				echo '<p><strong>'.$name.'</strong></p>';
				wp_editor(get_post_meta($post->ID,'mcb-'.$id,true),$id);
			endforeach;
		else :
			echo '<p>'.__('The template that this post uses does not contain any content blocks.','mcb').'</p>';
		endif;
	}
	
	/**
	 * Save the blocks
	 *
	 * @param int $post_id
	 */
	function save_blocks($post_id) {
		if(!wp_is_post_revision($post_id) && !wp_is_post_autosave($post_id) && (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest')) :
			if($blocks = $this->get_blocks($post_id)) :
				foreach($blocks as $id=>$name) :
					if($_POST[$id]) :
						update_post_meta($post_id,'mcb-'.$id,apply_filters('content_save_pre',$_POST[$id]));
					else :
						delete_post_meta($post_id,'mcb-'.$id);
					endif;
				endforeach;
			endif;
		endif;
	}
	
	/**
	 * Retrieve content blocks for this post
	 *
	 * @param int $post_id
	 */
	function get_blocks($post_id,$refresh=true) {
		if($post_id) :
			if($refresh) $this->refresh_blocks($post_id);
			
			return get_post_meta($post_id,'mcb-blocks',true);
		endif;
	}
	
	/**
	 * Update which MCB's there are on a post or page
	 *
	 * @param int $post_id
	 */
	function refresh_blocks($post_id) {
		delete_post_meta($post_id,'mcb-blocks');
		wp_remote_get(get_permalink($post_id));
	}
}
new MCB;
?>