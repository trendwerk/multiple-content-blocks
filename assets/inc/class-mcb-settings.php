<?php 
/**
 * Some of Multiple content blocks' settings. Mostly used for debugging or when having conflicts.
 *
 * @package MCB
 * @subpackage Settings
 */

class MCB_Settings {
	function __construct() {
		add_action( 'admin_init', array( $this, 'add_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
	}
	
	/**
	 * Add settings fields
	 */
	function add_settings() {
		add_settings_section( 'mcb-debug', __( 'Debugging', 'mcb' ), array( $this, 'desc_debug' ), 'mcb-settings' );
		
		add_settings_field( 'mcb-disable-http-requests', __( 'Disable HTTP Requests', 'mcb' ), array( $this, 'show_checkbox' ), 'mcb-settings', 'mcb-debug', array( 
			'label_for'   => 'mcb-disable-http-requests', 
			'description' => __( 'Multiple content blocks initializes it\'s blocks through an HTTP request. This way it \'knows\' what blocks you want to use on a certain page. Sometimes these HTTP requests can cause bugs, for example: when they\'re not allowed by the server or the blocks are wrapped in conditional statement like is_user_logged_in(). You can disable them here.', 'mcb' )
		) );
		register_setting( 'mcb-settings', 'mcb-disable-http-requests' );
		
		add_settings_field( 'mcb-show-inactive-blocks', __( 'Show inactive blocks', 'mcb' ), array( $this, 'show_checkbox' ), 'mcb-settings', 'mcb-debug', array(
			'label_for'   => 'mcb-show-inactive-blocks', 
			'description' => __( 'Sometimes blocks are renamed or in some other way the content is lost. This activates a new meta box on all edit pages with inactive blocks.', 'mcb' )
		) );
		register_setting( 'mcb-settings', 'mcb-show-inactive-blocks' );
	}
	
	/**
	 * Description: Debugging section
	 */
	function desc_debug() {
		echo '<p>' . __( 'You\'re probably having problems showing the right blocks or losing content in some way. Adjust the settings below for some more comfort while using this plugin.', 'mcb' ) . '</p>';
	}
	
	/**
	 * Display a checkbox
	 */	
	function show_checkbox( $args ) {
		?>

		<input type="checkbox" id="<?php echo $args['label_for']; ?>" name="<?php echo $args['label_for']; ?>" <?php checked( (bool) get_option( $args['label_for'] ), true ); ?> value="true" />
		<p class="description"><?php echo $args['description']; ?></p>

		<?php
	}
	
	/**
	 * Add settings page to menu
	 */
	function add_settings_page() {
		add_options_page( __( 'Multiple content blocks', 'mcb' ), __( 'Multiple content blocks', 'mcb' ), 'manage_options', 'mcb-settings', array( $this, 'settings_page' ) );
	}
	
	/**
	 * Settings page
	 */
	function settings_page() {
		?>

		<div class="wrap">

			<div class="icon32" id="icon-themes"><br></div>

			<h2><?php _e( 'Multiple content blocks', 'mcb' ); ?></h2>
			
			<form action="options.php" method="post">
				<?php 
					settings_fields( 'mcb-settings' );
					do_settings_sections( 'mcb-settings' );
					submit_button(); 
				?>
			</form>

		</div>

		<?php
	}
} new MCB_Settings;
