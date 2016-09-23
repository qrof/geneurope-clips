=== GEN Europe CLIPS Wordpress plugin ===
Contributors: qrof
Donate link: http://clips.gen-europe.org/
Tags: comments, spam
Requires at least: 3.0.1
Tested up to: 4.6
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Display Projects, Events from the FLOW database through an API and display it on the website. Display resources from a remote WebDAV server.

== Description ==

Uses:
- leafletjs (http://leafletjs.com)
- datatables (http://datatables.net)
- moment js (http://momentjs.com) + plugin for datatables (https://datatables.net/plug-ins/sorting/datetime-moment)

Optional:
- MapBox (http://www.mapbox.com) - an access token is needed, which you can get when you register an account.

In order to use the plugin, you must enter API url in CLIPS Settings page. Can only use specific API based on GeoJSON format. API calls are made with language header based on Wordpress Site language.

When adding the shortcodes, be careful not to wrap them in PRE html tags (when using Visual view; check Text view that they are not present)

Projects, Events and Resource urls are set to:
* /projects/
* /events/
* /resources/

## Add projects map

[clips_projects_map width="100%" height="400px"]

## Add projects list

[clips_projects_list width="100%"]

## Add events map

[clips_events_map width="100%" height="400px"]

## Add events list

[clips_events_list width="100%"]

## Add resource list

In settings you need to add WebDAV (ownCloud / Nextcloud) URL and username & password.

Show root folder resource list:

[clips_resource_list width="100%"]

Show folder resource list (folder name is appended to the root folder defined in settings):

[clips_resource_list width="100%" folder="{folder name}"]

## Styling

Use CSS overrides to override styles set in css/style.css

## Caching

Default caching for resources uses Wordpress Transient API and is set to 15 minutes.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings->Plugin Name screen to configure the plugin
1. Enter Flow API URL and other settings (WebDAV URL if you will also display Resources)
1. Add shortcodes to pages.

== Frequently Asked Questions ==

None yet.

= What about foo bar? =

That is the dillemma!

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 1.0 =
