<?php
/**
 * This class allows Multiple Content Blocks in WordPress
 *
 * @package MCB
 * @subpackage Admin
 */
 
class MCB {
	/**
	 * Constructor
	 */
	function __construct() {
		//Initialize
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		
		//Save the blocks
		add_action( 'save_post', array( $this, 'save_blocks' ) );
		
		//Admin CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'add_css' ) );
	}
	
	/**
	 * Add CSS
	 */
	function add_css() {
		wp_enqueue_style( 'multiple-content-blocks', MCB_URL . 'assets/css/admin.css' );
		wp_enqueue_script( 'multiple-content-blocks', MCB_URL . 'assets/js/admin.js', array( 'jquery' ) );
		wp_localize_script( 'multiple-content-blocks', 'MCB', array(
			'show'           => __( 'Show', 'mcb' ),
			'hide'           => __( 'Hide', 'mcb' ),
			'confirm_delete' => __( 'Are you sure you want to delete this block entirely? It will be lost forever!', 'mcb' ),
		) );
	}
	
	/**
	 * Add meta box when the post has a block 
	 */
	function add_meta_box() {
		global $post;
		
		$type = get_post_type_object( $post->post_type );

		if( $this->get_blocks( $post->ID ) )
			add_meta_box( 'multiple-content-blocks-box', __( 'Multiple content blocks', 'mcb' ), array( $this, 'meta_box' ), $post->post_type, 'normal', 'high' );
		
		if( true === (bool) get_option( 'mcb-show-inactive-blocks' ) && $this->get_inactive_blocks( $post->ID ) )
			add_meta_box( 'multiple-content-blocks-box-inactive', __( 'Multiple content blocks (inactive)', 'mcb' ), array( $this, 'meta_box_inactive' ), $post->post_type, 'normal', 'high' );
	}
	
	/**
	 * Show meta box
	 */
	function meta_box() {
		global $post;
		
		$blocks = $this->get_blocks( $post->ID );
		if( is_wp_error( $blocks ) ) {
			echo '<p>' . $blocks->get_error_message() . '</p>';
			$blocks = $this->get_blocks( $post->ID, false );
		}
		
		if( $blocks ) {
			foreach( $blocks as $id => $block ) {

				if( is_array( $block ) ) {
					$label = $block['label'];
					$type = $block['type'];
				} else {
					$label = $block;
					$type = 'editor';
				}

				echo '<p><strong>' . $label . '</strong></p>';

				if( 'one-liner' == $type )
				  echo '<input type="text" name="' . $id . '" value="' . htmlentities( get_post_meta( $post->ID, '_mcb-' . $id, true ), ENT_COMPAT, 'UTF-8', false ) . '" />';
				else
					wp_editor( get_post_meta( $post->ID, '_mcb-' . $id, true ), '_mcb_' . $id, array(
						'tinymce'              => array(
							'wp_autoresize_on' => false,
						),
					) );
			}
			
			if( true === (bool) get_option( 'mcb-disable-http-requests' ) ) {
				?>

				<h2>
					<?php _e( 'Help! These are not the right blocks.', 'mcb' ); ?> 

					<a class="button-secondary" target="_blank" href="<?php echo get_permalink( $post->ID ); ?>">
						<?php _e( 'Refresh', 'mcb' ); ?>
					</a>
				</h2>

				<p class="http-off">
					<?php _e( 'That\'s right. When you have HTTP requests switched off, you have to refresh the blocks manually by visiting the page. ', 'mcb' ); ?>
				</p>

				<?php
			}
		}
	}
	
	/**
	 * Show inactive blocks
	 */
	function meta_box_inactive() {
		global $post;
		
		$blocks = $this->get_inactive_blocks( $post->ID );
		
		if( $blocks ) {
			?>

			<table class="form-table">

				<thead>
					<tr>
						<th><strong><?php _e( 'Block ID', 'mcb' ); ?></strong></th>
						<th><strong><?php _e( 'Actions', 'mcb' ); ?></strong></th>
					</tr>
				</thead>

				<tbody>
					<?php foreach( $blocks as $block ) { $id = str_replace( '_mcb-', '', $block->meta_key ); ?>
						<tr>
							<td>
								<?php echo $id; ?>
							</td>

							<td>
								<a class="mcb-show"><?php _e( 'Show', 'mcb' ); ?></a> | 
								<a class="mcb-delete" href="<?php echo get_edit_post_link( $post->ID ) . '&amp;delete_mcb=' . $id; ?>"><?php _e( 'Delete', 'mcb' ); ?></a>
							</td>
						</tr>

						<tr class="mcb-content">
							<td colspan="2">
								<p class="description"><?php _e( 'The content displayed below will not be saved. This is just for recovery purposes.', 'mcb' ); ?></p>
								<?php wp_editor( get_post_meta( $post->ID, '_mcb-' . $id, true), '_mcb_' . $id . '_inactive', array( 'media_buttons' => false ) ); ?>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>

			<?php
		}
	}
	
	/**
	 * Maybe delete a block
	 */
	function maybe_delete_block() {
		if( isset( $_GET['delete_mcb'] ) ) {
			global $post;
			delete_post_meta( $post->ID, '_mcb-' . $_GET['delete_mcb'] );
		}
	}
	
	/**
	 * Save the blocks
	 *
	 * @param int $post_id
	 */
	function save_blocks( $post_id ) {
		/**
		 * Perform checks
		 */
		if( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) )
			return;

		if( isset( $_REQUEST['doing_wp_cron'] ) )
			return;
			
		if( isset( $_REQUEST['post_view'] ) )
		    return;

		/**
		 * Save data
		 */
		$blocks = $this->get_blocks( $post_id );
		if( is_wp_error( $blocks ) )
			$blocks = $this->get_blocks( $post_id, false );
		
		if( $blocks ) {
			foreach( $blocks as $id => $args ) {
				if( isset( $_POST[ '_mcb_' . $id ] ) )
					update_post_meta( $post_id, '_mcb-' . $id, apply_filters( 'content_save_pre', $_POST[ '_mcb_' . $id ] ) );
			}
		}
	}
	
	/**
	 * Retrieve content blocks for this post
	 *
	 * @param int $post_id
	 */
	function get_blocks( $post_id, $refresh = true ) {
		if( $post_id ) {
			if( $refresh ) {
				$refreshed = $this->refresh_blocks( $post_id );

				if( is_wp_error( $refreshed ) )
					return $refreshed;
			}
			
			return get_post_meta( $post_id, '_mcb-blocks', true );
		}
	}
	
	/**
	 * Update which MCB's there are on a post or page by visiting it
	 *
	 * @param int $post_id
	 */
	function refresh_blocks( $post_id ) {
		if( true === (bool) get_option( 'mcb-disable-http-requests' ) )
			return true;
		
		$post = get_post( $post_id );
		$type = get_post_type_object( $post->post_type );
		
		if( 'publish' == $post->post_status && $type->public ) {
			$args = array();
			$request_url = get_permalink( $post_id );

			if( isset( $_SERVER['PHP_AUTH_USER'] ) && 0 < strlen( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) && 0 < strlen( $_SERVER['PHP_AUTH_PW'] ) )
				$args['headers'] = array(
					'Authorization' => 'Basic ' . base64_encode( esc_attr( $_SERVER['PHP_AUTH_USER'] ) . ':' . esc_attr( $_SERVER['PHP_AUTH_PW'] ) ),
				);

			$request = wp_remote_get( $request_url, $args );

			if( is_wp_error( $request ) || 200 != $request['response']['code'] ) //HTTP Request failed: Tell the user to do this manually					
				return new WP_Error( 'mcb', sprintf( __( '<p>It doesn\'t look like we can automatically initialize the blocks. <a href="%1$s" target="_blank">Visit this page</a> in the front-end and then try again.</p><p>To turn off this option entirely, go to the <a href="%2$s">settings page</a> and disable HTTP Requests. You will still need to perform the steps above.</p>', 'mcb' ), get_permalink( $post_id ), admin_url( 'options-general.php?page=mcb-settings' ) ) );
		}
		
		return true;
	}
	
	/**
	 * Get inactive blocks
	 *
	 * @param int $post_id
	 */
	function get_inactive_blocks( $post_id ) {
		$this->maybe_delete_block();
		
		global $wpdb;
		
		$blocks = $this->get_blocks( $post_id, false );
		
		$blocks['blocks'] = true; //Saved blocks
		
		$all_blocks = $wpdb->get_results( "SELECT * FROM " . $wpdb->postmeta . " WHERE post_id='" . $post_id . "' AND meta_key LIKE '_mcb-%'" );
		$inactive_blocks = array();
		
		if( $all_blocks ) {
			foreach( $all_blocks as $inactive_block ) {
				$id = str_replace( '_mcb-', '', $inactive_block->meta_key );
				
				if( isset( $blocks[ $id ] ) )
					continue;
				
				$inactive_blocks[] = $inactive_block;
			}
		}
		
		return $inactive_blocks;
	}
} new MCB;
