<?php
/**
 * Plugin Name: WP File Hide
 * Plugin URI: http://whoischris.com
 * Description: Force users to insert email plus optional fields to receive a link to download files.  URL for files will be temporary and expire.
 * Version: 1.0.0
 * Author: Chris Flannagan
 * Author URI: http://whoischris.com
 * License: GPL2
 */
 
//set our custom table version
global $wfph_db_version;
$wfph_db_version = '1.0';
 
if ( !class_exists( 'WP_File_Hide' ) )
{
    class WP_File_Hide
    {
		const PLUGIN_SLUG = 'wp-file-hide';
		const PLUGIN_ABBR = 'wpfh';
		
		//Set our field options, admins can choose which fields are required for file requests.  These requests will be stored in the database with this information
		public $field_options = array(
			'name' => array( 'label' => 'Full Name', 'type' => 'text', 'size' => '30', 'admin' => 'checkbox' ),
			'phone' => array( 'label' => 'Phone', 'type' => 'text', 'size' => '12', 'admin' => 'checkbox' ),
			'companyname' => array( 'label' => 'Company Name', 'type' => 'text', 'size' => '30', 'admin' => 'checkbox' ),
			'address' => array( 'label' => 'Address', 'type' => 'text', 'size' => '30', 'admin' => 'checkbox' ),
			'city' => array( 'label' => 'City', 'type' => 'text', 'size' => '30', 'admin' => 'checkbox' ),
			'state' => array( 'label' => 'State', 'type' => 'text', 'size' => '2', 'admin' => 'checkbox' ),
			'zip' => array( 'label' => 'Zip', 'type' => 'text', 'size' => '5', 'admin' => 'checkbox' )
		);
		
        /**
         * Construct the plugin object
         */
        public function __construct()
        {
            // register actions
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'admin_menu', array( &$this, 'add_settings' ) );
			
			// register shortcode
			require_once( sprintf( "%s/shortcodes/filehide_sc.php", dirname(__FILE__) ) );
			add_action( 'wp_enqueue_scripts', array( &$this, 'filehide_sc_func' ) );
			
			// file request redirect to zip filter and action
			add_filter( 'query_vars', array( &$this, 'wpfh_query_vars' ) );
			add_action( 'parse_request', array( &$this,'wpfh_parse_request' ) );
        } // END public function __construct
    
        /**
         * Activate the plugin
         */
        public static function activate()
        {
		    //WP File Hide uses a custom table to store file request data
			global $wpdb;
			$upload_dir = wp_upload_dir();

		    $table_name = $wpdb->prefix . self::PLUGIN_ABBR . 'downloads';
	
			$charset_collate = $wpdb->get_charset_collate();
	
			$sql = "CREATE TABLE $table_name (
				  id mediumint(9) NOT NULL AUTO_INCREMENT,
				  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				  submitted text NOT NULL,
				  UNIQUE KEY id (id)
				) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			
			//create storage folder for requested files
			if( !file_exists( $upload_dir['basedir'] . '/wp-file-hide' ) ) {
				wp_mkdir_p( $upload_dir['basedir'] . '/wp-file-hide' );
			}

			add_option( self::PLUGIN_ABBR . '_db_version', $wfph_db_version );
        } // END public static function activate
    
        /**
         * Deactivate the plugin
         */     
        public static function deactivate()
        {
            // Do nothing
        } // END public static function deactivate
		
		public function add_settings() {
			//Place a link to our settings page under the Wordpress "Settings" menu
			add_options_page( 'WP File Hide', 'WP File Hide', 'manage_options', self::PLUGIN_SLUG . '-options', array( $this, 'settings_page') );
		}
		
		public function settings_page() {
			//Include our settings page template
			include(sprintf("%s/%s_settings.php", dirname(__FILE__), self::PLUGIN_SLUG));  
		}
		
		/**
		 * hook into WP's admin_init action hook
		 */
		public function admin_init()
		{
			// Here we create our settings fields to be contained in the setting group 'wpfh-group'
			register_setting( self::PLUGIN_ABBR . '-group', self::PLUGIN_ABBR . '-group' );
			add_settings_section ( 'main_section' , 'Primary Settings', array( $this, 'settings_callback'), self::PLUGIN_SLUG . '-options' );
			
			//add field for duration before file request link expires
			add_settings_field( 'wpfh_duration', 'Duration until expiration (hours)', array( $this, 'text_callback' ), self::PLUGIN_SLUG . '-options', 'main_section', array(
            'duration'
			) );
			
			//loop through our defined fields and add as a setting field option
			foreach ( $this->field_options as $field => $args ) {
				add_settings_field( self::PLUGIN_ABBR . '_' . $field, 'Require ' . ucfirst ( $args['label'] ), array( $this, $args['admin'] . '_callback' ), self::PLUGIN_SLUG . '-options', 'main_section', array( $field ) );
			}
		}
		
		//Label our settings page instructions
		public function settings_callback() {
			echo 'Set your core settings here. Check boxes next to fields you\'d like the user to fill out when requesting files.';
			if ( ! extension_loaded('zlib') ) {
				echo '<p><strong>Contact your server host to install zlib, this plugin using the zip function to contain files</strong></p>';
			}
		}
		
		//create text field options
		public function text_callback( $args ) {
			$options = get_option( self::PLUGIN_ABBR . '-group' );
			$value = '';
			
			//define value of option if has been set previously
			if ( isset( $options[ self::PLUGIN_ABBR . '_' . $args[0] ] ) ) {
				$value = $options[ self::PLUGIN_ABBR . '_' . $args[0] ];
			}
			echo '<input type="text" name="' . self::PLUGIN_ABBR . '-group[' . self::PLUGIN_ABBR . '_' . $args[0] . ']" value="' . $value . '" />';
		}
		
		//create checkbox field options
		public function checkbox_callback( $args ) {
			$options = get_option( self::PLUGIN_ABBR . '-group' );
			$checked = '';
			
			//select the option if has been saved previously
			if ( isset( $options[ self::PLUGIN_ABBR . '_' . $args[0] ] ) ) {
				$checked = ' checked="checked"';
			}
			echo '<input type="checkbox"' . $checked . ' name="' . self::PLUGIN_ABBR . '-group[' . self::PLUGIN_ABBR . '_' . $args[0] . ']" value="1" />';
		}
	
		//[filehide] shortcode function 
		public function filehide_sc_func() {
			// register shortcode
			$FileHideSC = new FileHideSC();
			$FileHideSC->field_options = $this->field_options;
			add_shortcode( 'filehide', array( $FileHideSC, 'filehide_sc_func' ) );
		}

		//register wpfh-rq query var for redirecting file requests
		function wpfh_query_vars( $vars ) {
			$vars[] = 'wpfh-rq';
			return $vars;
		}
		
		//redirect links to appropriate file requests
		function wpfh_parse_request( $wp ) {
			$upload_dir = wp_upload_dir();
			if ( array_key_exists(self::PLUGIN_ABBR . '-rq', $wp->query_vars) ) {
				$options = get_option( self::PLUGIN_ABBR . '-group' );
				include( 'functions.php' );
				$wpfh_zipfile = $upload_dir['basedir'] . '/wp-file-hide/' . $_GET[self::PLUGIN_ABBR . '-rq'] . '.zip';
				clearstatcache();
				$linkage = time()-filemtime( $wpfh_zipfile );
				
				if ( $linkage < ( intval( $options[self::PLUGIN_ABBR . '_duration'] ) * 3600 ) ) {
					wp_redirect( $upload_dir['baseurl'] . '/wp-file-hide/' . $_GET[self::PLUGIN_ABBR . '-rq'] . '.zip' );
					exit;
				}
			}
		}
	}
} // END if (!class_exists('WP_File_Hide'));

// Add a link to the settings page onto the plugin page
if (class_exists('WP_File_Hide'))
{			
	// Installation and uninstallation hooks
	register_activation_hook( __FILE__, array( 'WP_File_Hide', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'WP_File_Hide', 'deactivate' ) );

	// instantiate the plugin class
	$WP_File_Hide = new WP_File_Hide();
}