// wp_localize_script will pass in 'data' var.

var map = L.map(data.div);
L.tileLayer(data.tileLayer, {
    attribution: data.tileAttribution,
    maxZoom: 18,
    subdomains: data.tileSubdomains
}).addTo(map);
var polyline = L.polyline(data.latLong, {color: data.pathColour}).addTo(map);

// zoom the map to the polyline
map.fitBounds(polyline.getBounds());

// Add markers
if (data.addStart) {
    L.marker(data.start, {icon: startIcon}).addTo(map);
}
if (data.addFinish) {
    L.marker(data.finish, {icon: finishIcon}).addTo(map);
}