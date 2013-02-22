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
			// the email to whom the emails are sent
			$this->to_email = 'info@trainyourears.com';
			
			// the name of this webpage
			$this->webpage = 'TrainYourEars';
			
			// the db threshold
			$this->db_threshold = 4000;
			$this->db_threshold = $this->db_threshold * 1024;

			// the hook to add our own menu
			add_action('admin_menu', array(&$this, 'add_the_settings'));

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

		public function activation()
		{
			// start the plugin, so it adds the hook to the cron and it calls the function which checks the db
			$this->set_cron_hook('db-size-checker', 'twicedaily');
		}

		public function deactivation()
		{
			// remove the task from the cron
			wp_clear_scheduled_hook('db-size-checker');
		}

		public function send_notification($subject='Notification from DB Size Checker',$message='')
		{
			if ($this->not_in_test_mode()) {
				return wp_mail( $this->to_email, $subject, $message, $headers = '' );
			} else {
				return array($this->to_email, $subject, $message);
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

			$db_size = $db_size;	
			// returns the db size 
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

		public function do_cron_job($db_size, $db_threshold)
		{
			// this function executes the cron job and sends notification if db size is bigger than threshold.
			
			// if no args are passed, it takes values from internal data
			if (!isset($db_size)) $db_size = $this->check_database_size();
			if (!isset($db_threshold)) $db_threshold = $this->db_threshold;

			if ((($db_size == 0)) || ($db_threshold == 0)) {
				// if it doesn't receive a propper $db_size, monitorizes and it exists.
				$this->send_notification('Something went wrong with DB Size Checker', 'It is sending $db_size of ' . $db_size . ' and $db_threshold of ' . $db_threshold . ' to the do_cron_job function');
				return null;
			} else {
				// if $db_size and $db_threshold is bigger than 0 do the check and send the notifications.
				if ($this->is_database_bigger($db_size, $db_threshold)) {
					$notification = $this->send_notification($this->webpage . ' DB is getting big: CHECK IT NOW!', 'The $db_size is: ' . $db_size . ' and $db_threshold is: ' . $db_threshold . ' so check it now to reduce it.');
				} else {
					$notification = $this->send_notification($this->webpage . ' DB is fine', 'The $db_size is: ' . $db_size . ' and $db_threshold is: ' . $db_threshold . ' so nothing to worry about.');
				}
				if ($notification == false) {
					// shit, something went wrong sending the emails, what should we do?
				} else {
					// returns either true if we are in the non-test or the array with email, subject and message if we are
					return $notification;
				}
			}
		}

		public function add_the_settings()
		{
			// this is the function that adds the menu to the admin panel
			add_options_page('DB Size Checker', 'DB Size Checker', 'manage_options', 'db-size-checker', array(&$this, 'display_the_settings'));
		}

		public function display_the_settings()
		{
			// this is the function that displays the things in the menu
			echo("<br><br><br>DB SIZE CHECKER:<br><br>");
			echo("DB size is: " . floor($this->check_database_size() / 1024) . "Kb<br>Threshold size is: " . $this->db_threshold / 1024 . "Kb.<br><br><br>");

		}


	} // end class
	
	// Store a reference to the plugin in GLOBALS so that our unit tests can access it
	$GLOBALS['db-size-checker'] = new DB_Size_Checker();
	
} // end if

?>