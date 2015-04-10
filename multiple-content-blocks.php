<?php
/**
 * Plugin Name: Multiple content blocks
 * Description: Allow for more content blocks in WordPress than just the one.
 *
 * Plugin URI: https://github.com/trendwerk/multiple-content-blocks/
 * 
 * Author: Trendwerk
 * Author URI: https://github.com/trendwerk/
 * 
 * Version: 3.2.1
 *
 * @package MCB
 * @subpackage Main
 */

define( 'MCB_URL', plugins_url( '/', __FILE__ ) );

include( 'assets/inc/class-mcb.php' );
include( 'assets/inc/class-mcb-settings.php' );
include( 'assets/inc/template-tags.php' );

/**
 * Add translation
 */
function mcb_translation() {
	load_plugin_textdomain( 'mcb', false, dirname( plugin_basename( __FILE__ ) ) . '/assets/languages/' );
}
add_action( 'plugins_loaded', 'mcb_translation' );

/**
 * Backwards compatibility with versions lower than 3.0
 */
function mcb_upgrade() {
	if( ! get_option( 'mcb-3.0-migration' ) ) {
		//Rename database fields: _ot_multiplecontent_box-$name -> mcb-$id
		global $wpdb;
		$blocks = $wpdb->get_results( "SELECT * FROM " . $wpdb->postmeta . " WHERE meta_key LIKE '_ot_multiplecontent_box-%'" );
		
		if( $blocks ) {
			foreach( $blocks as $block ) {
				$id = sanitize_title( str_replace( '_ot_multiplecontent_box-', '', $block->meta_key ) );
				update_post_meta( $block->post_id, 'mcb-' . $id, $block->meta_value );
				delete_post_meta( $block->post_id, $block->meta_key );
			}
		}
		
		update_option( 'mcb-3.0-migration', true );
	} elseif( ! get_option( 'mcb-3.1-migration' ) ) {
		//Prepend meta_key with an underscore so WordPress won't show it in Custom Fields
		global $wpdb;
		$wpdb->query( "UPDATE " . $wpdb->postmeta . " SET meta_key = Concat('_',meta_key) WHERE meta_key LIKE 'mcb-%'" );
		update_option( 'mcb-3.1-migration', true );
	}
}
add_action( 'init', 'mcb_upgrade' );
