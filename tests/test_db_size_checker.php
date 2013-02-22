<?php

require_once( '../db-size-checker.php' );  

class Testing_Plugin_Tests extends WP_UnitTestCase {  
 	
 	private $plugin;  

 	function setUp() {

		parent::setUp();
		$this->plugin = $GLOBALS['db-size-checker'];			

	} // end setup

	public function test_plugin_initialization() 
	{
		//Verifies that the plugin isn't null and was properly retrieved in setup.
		$this->assertFalse( null == $this->plugin );
	}

	public function test_mode_is_on()
	{
		// It checks if the WP_TEST_MODE is defined
		$this->assertTrue(defined('WP_TEST_MODE'), 'Something went wrong and WP_TEST_MODE is not defined');
		// Checks if WP_TEST_MODE is true
		$this->assertTrue(WP_TEST_MODE, 'Something went wrong and we are not in test mode');
	}

	public function test_not_in_test_mode()
	{
		// Checks if the no_in_test mode works
		$this->assertFalse($this->plugin->not_in_test_mode(), 'We are in test mode, but it thinks we are not.');
	}

	public function test_send_notification()
	{
		// Checks if it's sending the notifications using wp_mail: message
		$this->assertContains("This is the email message", $this->plugin->send_notification('some subject', 'This is the email message'), 'The send_notification messages is not being sent.');
		// Checks if it's sending the notifications using wp_mail: message
		$this->assertContains("This is the email subject", $this->plugin->send_notification('This is the email subject', 'some message'), 'The send_notification subject is not being sent.');
	}

    public function test_db_size_bigger_than_zero()
    {
    	// Fails if the database size is 0.
    	$this->assertGreaterThan(0, $this->plugin->check_database_size(), 'The database is zero. It shouldn\'t' );
    	
    }

    public function test_db_bigger_than_threshold()
    {
    	// Fails if the db is bigger than the threshold.
    	$this->assertTrue($this->plugin->is_database_bigger(1000, 500), 'The db is bigger than the threshold but there\'s something wrong');
    }

    public function test_db_smaller_than_threshold()
    {
    	// Fails if the db is bigger than the threshold.
    	$this->assertFalse($this->plugin->is_database_bigger(500, 1000), 'The db is smaller than the threshold but there\'s something wrong');
    }

    public function test_db_equal_than_threshold()
    {
    	// Fails if the db is bigger than the threshold.
    	$this->assertFalse($this->plugin->is_database_bigger(500,500), 'The db is equal than the threshold but there\'s something wrong');
    }

    public function test_set_cron_hook()
    {
    	// test if something which is putted in the cron, is really in the cron
    	$this->assertFalse(wp_next_scheduled( 'test_cron' , 'fake_callback'), 'The job is in the cron but we didn\'t added yet');
    	$this->plugin->set_cron_hook('test_cron');
    	$this->assertContainsOnly('integer', array(wp_next_scheduled( 'test_cron' )), true, 'The job is not in the cron.');
    }

    public function test_deactivation_hook()
    {
    	// test if after activation the hook exists and if after deactivation it doesn't
    	$this->assertContainsOnly('integer', array(wp_next_scheduled( 'db-size-checker' )), true, 'It wasn\'t activated properly');
    	$this->plugin->deactivation();
    	$this->assertFalse(wp_next_scheduled( 'db-size-checker' ), 'It wasn\'t deactivated properly');
    }

    public function test_activation_hook()
    {
    	// test if activation registers the hook
    	$this->plugin->deactivation();
    	$this->assertFalse(wp_next_scheduled( 'db-size-checker' ), 'It wasn\'t deactivated properly');
    	$this->plugin->activation();
    	$this->assertContainsOnly('integer', array(wp_next_scheduled( 'db-size-checker' )), true, 'It wasn\'t activated properly');
    }

    public function test_do_cron_job_bad_db_size()
    {
    	// test if the do_cron_job is receiving a good db_size
    	$this->assertNull($this->plugin->do_cron_job(0,0), 'The database is null but the cron job is not exisiting');
    	$this->assertNotNull($this->plugin->do_cron_job(1,1), 'The database is not null but the cron job is exiting');
    }

    public function test_do_cron_job_bigger_db()
    {
    	// test if the db is bigger than the threshold
    	$this->assertContains($this->plugin->webpage . " DB is getting big: CHECK IT NOW!", $return_array = $this->plugin->do_cron_job(1000, 500), 'The db is bigger than the threshold but it\'s not sending the correct email. It is returning: ' . implode(",", $return_array));
    }

    public function test_do_cron_job_smaller_db()
    {
    	// test if the db is bigger than the threshold
    	$this->assertContains($this->plugin->webpage . ' DB is fine', $this->plugin->do_cron_job(500, 1000), 'The db is smaller than the threshold but it\'s not sending the correct email');
    }



} // end class  





