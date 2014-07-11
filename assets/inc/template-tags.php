<?php
/**
 * Multiple content blocks template tags
 *
 * @package MCB
 * @subpackage Template_Tags
 */

/**
 * Display a block
 *
 * @param string $name The name of the block
 * @param array $args Optional. Additional arguments, see get_the_block for more information.
 *
 * @return string
 */
function the_block( $name, $args = array() ) {
	echo get_the_block( $name, $args );
}

/**
 * Return a block
 *
 * @param string $name The name of the block
 * @param array $args Optional. Additional arguments
 *   array['type']          string The name of the style, either 'editor' or 'one-liner'. Defaults to 'editor'.
 *   array['apply_filters'] bool Whether to apply WordPress content filters. Defaults to true.
 *
 * @return string
 */
function get_the_block( $name, $args = array() ) {
	if( ! empty( $name ) ) {
		$post = mcb_get_post();

		$defaults = array(
			'label'         => '',
			'type'          => 'editor',
			'apply_filters' => true,
		);
		$args = wp_parse_args( $args, $defaults );
		
		mcb_register_block( $post->ID, $name, $args['type'], $args['label'] );
		
		$meta = get_post_meta( $post->ID, '_mcb-' . sanitize_title( $name ), true );
		
		if( $args['apply_filters'] )
			return apply_filters( 'the_content', $meta );

		if( $meta && 0 < count( $meta ) )
			return htmlentities( $meta, null, 'UTF-8', false );
	}
	
	return '';
}

/**
 * Check if the block has content
 *
 * @param string $name
 * @param array $args Optional. Additional arguments, see get_the_block for more information
 */
function has_block( $name, $args = array() ) {
	if( 0 < strlen( get_the_block( $name, $args ) ) ) 
		return true;

	return false;
}

/**
 * Register a block if it does not exist already
 *
 * @param int $post_id
 * @param string $name The name of the block (unique ID)
 * @param string $type Optional. The name of the style, either 'editor' or 'one-liner' (defaults to 'editor')
 * @param string $label Optional. The label for the admin area.
 */
function mcb_register_block( $post_id, $name, $type = 'editor', $label = '' ) {
	if( 'blocks' == $name )
		return;

	if( 0 == strlen( $label ) )
		$label = $name;

	$blocks = get_post_meta( $post_id, '_mcb-blocks', true );

	if( ! is_array( $blocks ) )
		$blocks = array();
	
	$blocks[ sanitize_title( $name ) ] = array(
		'label' => $label,
		'type'  => $type,
	);
	
	update_post_meta( $post_id, '_mcb-blocks', $blocks );
}

/**
 * Reset which blocks are used when visiting the page
 */
function mcb_refresh_blocks() {
	$post = mcb_get_post();

	if( isset( $post ) ) 
		delete_post_meta( $post->ID, '_mcb-blocks' );
}
add_action( 'wp_head', 'mcb_refresh_blocks' );

/**
 * Get current post
 */
function mcb_get_post() {
	global $post;
	$block_post = $post;

	if( 'page' == get_option( 'show_on_front' ) && is_home() && ! $GLOBALS['wp_query']->in_the_loop )
		$block_post = get_post( get_option( 'page_for_posts' ) );

	return $block_post;
}
