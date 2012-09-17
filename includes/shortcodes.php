<?php

/*
 * Copyright 2012 David Keen <david@davidkeen.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * The [gpxpress] shortcode handler.
 *
 * This shortcode inserts a map of the GPX track.
 * The 'src' parameter should be used to give the url containing the GPX data.
 * The 'width' and 'height' parameters set the width and height of the map in pixels. (Default 600x400)
 * Eg: [gpxpress src=http://www.example.com/my_file.gpx width=600 height=400]
 *
 * @param string $atts an associative array of attributes.
 * @return string the shortcode output to be inserted into the post body in place of the shortcode itself.
 */
function gpxpress_gpxpress_shortcode_handler($atts) {

    // Extract the shortcode arguments into local variables named for the attribute keys (setting defaults as required)
    $defaults = array(
        'src' => null,
        'width' => 600,
        'height' => 400);
    extract(shortcode_atts($defaults, $atts));

    // Create a div to show the map.
    $ret = '<div id="gpxpressMap" style="width: ' . $width . 'px; height: ' . $height .'px">&#160;</div>';

    // Parse the latlongs from the GPX and save them to a global variable to be used in the JS later.
    // String format: [[12.34,98.76],[56.78,54.32]]
    $pairs = array();
    $xml = simplexml_load_file($src);
    foreach ($xml->trk->trkseg->trkpt as $trkpt) {
        $pairs[] = '[' . $trkpt['lat'] . ',' . $trkpt['lon'] . ']';
    }
    global $latlong;
    $latlong = '[' . implode(',', $pairs) . ']';

    return $ret;
}

// register the '[gpxpress]' shortcode handler
add_shortcode('gpxpress', 'gpxpress_gpxpress_shortcode_handler');
