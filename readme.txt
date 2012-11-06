=== GPXpress ===
Contributors: davidkeen
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2ZJAG8WMAXLD2
Tags: geo, gpx, gps, navigation, maps
Requires at least: 3.0
Tested up to: 3.4.2
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Embed beautiful maps of GPX tracks.

== Description ==

This plugin uses the [Leaflet](http://leaflet.cloudmade.com) JavaScript library and tiles from the [Open MapQuest](http://open.mapquest.co.uk) project to display beautiful maps of GPX tracks.

http://davidkeen.github.com/gpxpress/

== Installation ==

1. Extract the zip file and drop the contents in the wp-content/plugins/ directory of your WordPress installation.
1. Activate the plugin from the Plugins page.

== Frequently Asked Questions ==

= How do I add a map to a post? =

1. Insert the [gpxpress] shortcode into your post where you want to display the map. Use the 'src' parameter to specify the URL of the GPX track you want to display.
1. Use the optional 'width' and 'height' parameters to give the width and height of the map in pixels.
1. Use the optional 'showstart' and 'showfinish' parameters to display a marker at the start and/or finish of a track.

Eg: [gpxpress src=http://www.example.com/my_file.gpx width=600 height=400 showstart=true showfinish=false].

You can set the default values for track colour, width, height and start/finish markers in the plugin settings.

= I don't have a GPX file handy, do you have one I can try? =

If you just insert the [gpxpress] shortcode without any 'src' parameter a default track will be used.

= How can I report a bug/feature request? =

Create an issue in the [GitHub issue tracker](https://github.com/davidkeen/gpxpress/issues).

== Screenshots ==

1. Example post with map.
2. Settings screen.

== Changelog ==

= 1.2 =
* Bug Fix: Allow multiple maps per page.

= 1.1 =
* Enhancement: Add colour picker for path colour.
* Enhancement: Add default demo.gpx for when src is not specified in [gpxpress] shortcode.
* Enhancement: Add options for showing start and finish markers.
* Bug Fix: Fix option handling code for upgrades.

= 1.0 =
* Enhancement: Add GPX mime type to allowed uploads.
* Enhancement: Add default width and height options.
* Enhancement: Make default track colour magenta.

= 0.1 =
* Initial release.

== Credits ==

Icons from the [Map Icons Collection](http://mapicons.nicolasmollet.com).
