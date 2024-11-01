<?php
	/* build form for submission */
	function wpfh_form( $action, $inc, $fields ) {
		$returnform = '<form action="' . $action . '" method="POST">';
		$fh_email_val = '';
		if ( isset( $_POST['wpfh_email'] ) ) {
			$fh_email_val = ' value="' . esc_attr( $_POST['wpfh_email'] ) . '"';
		}
		$returnform .= $inc;
		$returnform .= '<p>Email <input type="text" name="wpfh_email"' . $fh_email_val . ' /></p>';
		
		$options = get_option( 'wpfh-group' );
		foreach ( $fields as $field => $args ) {
			if ( isset( $options[ 'wpfh_' . $field ] ) ) {
				$value = isset( $_POST['wpfh_' . $field] ) ? ' value="' . esc_attr( $_POST[ 'wpfh_' . $field ] ) . '"' : null;
				$returnform .= '<p>' . $args['label'] . ' <input size="' . $args['size'] . '" type="' . $args['type'] . '" name="wpfh_' . esc_attr( $field ) . '"' . $value . ' /></p>';
			}
		}
		$returnform .= "<p><input type='submit' value='Get Files' /></p></form>";
		return $returnform;
	}
	
	//Handle expired files still on server
	function wpfh_expired() {
		$upload_dir =  wp_upload_dir();
		$files = glob( $upload_dir['basedir'] . '/wp-file-hide/*.zip', GLOB_BRACE );
		$options = get_option( 'wpfh-group' );
		foreach ( $files as $file ) {
			if (time()-filemtime( $file ) > intval( $options['wpfh_duration'] ) * 3600) {
				// file older than 7 days, delete
				if ( ! @unlink( $file ) ) {
					wp_mail( get_option( 'admin_email' ), 'WP File Hide - Error', 'The following file was unable to be deleted after expiration' . $file );
				}
			}
		}
	}