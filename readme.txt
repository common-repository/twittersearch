=== Plugin Name ===
Contributors: Daniel Beaulieu
Donate link: mailto:danjacob.beaulieu@gmail.com
Tags: Twitter, search, widget, tweet, tweets, sidebar, TwitterSearch, twitter search
Requires at least: 2.7
Tested up to: 3.1.3
Stable tag: 1.0

TwitterSearch allows users to keep track of twitter search terms from within their wordpress dashboard.

== Description ==

TwitterSearch allows users to keep track of twitter search terms from within their wordpress dashboard. Find people tweeting about topics related to your blog and engage them.

== Installation ==

TwitterSearch requires PHP 5.2 or higher.  This is because it is written using Twitter's JSON API and older versions of PHP do not include JSON decoding by default.

Extract the contents of the archive. Upload the TwitterSearch folder to your Wordpress plugins folder (e.g. http://example.com/wp-content/plugins/).  

Ensure the web server is able to write to the cache directory which is ../plugins/TwitterSearch/cache.

Ensure curl is enabled on your php installation.

== Frequently Asked Questions ==

= How do I use this plugin? =

Add search terms using the TwitterSearch configure panel in the TwitterSearch dashboard widget.

== Changelog ==

= 0.2 =
* Widen the input box
* Don't lowercase search terms