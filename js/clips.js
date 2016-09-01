var geoLocation = {
    lat: "",
    lng: ""
};

if (window.location.protocol == "https:") {
    navigator.geolocation.getCurrentPosition(
        function success(pos) {
            var crd = pos.coords;

            //console.log('Your current position is:');
            //console.log('Latitude : ' + crd.latitude);
            //console.log('Longitude: ' + crd.longitude);
            //console.log('More or less ' + crd.accuracy + ' meters.');
            geoLocation.lat = crd.latitude;
            geoLocation.lng = crd.longitude;

            if (typeof clips_projects_map != 'undefined' && clips_projects_map.loaded) {
                clips_projects_map.panTo(new L.LatLng(geoLocation.lat, geoLocation.lng));
            }
            if (typeof clips_events_map != 'undefined' && clips_events_map.loaded) {
                clips_events_map.panTo(new L.LatLng(geoLocation.lat, geoLocation.lng));
            }
        },
        function error(err) {
            console.warn('ERROR(' + err.code + '): ' + err.message);
        },
        {
            enableHighAccuracy: true,
            timeout: 5000,
            maximumAge: 0
        }
    );
}
