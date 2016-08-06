var geoLocation = {
    lat: "",
    lng: ""
};

jQuery.get("http://ipinfo.io", function( response ) {
    if ( response.loc ) {
        geoLocation.lat = response.loc.split(",")[0];
        geoLocation.lng = response.loc.split(",")[1];
    }
}, "jsonp");
