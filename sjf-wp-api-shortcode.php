<?php

/**
 * Plugin Name: SJF WP API Shortcode
 * Plugin URI: http://scottfennell.org/2014/09/08/wordpress-json-rest-api-shortcode-tutorial/
 * 
 * Author: Scott Fennell
 * Author URI: http://scottfennell.org
 * Description: The purpose of this plugin is to give developers a simple block of code for "hello-world-ing" the new WordPress JSON Rest API: http://wp-api.org/.
 * License: GPL2
 * Text Domain: json-rest-api-shortcode
 * Version: 2.0.9
 * 
 * This plugin will not work without the WP-API plugin, V2.
 * @see https://wordpress.org/plugins/rest-api/
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) { die; }

// A constant to define our plugin version for things like cache-busting.
define( 'SJF_WP_API_SHORTCODE_VERSION', '2.0.9' );

// A constant to define the paths to our plugin folders.
define( 'SJF_WP_API_SHORTCODE_FILE', __FILE__ );
define( 'SJF_WP_API_SHORTCODE_PATH', trailingslashit( plugin_dir_path( SJF_WP_API_SHORTCODE_FILE ) ) );

// A constant to define the urls to our plugin folders.
define( 'SJF_WP_API_SHORTCODE_URL', trailingslashit( plugin_dir_url( SJF_WP_API_SHORTCODE_FILE ) ) );

/**
 * Instantiate the plugin class.
 */
function sjf_wp_api_shortcode() {
	new SJF_WP_API_Shortcode();
}
add_action( 'init', 'sjf_wp_api_shortcode' );

/**
 * Our WP API Shortcode.
 * 
 * Registers a shortcode for drawing an HTML form for subitting requests to the
 * WP API.
 */
class SJF_WP_API_Shortcode {

	/**
	 * A prefix for all of our html tags and css targeting.
	 * 
	 * @var string
	 */
	private $prefix = 'sjf-wp_api-shortcode';

	/**
	 * A camelCase prefix for our translations.
	 * 
	 * @var string
	 */
	private $js_prefix = 'sjfWpApiShortcode';

	/**
	 * The WP API "lives" at this path.  I'm not exactly sure why, or how 
	 * stable that is, but at least it's DRY to this plugin.
	 * 
	 * @var string
	 */
	private $route_prefix = 'wp/v2/';

	/**
	 * This is the url where you can download the WordPress REST API (Version 2)
	 * plugin.
	 * 
	 * @var string
	 */
	private $api_plugin_url = 'https://wordpress.org/plugins/rest-api/';

	/**
	 * You must have at least this version of the Rest API plugin.
	 * 
	 * Typically the version number may also contain a 'beta' string or similar,
	 * but we're going to ignore that.
	 * 
	 * @var string
	 */
	private $min_api_version = '2.0';

	/**
	 * A handle for our plugin stylesheet.
	 * 
	 * @var string
	 */
	private $style_handle = '';

	/**
	 * A handle for our plugin js.
	 * 
	 * @var string
	 */
	private $script_handle = '';	

	/**
	 * Add actions for our class methods.
	 */
	public function __construct() {

		// Set up the handles for our plugin assets.
		$this -> style_handle  = $this -> prefix . '-style';
		$this -> script_handle = $this -> prefix . '-script';	

		// Register the shortcode.
		add_shortcode( 'wp_api', array( $this, 'wp_api' ) );

		// Pass our php variables to our JS.
		add_action( 'wp_enqueue_scripts', array( $this, 'localize' ), 980 );

		// Register our css.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_style' ), 990 );

		// Register our js.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_script' ), 990 );
	
	}

	/**
	 * Pass our php variables to our JS file.
	 */
	function localize() {

		// Grab admin-ajax.php URL with same protocol as current page.
		$localize = array(
			'prefix'          => $this -> prefix,
			'route_prefix'    => $this -> route_prefix,
			'always'          => esc_attr__( 'WP API call is happening.', 'json-rest-api-shortcode' ),
			'fail'            => esc_attr__( 'WP API call failed.', 'json-rest-api-shortcode' ),
			'done'            => esc_attr__( 'WP API call is done.', 'json-rest-api-shortcode' ),
			'discover_always' => esc_attr__( 'Attempt to discover WP API URL is happening.', 'json-rest-api-shortcode' ),
			'discover_fail'   => esc_attr__( 'Attempt to discover WP API URL failed.', 'json-rest-api-shortcode' ),
			'discover_done'   => esc_attr__( 'Attempt to discover WP API URL is done.', 'json-rest-api-shortcode' ),
		);

		// Send to the plugin-wide JS file.  We have to associate it with a script, so we'll just say jquery.
		wp_localize_script( 'jquery', $this -> js_prefix, $localize );

	}

	/**
	 * Register our css.
	 */
	function register_style() {

		$style_handle = $this -> style_handle;

		$src = SJF_WP_API_SHORTCODE_URL . 'css/style.css';

		$deps = array();

		$ver = SJF_WP_API_SHORTCODE_VERSION;

		wp_register_style( $style_handle, $src, $deps, $ver );

	}	

	/**
	 * Register our js.
	 */
	function register_script() {

		$script_handle = $this -> script_handle;

		$src = SJF_WP_API_SHORTCODE_URL . 'js/script.js';

		$deps = array( 'jquery' );

		$ver = SJF_WP_API_SHORTCODE_VERSION;

		wp_register_script( $script_handle, $src, $deps, $ver );

	}		

	/**
	 * Return an HTML form to ping the JSON API.  Upon success, show the
	 * response.
	 *
	 * This is the primary way to use this plugin.  All the other functions are
	 * in support of this function.
	 * 
	 * Example values:
	 * * [wp_api]
	 * * [wp_api route=users data="{'email':'dude@dude.com','username':'newuser','name':'New User','password':'secret'}"]
	 * * [wp_api route=posts method=get]
	 *
	 * @see    http://v2.wp-api.org/
	 * @param  array $atts An array of strings used as WordPress shortcode args.
	 * @return string An HTML form with a script to send an ajax request to wp JSON API.
	 */
	public function wp_api( $atts ) {
		
		// Make sure the WordPress REST API (Version 2)  is available.  If not, page will wp_die().
		$this -> api_plugin_check();

		// Grab the prefix for name-spacing our front-end code.
		$prefix = $this -> prefix;

		// The default args have the affecting of sending a 'POST' request to the 'posts' route, to create a new sample draft post.
		$a = shortcode_atts( array(

			// Any domain name.  Defaults to the current blog.
			'domain' => home_url(),

			// Any route.  Defaults to an empty string, which is the api root response.
			'route' => '',
	
			// Any REST verb such as GET, POST, etc...
			'method' => 'GET',

			// Any JSON string.
			'data' => '{}',

			/**
			 * Make a nonce as per docs.
			 * @see http://v2.wp-api.org/guide/authentication/
			 */
			'nonce' => wp_create_nonce( 'wp_rest' ),

			// Want some CSS for this stuff?
			'default_styles' => 1,

		), $atts );

		// Sanitize the shortcode args before sending them to our ajax script.
		$a = array_map( 'esc_attr', $a );
		
		// Grab some disclaimer text to make it clear that this thing is live.
		$disclaimer = $this -> get_disclaimer();

		// The fields in our form as key/value pairs.
		$field_array = array(
			'domain' => esc_html__( 'Domain:', 'json-rest-api-shortcode' ),
			'method' => esc_html__( 'Method:', 'json-rest-api-shortcode' ),
			'route'  => esc_html__( 'Route:',  'json-rest-api-shortcode' ),
			'data'   => esc_html__( 'Data:',   'json-rest-api-shortcode' ),
			'nonce'  => esc_html__( 'Nonce:',  'json-rest-api-shortcode' ),
		);

		// For each field, output it as a label/input.
		$fields = '';
		foreach( $field_array as $k => $v ) {
			$val = esc_attr( $a[ $k ] );
			$fields .= "
				<label class='$prefix-label' for='$k'>$v</label>
				<input id='$prefix-$k' class='$prefix-input' name='$prefix-$k' value='$val'>
			";
		}

		// A submit button for the form.
		$submit_text = esc_html__( 'Send a real, live API request!', 'json-rest-api-shortcode' );
		$submit = "<button class='$prefix-button' name='submit'>$submit_text</button>";

		/**
		 * Build the form.
		 * 
		 * To be clear, the form really doesn't do anything other than get
		 * clicked.  It's just a UI to trigger the Ajax call.
		 */
		$form = "
			
			<form action='#' id='$prefix-form'>
				$disclaimer	
				$fields
				$submit
			</form>
		";

		// This div will hold the response we get back after we ping the API.
		$output = "<output id='$prefix-output' class='$prefix-hide'></output>";

		// Grab our plugin JS.
		wp_enqueue_script( $this -> script_handle );
		
		// When we get a response from the JSON API, we'll give it some basic styles.
		if( ! empty( $a['default_styles'] ) ) {
			wp_enqueue_style( $this -> style_handle );
		}

		$out = "
			$form
			$output
		";

		$out = apply_filters( $prefix, $out );

		return $out;

	}

	/**
	 * Get some disclaimer text to make sure everyone understands that this
	 * thing really can execute queries.
	 * 
	 * @return string Some disclaimer text.
	 */
	private function get_disclaimer() {
	
		$prefix = $this -> prefix;

		$text = esc_html__( 'This form sends real, live API requests.  Be careful out there!', 'json-rest-api-shortcode' );
		$out  = "<p class='$prefix-disclaimer'>$text</p>";
	
		return $out;

	}

	

	/**
	 * When we print inline JS or CSS, we will also provide an HTML comment to
	 * help the user debug where that stuff is coming from.
	 * 
	 * @param  string $php_class_name  The current php class.
	 * @param  string $php_method_name The current function.
	 * @param  int    $line_number     The current line number.
	 * @return string A comment to explain the origin of our front-end code.
	 */
	private function get_added_by( $php_class_name, $php_method_name, $line_number ) {

		$out = esc_html__( 'Added by: %s, %s(), line %s.', 'json-rest-api-shortcode' );
		$out = sprintf( $out, $php_class_name, $php_method_name, $line_number );
		$out = "<!-- $out -->";

		return $out;

	}

	/**
	 * Check to make sure the JSON API is available. If not, wp_die().
	 * 
	 * I know it seems heavy-handed to just die, but that's really the simplest
	 * and fastest way to communicate this requirement.  Again, this plugin is
	 * for devs, not "normal" folks.
	 */
	private function api_plugin_check() {

		// Do we have the WP Rest API plugin?
		$is_api_plugin_active = $this -> is_api_plugin_active();

		// If not...
		if( ! $is_api_plugin_active ) {

			// Here's an error message in case the user lacks the plugin.
			$no_api_error_message = esc_html__( 'You do not have the WordPress Rest API.  Try updating your WordPress version or installing/updating this plugin: %s', 'json-rest-api-shortcode' );
			
			// I'll be cool and provide a link to the plugin.
			$no_api_error_message = sprintf( $no_api_error_message, $this -> api_plugin_url );

			/**
			 * Time to die.
			 * 
			 * @see https://www.youtube.com/watch?v=bIg85wU4Ilg
			 */
			wp_die( sprintf( $no_api_error_message, $plugin_url ) );

		}

		/**
		 * Okay, great, we made it this far.  That means we have the WP Rest API
		 * plugin installed.  Now let's make sure we have the right version.
		 */
		$is_api_plugin_min_version = $this -> is_api_plugin_min_version();

		// If we don't have the min version...
		if( ! $is_api_plugin_min_version ) {

			// Build an error message to explain this awkward situation.
			$api_version_error_message = esc_html__( 'You do not have the minimum required version of the WordPress Rest API. The minimum version is %s. Try updating your WordPress version or installing/updating this plugin: https://wordpress.org/plugins/rest-api/', 'json-rest-api-shortcode' );

			// Be cool and tell the user what the min version is.
			$api_version_error_message = sprintf( $api_version_error_message, $this -> min_api_version );

			/**
			 * Time to die, again.
			 * 
			 * @see http://www.mtv.com/crop-images/2013/09/04/gwar.jpg
			 */ 
			wp_die( $api_version_error_message );

		}

	}

	/**
	 * Do we have the WP Rest API plugin?  Let's assume that checking for this
	 * version number constant is a good way to determine that.
	 * 
	 * @return boolean If no WP Rest API plugin, FALSE. Else, TRUE.
	 */
	private function is_api_plugin_active() {
		
		if( ! defined( 'REST_API_VERSION' ) ) { return FALSE; }

		return TRUE;

	}

	/**
	 * Do we have the min version of the WP Rest API plugin?  Let's assume that 
	 * checking for this in the version number constant is a good way to
	 * determine that.
	 * 
	 * @return boolean If not the min WP Rest API plugin version, FALSE. Else, TRUE.
	 */
	private function is_api_plugin_min_version() {

		/**
		 * Break the version number apart at the hyphen so that it's easier to
		 * compare to the minimum version.
		 */ 
		$current_version_arr = explode( '-', REST_API_VERSION );
	
		// Grab the portion of the version number, before the hyphen.
		$current_version = $current_version_arr[0];

		// Let's see if we have the minimum required version of the API:
		$comp = version_compare( $current_version, $this -> min_api_version, '>=' );

		/**
		 * If we have the min version, $comp will be the integer, 0.
		 * If we have exceeded te min version, $comp will be the integer, 1.
		 * If we are less than the min version, $comp will be -1.
		 */ 
		if( $comp < 0 ) { return FALSE; }

		return TRUE;

	}

}