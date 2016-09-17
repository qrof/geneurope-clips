# geneurope-clips
GEN Europe CLIPS Wordpress plugin

Get Projects, Events from the FLOW database through an API and display it on the website.

Uses:
- leafletjs (http://leafletjs.com)
- datatables (http://datatables.net)
- moment js (http://momentjs.com) + plugin for datatables (https://datatables.net/plug-ins/sorting/datetime-moment)

Optional:
- MapBox (http://www.mapbox.com) - an access token is needed, which you can get when you register an account.

In order to use the plugin, you must enter API url in CLIPS Settings page. Can only use specific API based on GeoJSON format.

When adding the shortcodes, be careful not to wrap them in PRE html tags (when using Visual view; check Text view that they are not present)

## Add projects map

[clips_projects_map width="100%" height="400px"]

## Add projects list

[clips_projects_list width="100%"]

## Add events map

[clips_events_map width="100%" height="400px"]

## Add events list

[clips_events_list width="100%"]

## Add resource list

[clips_resource_list width="100%"]
