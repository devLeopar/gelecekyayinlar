<?php
/*
Plugin Name: Plus Canli Yayınlar Extended
Plugin URI: https://www.google.com
Description: Displaying events from G.Drive
Version: 1.5
Author: Oktar Kara
Author URI: https://www.google.com
License: Licensed under GPLv2
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
//require_once(dirname(dirname(dirname(__DIR__))) . '/wp-load.php');

include_once(__DIR__ .'/frontend.php');
//firing with cron jobs with wp crontal manager
function cron_pluscanliyayinlar(){
	include(WP_PLUGIN_DIR . '/canliyayinlar-ssportplus/backend.php');
}

//adding cron jobs as action
add_action('cron_plus_events','cron_pluscanliyayinlar');

function deactivation_shortcode(){
	remove_shortcode('plus_events','upcoming_events');	
}

//remove_shortcode('plus_events','upcoming_events');
register_deactivation_hook(__FILE__ ,'deactivation_shortcode');
//register_deactivation_hook(__FILE__ ,'deactivation_shortcode');
add_shortcode('plus_events','upcoming_events');	
?>