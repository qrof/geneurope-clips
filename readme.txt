=== GEN Europe CLIPS Wordpress plugin ===
Contributors: qrof
Donate link: http://clips.gen-europe.org/
Tags: projects, events, gen-europe
Requires at least: 3.0.1
Tested up to: 4.6
Stable tag: 1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display Projects, Events from the FLOW database through an API and display it on the website. Display resources from a remote WebDAV server.

== Description ==

Uses:

* leafletjs (http://leafletjs.com)
* datatables (http://datatables.net)
* moment js (http://momentjs.com) + plugin for datatables (https://datatables.net/plug-ins/sorting/datetime-moment)

Optional:

* MapBox (http://www.mapbox.com) - an access token is needed, which you can get when you register an account.

In order to use the plugin, you must enter API url in CLIPS Settings page. Can only use specific API based on GeoJSON format. API calls are made with language header based on Wordpress Site language.

When adding the shortcodes, be careful not to wrap them in PRE html tags (when using Visual view; check Text view that they are not present)

Projects, Events and Resource urls are set to:

* /projects/
* /events/
* /resources/

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings->Plugin Name screen to configure the plugin
1. Enter Flow API URL and other settings (WebDAV URL if you will also display Resources)
1. Add shortcodes to pages.

= Add projects map =

[clips_projects_map width="100%" height="400px"]

= Add projects list =

[clips_projects_list width="100%"]

= Add events map =

[clips_events_map width="100%" height="400px"]

= Add events list =

[clips_events_list width="100%"]

= Add resource list =

In settings you need to add WebDAV (ownCloud / Nextcloud) URL and username & password.

Show root folder resource list:

[clips_resource_list width="100%"]

Show folder resource list (folder name is appended to the root folder defined in settings):

[clips_resource_list width="100%" folder="{folder name}"]



== Frequently Asked Questions ==

= Styling =

Use CSS overrides to override styles set in css/style.css

= Caching =

Default caching for resources uses Wordpress Transient API and is set to 15 minutes.

= What about foo bar? =

That is the dillemma!

== Screenshots ==

1. Projects map example
2. Projects list example
3. Events map example
4. Events list example
5. Resources list

== Changelog ==

= 1.0 =

first release

= 1.1 =

fixed some css styling

= 1.2 =

added 'default' name to all header and footer references in templates

changed GEN Europe contact email for API access
