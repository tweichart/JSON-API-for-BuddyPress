<?php
/*
Plugin Name: JSON API for Buddypress
Plugin URI: http://wordpress.org/download/json-api-for-buddypress.zip
Description: Extends the JSON API to be used with Buddypress
Version: 0.3
Author: Tobias Weichart
Author URI: https://github.com/tweichart/JSON-API-for-BuddyPress
License: GPL2
*/

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
define ('JSON_API_FOR_BUDDYPRESS_HOME', dirname(__FILE__));

if (!is_plugin_active('buddypress/bp-loader.php')) {
    add_action( 'admin_notices', 'draw_notice_buddypress');
    return;
}

if (!is_plugin_active('json-api/json-api.php')) {
    add_action( 'admin_notices', 'draw_notice_json_api');
    return;
}

add_filter('json_api_controllers', 'addJsonApiController');
add_filter('json_api_buddypressread_controller_path', 'setBuddypressReadControllerPath');
load_plugin_textdomain('json-api-for-buddypress', false, basename( dirname( __FILE__ ) ) . '/languages' );

function draw_notice_buddypress() {
	echo '<div id="message" class="error fade"><p style="line-height: 150%">';
        _e('<strong>JSON API for Buddypress</strong></a> requires the BuddyPress plugin to be activated. Please <a href="http://buddypress.org">install / activate BuddyPress</a> first, or <a href="plugins.php">deactivate JSON API for Buddypress</a>.', 'json-api-for-buddypress');
	echo '</p></div>';
}

function draw_notice_json_api(){
    echo '<div id="message" class="error fade"><p style="line-height: 150%">';
    _e('<strong>JSON API for Buddypress</strong></a> requires the BuddyPress plugin to be activated. Please <a href="http://buddypress.org">install / activate BuddyPress</a> first, or <a href="plugins.php">deactivate JSON API for Buddypress</a>.', 'json-api-for-buddypress');
    echo '</p></div>';
}

function addJsonApiController($aControllers) {
  $aControllers[] = 'BuddypressRead';
  return $aControllers;
}

function setBuddypressReadControllerPath($sDefaultPath) {
    return dirname(__FILE__).'/controllers/BuddypressRead.php';
}