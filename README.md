JSON-API-for-BuddyPress
=======================

JSON API for BuddyPress Plugin

==Introduction==

JSON API for BuddyPress is a plugin, that supports the JSON API Plugin with a new Controller to get information from BuddyPress.

Requirements are a working WordPress installation with BuddyPress installed plus the JSON API Plugin up and running.

To install JSON API for BuddyPress follow the installation manual.

For future features refer to the Release Management Plan.

==Installation Manual==

Requirements for this Plugin are a working WordPress installation with BuddyPress up and running.

Furthermore the plugin JSON API needs to be installed and configured, please refer to its installation manual (http://wordpress.org/extend/plugins/json-api/installation/).

To install JSON API for BuddyPress just follow these steps:
* upload the folder "json-api-for-buddypress" to your WordPress plugin folder (<url-to-wordpress>/wp-content/plugins)
* activate the plugin through the 'Plugins' menu in WordPress or by using the link provided by the plugin installer
* activate the controller through the JSON API menu found in the WordPress admin center (Settings -> JSON API)

==Release Notes==

===0.4===
* extended functionality for messages / notifications
* reworked the framework

===0.3===
* extended functionality for profile
* new parameter 'limit' for get_activity
* including error handler function

===0.2===
* extended functionality for activity

===0.1===
* initial commit 
