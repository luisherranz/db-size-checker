<?php

require_once( '../testing-plugin.php' );  

class Testing_Plugin_Tests extends WP_UnitTestCase {  
 	
 	private $plugin;  

    public function test_example() {  
        $this->assertFalse( 1 == 2 );  
    }  


} // end class  





