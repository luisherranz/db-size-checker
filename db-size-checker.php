<?php

/*
Plugin Name: DB_Size_Checker
Plugin URI: 
Description: A simple plugin used to help demonstrate unit testing in the context of WordPress.
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
			
		} // end constructor
	  
	} // end class
	
	// Store a reference to the plugin in GLOBALS so that our unit tests can access it
	$GLOBALS['db-size-checker'] = new DB_Size_Checker();
	
} // end if

?>