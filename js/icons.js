// wp_localize_script will pass in 'data' var.

var MapIcon = L.Icon.extend({
    options: {
        shadowUrl: data.iconPath + '/shadow.png',
        iconSize:     [32, 37], // size of the icon
        shadowSize:   [51, 37], // size of the shadow
        iconAnchor:   [16, 37], // point of the icon which will correspond to marker's location
        shadowAnchor: [20, 37], // the same for the shadow
        popupAnchor:  [-3, -76] // point from which the popup should open relative to the iconAnchor
    }
});

var motorcycleIcon = new MapIcon({iconUrl: data.iconPath + '/motorcycle.png'}),
    startIcon = new MapIcon({iconUrl: data.iconPath + '/start-race-2.png'}),
    finishIcon = new MapIcon({iconUrl: data.iconPath + '/finish.png'});