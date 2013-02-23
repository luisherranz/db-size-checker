<?php

require_once( '../db-size-checker.php' );  

class Testing_Plugin_Tests extends WP_UnitTestCase {  
 	
 	private $plugin;  

 	function setUp() {
		parent::setUp();
		$this->plugin = $GLOBALS['db-size-checker'];
        $this->plugin->activation();
	} // end setup

    function tearDown()
    {
        $this->plugin->deactivation();
        $this->plugin->delete_options();
    }

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
        // activates the option to send
        update_option( 'dsc_send_email', '1' );
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

    public function test_db_real_size()
    {
        // test the database real value
        $this->assertTrue($this->plugin->is_database_bigger($this->plugin->check_database_size(), 1), 'The db should be bigger than 1 but it is not.');
        $this->assertFalse($this->plugin->is_database_bigger($this->plugin->check_database_size(), 100000000), 'The db should be smaller than 10000000 but it is not.');
    }

    public function test_is_db_values_numbers()
    {
        // checks if everything that should be numbers, is numbers
        $this->assertTrue(is_float($this->plugin->check_database_size()), 'The check_database_size() is not returning an integer. It is: ' . gettype($this->plugin->check_database_size()));
        $this->assertTrue(is_float($this->plugin->get_sanitized_option('dsc_db_threshold')), 'The option dsc_db_threshold is not sanitized properly. It is: ' . gettype($this->plugin->get_sanitized_option('dsc_db_threshold')));
    }

    public function test_set_cron_hook()
    {
    	// test if something which is putted in the cron, is really in the cron
    	$this->assertFalse(wp_next_scheduled( 'test_cron' , 'fake_callback'), 'The job is in the cron but we didn\'t added yet');
    	$this->plugin->set_cron_hook('test_cron');
    	$this->assertContainsOnly('integer', array(wp_next_scheduled( 'test_cron' )), true, 'The job is not in the cron.');
    }

    public function test_activation_hook()
    {
    	// test if activation registers the hook
    	$this->plugin->deactivation();
    	$this->assertFalse(wp_next_scheduled( 'db-size-checker' ), 'It wasn\'t deactivated properly');
    	$this->plugin->activation();
    	$this->assertContainsOnly('integer', array(wp_next_scheduled( 'db-size-checker' )), true, 'It wasn\'t activated properly');
    }

    public function test_activation_added_options()
    {
    	// test if in activation the options get added
    	foreach ($this->plugin->list_of_options() as $key => $value) {
    		$this->assertContainsOnly('string', array(get_option( $value )), true, 'It did not find the option ' . $value );
    		$this->assertFalse((get_option( $value ) == ''),'The option ' . $value . ' is empty');
    	}
    }

    public function test_deactivation_hook()
    {
    	// test if after activation the hook exists and if after deactivation it doesn't
    	$this->assertContainsOnly('integer', array(wp_next_scheduled( 'db-size-checker' )), true, 'It wasn\'t activated properly');
    	$this->plugin->deactivation();
    	$this->assertFalse(wp_next_scheduled( 'db-size-checker' ), 'It wasn\'t deactivated properly');
    }

    public function test_delete_options()
    {
        // test if options get deleted after delete_options function
        $this->plugin->delete_options();
        foreach ($this->plugin->list_of_options() as $key => $value) {
            $this->assertFalse(get_option( $value ), 'The option: ' . $value . ' was not deleted.');
        }    
    }

    public function test_do_cron_job_bad_db_size()
    {
        // activates the option to send
        update_option( 'dsc_send_email', '1' );
    	// test if the do_cron_job is receiving a good db_size
    	$this->assertNotNull($this->plugin->do_cron_job(1,1), 'The database is not null but the cron job is exiting');
    }

    public function test_do_cron_job_bigger_db()
    {
        // activates the option to send
        update_option( 'dsc_send_email', '1' );
    	// test if the db is bigger than the threshold
    	$this->assertContains(get_option( 'dsc_blog_name', get_option( 'blogname' ) ) . " DB is getting big: CHECK IT NOW!", $return_array = $this->plugin->do_cron_job(1000, 500), 'The db is bigger than the threshold but it\'s not sending the correct email. It is returning: ' . implode(",", $return_array));
    }

    public function test_do_cron_job_smaller_db()
    {
        // activates the option to send
        update_option( 'dsc_send_email', '1' );
    	// test if the db is bigger than the threshold
    	$this->assertContains(get_option( 'dsc_blog_name', get_option( 'blogname' ) ) . ' DB is fine', $this->plugin->do_cron_job(500, 1000), 'The db is smaller than the threshold but it\'s not sending the correct email');
    }



} // end class  





