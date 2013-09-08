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
		wp_enqueue_style('multiple-content-blocks',MCB_URL.'assets/css/admin.css');
		wp_enqueue_script('multiple-content-blocks',MCB_URL.'assets/js/admin.js',array('jquery'));
		wp_localize_script('multiple-content-blocks','MCB',array(
			'show' => __('Show','mcb'),
			'hide' => __('Hide','mcb'),
			'confirm_delete' => __('Are you sure you want to delete this block entirely? It will be lost forever!','mcb')
		));
	}
	
	/**
	 * Add meta box when the post has a block 
	 */
	function add_meta_box() {
		global $post;
		
		$type = get_post_type_object($post->post_type);
		if($type->public && $this->get_blocks($post->ID)) add_meta_box('multiple-content-blocks-box',__('Multiple content blocks','mcb'),array($this,'meta_box'),$post->post_type,'normal','high');
		
		if((bool) get_option('mcb-show-inactive-blocks') === true && $this->get_inactive_blocks($post->ID)) add_meta_box('multiple-content-blocks-box-inactive',__('Multiple content blocks (inactive)','mcb'),array($this,'meta_box_inactive'),$post->post_type,'normal','high');
	}
	
	/**
	 * Show meta box
	 */
	function meta_box() {
		global $post;
		
		$blocks = $this->get_blocks($post->ID);
		if(is_wp_error($blocks)) :
			echo '<p>'.$blocks->get_error_message().'</p>';
			$blocks = $this->get_blocks($post->ID,false);
		endif;
		
		if($blocks) :
			foreach($blocks as $id=>$block) :
			  
			  if (is_array($block)) :
			    $name = $block['name'];
			    $type = $block['type'];
			  else :
			    $name = $block;
			    $type = 'editor';
			  endif;
				echo '<p><strong>'.$name.'</strong></p>';
				if ($type == 'one-liner') :
				  echo '<input type="text" name="' . $id . '" value="' . htmlentities(get_post_meta($post->ID,'_mcb-'.$id,true),null,'UTF-8',false) . '" />';
			  else :
				  wp_editor(get_post_meta($post->ID,'_mcb-'.$id,true),$id);
				endif;
			endforeach;
			
			if((bool) get_option('mcb-disable-http-requests') === true) :
				?>
				<h2><?php _e('Help! These are not the right blocks.','mcb'); ?></h2>
				<p class="http-off"><?php printf(__('That\'s right. When you have HTTP requests switched off, you have to refresh the blocks manually by visiting the page. <a class="button-secondary" target="_blank" href="%1$s">Refresh</a>','mcb'),get_permalink($post->ID)); ?></p>
				<?php
			endif;
		endif;
	}
	
	/**
	 * Show inactive blocks
	 */
	function meta_box_inactive() {
		global $post;
		
		$blocks = $this->get_inactive_blocks($post->ID);
		
		if($blocks) :
			?>
			<table class="form-table">
				<thead>
					<tr>
						<th><strong><?php _e('Block ID','mcb'); ?></strong></th>
						<th><strong><?php _e('Actions','mcb'); ?></strong></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($blocks as $block) : $id = str_replace('_mcb-','',$block->meta_key) ?>
						<tr>
							<td>
								<?php echo $id; ?>
														
							</td>
							<td><a class="mcb-show"><?php _e('Show','mcb'); ?></a> | <a class="mcb-delete" href="<?php echo get_edit_post_link($post->ID).'&amp;delete_mcb='.$id; ?>"><?php _e('Delete','mcb'); ?></a></td>
						</tr>
						<tr class="mcb-content">
							<td colspan="2">
								<p class="description"><?php _e('The content displayed below will not be saved. This is just for recovery purposes.','mcb'); ?></p>
								<?php wp_editor(get_post_meta($post->ID,'_mcb-'.$id,true),$id.'-inactive',array('media_buttons' => false)); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		endif;
	}
	
	/**
	 * Maybe delete a block
	 */
	function maybe_delete_block() {
		if($_GET['delete_mcb']) :
			global $post;
			delete_post_meta($post->ID,'_mcb-'.$_GET['delete_mcb']);
		endif;
	}
	
	/**
	 * Save the blocks
	 *
	 * @param int $post_id
	 */
	function save_blocks($post_id) {
		if(!wp_is_post_revision($post_id) && !wp_is_post_autosave($post_id) && ((!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || @$_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest'))) :
			$blocks = $this->get_blocks($post_id);
			if(is_wp_error($blocks)) $blocks = $this->get_blocks($post_id,false);
			
			if($blocks) :
				foreach($blocks as $id=>$name) :
					if(isset($_POST[$id])) :
						update_post_meta($post_id,'_mcb-'.$id,apply_filters('content_save_pre',$_POST[$id]));
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
			if($refresh) :
				$refreshed = $this->refresh_blocks($post_id);
				if(is_wp_error($refreshed)) return $refreshed;
			endif;
			
			return get_post_meta($post_id,'_mcb-blocks',true);
		endif;
	}
	
	/**
	 * Update which MCB's there are on a post or page by visiting it
	 *
	 * @param int $post_id
	 */
	function refresh_blocks($post_id) {
		if((bool) get_option('mcb-disable-http-requests') === true) return true;
		
		$post = get_post($post_id);
		
		if($post->post_status == 'publish') :
			$request = wp_remote_get(get_permalink($post_id));
			if(is_wp_error($request) || $request['response']['code'] != 200) :			
				//HTTP Request failed: Tell the user to do this manually
				return new WP_Error('mcb',sprintf(__('HTTP requests using <a href="http://codex.wordpress.org/Function_API/wp_remote_get" target="_blank">wp_remote_get</a> do not seem to work. This means the blocks cannot be initialized automatically. You can turn off HTTP requests altogether on the <a href="%1$s">options page</a> and manually update your blocks.','mcb'),admin_url('options-general.php?page=mcb-settings')));
			endif;
		endif;
		
		return true;
	}
	
	/**
	 * Get inactive blocks
	 *
	 * @param int $post_id
	 */
	function get_inactive_blocks($post_id) {
		$this->maybe_delete_block();
		
		global $wpdb;
		
		$blocks = $this->get_blocks($post_id,false);
		
		$blocks['blocks'] = true; //Saved blocks
		
		$all_blocks = $wpdb->get_results("SELECT * FROM ".$wpdb->postmeta." WHERE post_id='".$post_id."' AND meta_key LIKE '_mcb-%'");
		$inactive_blocks = array();
		
		if($all_blocks) :
			foreach($all_blocks as $inactive_block) :
				$id = str_replace('_mcb-','',$inactive_block->meta_key);
				if($blocks[$id]) continue;
				
				$inactive_blocks[] = $inactive_block;
			endforeach;
		endif;
		
		return $inactive_blocks;
	}
}
new MCB;
?>