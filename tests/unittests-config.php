<?php

/* Path to the WordPress codebase in relation to the location of these tests. Since they are included with our plugin, we refer to a few directories above. */
//define( 'ABSPATH', '/Users/luisherranz/Dropbox/Web Dev/testing-plugin/' );
define( 'ABSPATH', '../../../../' );

/* Local throwaway database */
define( 'DB_NAME', 'testing-plugin-dev' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', 'root' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );
/**/

/* Remote throwaway database 
define( 'DB_NAME', 'heroku_c4ff5ae9eae1ca2' );
define( 'DB_USER', 'b89ea4e1ff4c2e' );
define( 'DB_PASSWORD', 'd1a83947' );
define( 'DB_HOST', 'us-cdbr-east-03.cleardb.com' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );
/**/

define( 'WPLANG', '' );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', true );

define( 'WP_TESTS_DOMAIN', 'www.testing-plugin.dev' );
define( 'WP_TESTS_EMAIL', 'luis@herranz.com' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

/* Not worried about testing networks or subdomains, so setting to false. */
define( 'WP_TESTS_NETWORK_TITLE', 'Test Network' );
define( 'WP_TESTS_SUBDOMAIN_INSTALL', false );
$base = '/';

/* Cron tries to make an HTTP request to the blog, which always fails, because tests are run in CLI mode only */
define( 'DISABLE_WP_CRON', true );

/* Also not interested in testing multisite for this project, so setting to false. */
define( 'WP_ALLOW_MULTISITE', false );
if ( WP_ALLOW_MULTISITE ) {
	define( 'WP_TESTS_BLOGS', 'first,second,third,fourth' );
}
if ( WP_ALLOW_MULTISITE && !defined('WP_INSTALLING') ) {
	define( 'SUBDOMAIN_INSTALL', WP_TESTS_SUBDOMAIN_INSTALL );
	define( 'MULTISITE', true );
	define( 'DOMAIN_CURRENT_SITE', WP_TESTS_DOMAIN );
	define( 'PATH_CURRENT_SITE', '/' );
	define( 'SITE_ID_CURRENT_SITE', 1);
	define( 'BLOG_ID_CURRENT_SITE', 1);
}

$table_prefix  = 'wp_';

define( 'WP_PHP_BINARY', 'php' );

//echo "finished loading unittests-config.php\n";

?>