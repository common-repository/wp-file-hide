<?php
if( !class_exists( 'FileHideSC' ) )
{
    /**
     * A short code class that displays the files list and request form
     */
    class FileHideSC
    {
		public $field_options;
		/**
		 * The Constructor
		 */
		public function __construct()
		{
		} // END public function __construct()
		
		/**
		 * hook into WP's init action hook
		 */
		//[filehide] shortcode function 
		public function filehide_sc_func( $atts ) {
			$a = shortcode_atts( array(
				'filetag' => 'filehide',
			), $atts );
			
			global $wpdb;
			$upload_dir =  wp_upload_dir();
			$options = get_option( 'wpfh-group' );
			$returnform = '';
			$returnlog = '';
			
			//If user has submitted form for file request, perform zip of files and data record entry for admin
			if ( isset( $_POST['wpfh_email'] ) ) {
				$wpfh_zip = new ZipArchive();
				$wpfh_filename = rand() . '-' . time() . '.zip';
				$wpfh_files = '';
				
				if ( $wpfh_zip->open( $upload_dir['basedir'] . '/wp-file-hide/' . $wpfh_filename, ZipArchive::CREATE ) !== true ) {
					exit( "cannot open <$wpfh_filename>\n");
				}
				$allposts = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = %s AND post_content = %s", 'attachment', $a['filetag'] ) );
				foreach ( $allposts as $singlepost ) { 
					if ( isset( $_POST[ 'filehide' . $singlepost->ID ] ) && $_POST[ 'filehide' . $singlepost->ID ] == "on" ) {
						$wpfh_zip->addFile( get_attached_file( $singlepost->ID ), $singlepost->ID . '.pdf' );
						$wpfh_files .= wp_get_attachment_url( $singlepost->ID ) . ';';
					}
				}
				$wpfh_zip->close();
				
				//Insert request and all options into database for admin records
				$table_name = $wpdb->prefix . 'wpfhdownloads';
				
				$options = get_option( 'wpfh-group' );
				$submitted_json = array();
				$submitted_json['wpfh_email'] = $_POST['wpfh_email'];
				foreach ( $options as $field => $args ) {
					if ( isset( $_POST[ $field ] ) ) {
						$submitted_json[ $field ] = $_POST[ $field ];
					}
				}
				$submitted_json['wpfh_files'] = $wpfh_files;
				
				$wpdb->insert( 
					$table_name, 
					array( 
						'time' => current_time( 'mysql' ),
						'submitted' => json_encode( $submitted_json )
					) 
				);
				
				//email link to user to verify email address before download
				wp_mail( $_POST['wpfh_email'] . ',' . get_option( 'admin_email' ), 'Requested Files', "Please follow the link below to download your requested files.\n\n" . site_url() . '?wpfh-rq=' . str_replace( '.zip', '', $wpfh_filename ) );
				$returnform .= '<p style="color:Blue;font-size:12pt;font-weight:bold;">Your files have been emailed to you.</p>';
			}
			
			//Prepare list of file download options based on the filetag
			$allposts = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = %s AND post_content = %s", 'attachment', $a['filetag'] ) );
			foreach ($allposts as $singlepost) { 
					$checked = "";
					//precheck the checkbox if shortcode attribute was included to specify
					if ( isset( $_POST['precheck'] ) && $_POST['precheck'] == $singlepost->post_title ) {
						$checked = ' checked="checked"';
					}
					$returnform .= '<p><input type="checkbox"' . $checked . ' name="filehide' . $singlepost->ID . '" id="filehide' . $singlepost->ID . '" /> ' . $singlepost->post_title . '</p>';
			}
			
			include( __DIR__ . '/../functions.php' );
			$finalform = wpfh_form( '', $returnform, $this->field_options );
			
			return $finalform . $returnlog;
		} // END public function init()
		
		/**
		 * hook into WP's admin_init action hook
		 */
		public function admin_init()
		{           			
		} // END public function admin_init()
    } // END class FileHideSC
} // END if(!class_exists('FileHideSC'))