<?php

/*
Plugin Name: DB Size Checker
Plugin URI: 
Description: Checks de DB every hour and if it's bigger than a value, it sends an email.
Version: 1.0
Author: Luis Herranz
Author URI: http://www.luisherranz.com
Author Email: luisherranz@gmail.com
License:

  Copyright 2012 Luis Herranz (luisherranz@gmail.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
*/

  
// Only create an instance of the plugin if it doesn't already exists in GLOBALS
if( ! array_key_exists( 'db-size-checker', $GLOBALS ) ) { 

	class DB_Size_Checker {
		 
		function __construct() {
			// the hook to add our own menu
			add_action('admin_menu', array(&$this, 'add_the_options_page'));
			add_action('admin_init', array(&$this, 'add_the_options_sections'));

			// registers activation and deactivation functions
			register_activation_hook( __FILE__, array( &$this, 'activation' ));
			register_deactivation_hook( __FILE__, array( &$this, 'deactivation' ));

			// the hook to launch our tester
			add_action('db-size-checker', array(&$this, 'do_cron_job'), 10, 0);
			
		} // end constructor
	  
		public function not_in_test_mode()
		{
			if ((!defined('WP_TEST_MODE')) || (WP_TEST_MODE == false)) return true; else return false;
		}

		public function list_of_options($list = '')
		{
			// List of options used in the plugin
			$options = array(
				'dsc_send_email',
				'dsc_email_to',
				'dsc_blog_name',
				'dsc_db_threshold'
				);
			$defaults = array(
				'0',
				get_option( 'admin_email' ),
				get_option( 'blogname' ),
				'4000'
				);
			if ($list == 'defaults') return $defaults; else return $options;
		}



		public function activation()
		{
			// start the plugin, so it adds the hook to the cron and it calls the function which checks the db
			$this->set_cron_hook('db-size-checker', 'twicedaily');

			// adds all the options
			$defaults = $this->list_of_options('defaults');
			foreach ($this->list_of_options() as $key => $value) {
				if (get_option( $value ) == false) add_option( $value, $defaults[$key]);
			}
		}

		public function deactivation()
		{
			// remove the task from the cron
			wp_clear_scheduled_hook('db-size-checker');
		}

		public function delete_options()
		{
			// Delete the options. Useful for the tests
			foreach ($this->list_of_options() as $key => $value) {
				delete_option( $value );
			}
		}

		public function get_sanitized_option($option)
		{
			// get the normal option
			$value = get_option( $option );
			// sanitize the values
			switch ($option) {
				case 'dsc_db_threshold':
					$value = floatval($value);
					break;
				
				default:
					// nothign here yet, because $value is $value
					break;
			}
			// return the value
			return $value;
		}


		public function send_notification($subject='Notification from DB Size Checker',$message='')
		{
			// get the values for BOTH cases (test and no test)
			$email = get_option( 'dsc_email_to' );
			// if user has activated the feature
			if (get_option( 'dsc_send_email' ) == 1) {
				// if it's not in test mode
				if ( $this->not_in_test_mode() ) {
					return wp_mail($email, $subject, $message, $headers = '' );
				} else { // if it's in test mode
					return array($email, $subject, $message);
				}
			}
		}

		public function check_database_size()
		{
			// calculates the db size
			$db_size = 0;
			$rows = mysql_query("SHOW table STATUS");
			while ($row = mysql_fetch_array($rows)) 
				{
					$db_size += $row['Data_length'] + $row['Index_length']; 
				}
			// returns the db size 
			$db_size = floor($db_size / 1024);
			return $db_size;
		}

		public function is_database_bigger($db_size=0, $db_threshold=0)
		{
			// checks if the db_size is bigger than the db_threshold
			if ($db_size > $db_threshold) return true; else return false;
		}

		public function set_cron_hook($hook, $interval='hourly')
		{
			// it puts in the cron queue the function which needs to be called.
			if ( ! wp_next_scheduled($hook) ) {
				wp_schedule_event( time(), $interval, $hook ); // hourly, daily and twicedaily
			}
		}

		public function do_cron_job()
		{
			// this function executes the cron job and sends notification if db size is bigger than threshold.
			$blog_name = $this->get_sanitized_option('dsc_blog_name');

			// if no args are passed, it takes values from internal data
			if (!isset($db_size)) $db_size = $this->check_database_size();
			if (!isset($db_threshold)) $db_threshold = $this->get_sanitized_option( 'dsc_db_threshold' );

			// if $db_size and $db_threshold is bigger than 0 do the check and send the notifications.
			if ($this->is_database_bigger($db_size, $db_threshold)) {
				$notification = $this->send_notification( $blog_name . ' DB is getting big: CHECK IT NOW!', 'The Database is: ' . $db_size . 'Kb and the Threshold is: ' . $db_threshold . 'Kb so check it now to reduce it.');
			} else {
				//$notification = $this->send_notification( $blog_name . ' DB is fine', 'The Database is: ' . $db_size . 'Kb and the Threshold is: ' . $db_threshold . 'Kb so nothing to worry about.');
				$notification = array($blog_name . ' DB is fine', 'The Database is: ' . $db_size . 'Kb and the Threshold is: ' . $db_threshold . 'Kb so nothing to worry about.');
			}
			if ($notification === false) {
				// shit, something went wrong sending the emails, what should we do?
				wp_mail( get_option( 'dsc_email_to' ), 'Something wrong sending the emails of ' . $blog_name, 'Check the plugin configuration.');
			} else {
				// returns either true if we are in the non-test or the array with email, subject and message if we are
				return $notification;
			}
		}

		public function add_the_options_page()
		{
			// this is the function that adds the menu to the admin panel
			add_options_page('DB Size Checker', 'DB Size Checker', 'manage_options', 'db_size_checker', array(&$this, 'display_the_settings_page'));
		}

		public function add_the_options_sections()
		{
			// Register a section. This is necessary since all future options must belong to one.
			add_settings_section(
				'general_settings_section',			// ID used to identify this section and with which to register options
				'General Options',					// Title to be displayed on the administration page
				array(&$this, 'general_settings_callback'), // Callback used to render the description of the section
				'db_size_checker'					// Page on which to add this section of options
			);

			// Introduce the fields for toggling the sending of emails.  
			add_settings_field(   
			    'dsc_send_email',							// ID used to identify the field throughout the theme  
			    'Receive emails:',		// The label to the left of the option interface element  
			    array(&$this, 'send_email_callback'),	// The name of the function responsible for rendering the option interface  
			    'db_size_checker',						// The page on which this option will be displayed  
			    'general_settings_section',		        // The name of the section to which this field belongs  
			    array(									// The array of arguments to pass to the callback. In this case, just a description.  
			        'dsc_send_email',
			        'Activate to start receiving emails when the database is bigger than the threshold.'  
			    )  
			);
			// Register the settings
			register_setting( 'db_size_checker_general', 'dsc_send_email' );
			
			// Introduce the field for email address.  
			add_settings_field(   
			    'dsc_email_to',								// ID used to identify the field throughout the theme  
			    'Emails address:',						// The label to the left of the option interface element  
			    array(&$this, 'input_text_callback'),	// The name of the function responsible for rendering the option interface  
			    'db_size_checker',						// The page on which this option will be displayed  
			    'general_settings_section',		        // The name of the section to which this field belongs  
			    array(									// The array of arguments to pass to the callback. In this case, just a description.  
			        'dsc_email_to',
			        'The email address where you want to receive the emails.',
			        get_option('admin_email')
			    )  
			);
			// Register the settings
			register_setting( 'db_size_checker_general', 'dsc_email_to' );

			// Introduce the name of the blog.  
			add_settings_field(   
			    'dsc_blog_name',								// ID used to identify the field throughout the theme  
			    'Name of the blog:',						// The label to the left of the option interface element  
			    array(&$this, 'input_text_callback'),	// The name of the function responsible for rendering the option interface  
			    'db_size_checker',						// The page on which this option will be displayed  
			    'general_settings_section',		        // The name of the section to which this field belongs  
			    array(									// The array of arguments to pass to the callback. In this case, just a description.  
			        'dsc_blog_name',
			        'The name of this page so you know which blog has the database issue.',
			        get_option('blogname')
			    )  
			);
			// Register the settings
			register_setting( 'db_size_checker_general', 'dsc_blog_name' );

			// Introduce the fields for the threshold.  
			add_settings_field(   
			    'dsc_db_threshold',							// ID used to identify the field throughout the theme  
			    'Threshold limit:',		// The label to the left of the option interface element  
			    array(&$this, 'input_text_callback'),	// The name of the function responsible for rendering the option interface  
			    'db_size_checker',						// The page on which this option will be displayed  
			    'general_settings_section',		        // The name of the section to which this field belongs  
			    array(									// The array of arguments to pass to the callback. In this case, just a description.  
			        'dsc_db_threshold',
			        'It needs to be a number. Use Kbs, for example if you want a 4Mb threshold, use 4000',
			        '4000'
			    )  
			);
			// Register the settings
			register_setting( 'db_size_checker_general', 'dsc_db_threshold' );
		}

		public function send_email_callback($args)
		{
			// Note the ID and the name attribute of the element should match that of the ID in the call to add_settings_field  
		    $html = '<input type="checkbox" id="'  . $args[0] . '" name="'  . $args[0] . '" value="1" ' . checked(1, get_option($args[0]), false) . '/>';      
		    // Here, we will take the first argument of the array and add it to a label next to the checkbox  
		    $html .= '<label for="'  . $args[0] . '"> '  . $args[1] . '</label>';  
		    echo $html;  
		      
		}

		public function input_text_callback($args)
		{
			// Try to get the option
			$setting = esc_attr( get_option( $args[0], $args[2] ) );
    		$html = "<input type='text' name='$args[0]' value='$setting' />";
		    $html .= '<label for="$args[0]"> '  . $args[1] . '</label>';  		      
		    echo $html;  
		      
		}

		public function general_settings_callback()
		{
			// Display the info
			echo("The actual DB size is: <strong>" . $this->check_database_size() . "Kb</strong><br>");
		}

		public function display_the_settings_page()
		{
			// this is the function that displays the things in the menu
		    ?>
		    <div class="wrap"><br>
		        <h2>DB Size Checker Plugin</h2>
		        <form action="options.php" method="POST">
		            <?php settings_fields( 'db_size_checker_general' ); ?>
		            <?php do_settings_sections( 'db_size_checker' ); ?>
		            <p>In order to start receiving mail alerts click on Save Changes at least one time.</p>
		            <?php submit_button(); ?>
		        </form>
		    </div>
		    <?php
		}


	} // end class
	
	// Store a reference to the plugin in GLOBALS so that our unit tests can access it
	$GLOBALS['db-size-checker'] = new DB_Size_Checker();
	
} // end if

?>