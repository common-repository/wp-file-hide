<?php
	/*
	
	* WP File Hide Settings *
	
	Here we set our core settings
	- duration to expire: How long in days until files are no longer accessible after initial request
	- Let user decide (soon create) fields to include in form for file request
	
	*/
	
	wp_enqueue_script( 'jquery-ui-core', array( 'jquery' ) );
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_script( 'wpfhjs', plugin_dir_url( __FILE__ ) . 'js/wpfh.js', array( 'jquery' ), '1.0', true );
	wp_enqueue_style( 'jqueryui-css', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.theme.min.css' );
	wp_enqueue_style( 'jqueryui-structure-css', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.structure.min.css' );
	wp_enqueue_style( 'jqueryui-base-css', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.min.css' );
	
	include( 'functions.php' );
	wpfh_expired();
	
	$results = '';
	
	if ( isset( $_POST['startdate'] ) ) {
		global $wpdb;
		$table_name = $wpdb->prefix . "wpfhdownloads";
		
		$startdate = $_POST['startdate'];
		if ( $_POST['startdate'] == '' ) {
			$startdate = '1950-01-01';
		}
		$enddate = $_POST['enddate'];
		if ( $_POST['enddate'] == '' ) {
			$enddate = '2099-12-31';
		}
		
		$sql = $wpdb->prepare( "SELECT * FROM $table_name WHERE time BETWEEN %s AND %s", $startdate . " 00:00:00", $enddate . " 23:59:59" );
		$requests = $wpdb->get_results( $sql );
		foreach ( $requests as $request ) {
			$jsonrec = json_decode( $request->submitted, true );
			$results .= '<h3>ID: ' . $request->id . '</h3>';
			foreach ( $jsonrec as $key => $value ) {
				if( $key == 'wpfh_files' ) {
					foreach( explode( ';', $value ) as $afile ) {
						$sf = explode( '/', $value );
						$results .= '<a href="' . $value . '">' . str_replace( ';' , '', $sf[ count( $sf ) - 1 ] ) . '</a>; ';
					}
				} else {
					$results .= '<strong>' . ucfirst( str_replace('wpfh_', '', $key ) ) . ':</strong> ' . $value;
				}
				$results .= '<br />';
			}
		}
	}
	
?>
<div class="wrap">
	<h2>WP File Hide</h2>
    <?php settings_errors(); ?>
	<form action='' method='POST'>
	Input Date Range <input name='startdate' type='text' size='8' id='startdate' /> thru <input name='enddate' type='text' size='8' id='enddate' /> <input type='submit' value='Export Requests Data' />
	</form>
	<div id='wpfh_results'><?php echo $results; ?></div>
	<form action='options.php' method='POST'>
	<?php
		settings_fields( 'wpfh-group' );
		do_settings_sections( 'wp-file-hide-options' );
		submit_button(); ?>
	</table>
	</form>
</div>