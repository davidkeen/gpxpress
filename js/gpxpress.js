// wp_localize_script will pass in 'gpxpressData' var.

var map = L.map(gpxpressData.div);
L.tileLayer(gpxpressData.tileLayer, {
    attribution: gpxpressData.tileAttribution,
    maxZoom: 18,
    subdomains: gpxpressData.tileSubdomains
}).addTo(map);
var polyline = L.polyline(gpxpressData.latLong, {color: gpxpressData.pathColour}).addTo(map);

// zoom the map to the polyline
map.fitBounds(polyline.getBounds());

// Add markers
if (gpxpressData.addStart) {
    L.marker(gpxpressData.start, {icon: startIcon}).addTo(map);
}
if (gpxpressData.addFinish) {
    L.marker(gpxpressData.finish, {icon: finishIcon}).addTo(map);
}