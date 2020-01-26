=== Ssportplus Upcoming Events ===
Contributors: Devleopar
Tags: upcoming events,google drive,spreadsheet
Donate link: https://www.ssportplus.com
Requires at least: 4.0
Tested up to: 5.4
Requires PHP: 7.0
Stable tag: 1.2
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Getting data from google drive and display it as carousel

== Description ==
This plugin retrieves data from authenticated google drive api\'s spreadsheets and display those informations as caraousel.

*Specs:*

1. Getting correct spreadsheets by correct date function
2. Parsing sheet as determined range
3. Cron compatibility for performance


Link to [WordPress](http://wordpress.org/ \"Your favorite software\") and one to [Markdown\'s Syntax Documentation][markdown syntax].


== Installation ==
1. Upload \"activate.php\" to the \"/wp-content/plugins/\" directory.
2. Activate the plugin through the \"Plugins\" menu in WordPress.
3. Use [plus_events] shortcode to display carousel
4. Use \'cron_plus_events\' as cron hook to repeat filling data to serve as carousel\' source

== Frequently Asked Questions ==
The cron is not started how to do that ?
Look at plugin directory check whether plugin is activated

Carousel is not responsive!
You can give style to carousel with css. Actually this plugin is not fully support front end compatibility it is mainly used to pull data from google drive.

== Screenshots ==
1. The screenshot description corresponds to screenshot-1.(png|jpg|jpeg|gif).
2. The screenshot description corresponds to screenshot-2.(png|jpg|jpeg|gif).
3. The screenshot description corresponds to screenshot-3.(png|jpg|jpeg|gif).

== Changelog ==
= 1.1 =
* Date picker function added for performance at backend
* Some useful syntax in source code

= 1.2
* Supported external image added to wordpress directory

== Upgrade Notice ==
=1.2 =
You should upgrade it to 1.2 to get images into your directory if your website is on high traffic. Because, some pictures in export tag should not be displayed from google drive either for quota or mime type issue
