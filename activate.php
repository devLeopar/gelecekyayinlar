<?php


/*
Plugin Name: Plus Canli Yayınlar Extended
Plugin URI: https://www.google.com
Description: Displaying events from G.Drive
Version: 1.5
Author: Pluser
Author URI: https://www.google.com
License: Licensed under GPLv2
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


//firing with cron jobs with wp crontal manager
function cron_pluscanliyayinlar(){
	require(WP_PLUGIN_DIR . '/canliyayinlar-ssportplus/backend.php');
}

//adding cron jobs as action
add_action('cron_plus_events','cron_pluscanliyayinlar');
?>