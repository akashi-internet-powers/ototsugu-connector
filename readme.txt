=== Ototsugu Connector ===
Contributors: J-KEI
Tags: events, rest-api, nonprofit
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage consultation session schedules and publish them to an app via the REST API.

== Description ==

Provides a custom post type for managing consultation session schedules (date and time, venue, reception status, and reservation URL).
This plugin does not handle reservation forms or reservation data itself; instead, each schedule holds a link to an existing external reservation system.

== Installation ==

1. Place the plugin in `wp-content/plugins/ototsugu-connector/`.
2. Activate "Ototsugu Connector" from the WordPress admin "Plugins" screen.
3. Register schedules from the "Consultation Events" admin menu.
4. Add the "Consultation Event List" block to a page or template as needed.

== Usage ==

=== Registering a schedule ===

Open "Consultation Events" > "Add New", enter a title, and fill in the "Consultation Event Details" fields before publishing.

* Date & Sort Order: the date (and, within the same day, the time used only for sort order). The time itself is not shown on the front end
* Time Note: a free-text field for the displayed time slot(s)
* Venue Name: the name of the venue
* Address: the address of the venue
* Map: a Google Maps link generated from the address
* Status: Open, Full, or Closed
* Reservation URL: the URL of the external reservation system

If a session has multiple time slots, enter them all as free text in "Time Note". Reservations and capacity are not managed by this plugin.

=== Displaying the schedule on a web page ===

Add the "Consultation Event List" block in the block editor. Schedules are displayed in ascending order of date, and a reservation link is shown only for sessions with the "Open" status.

The block settings let you choose a list, table, or card layout. In the card layout, the date is shown on the left, the session details in the middle, and the featured image on the right. The date can be shown with the day of the week in one of three formats (for example `September 6, 2026 (Sun)`, `2026/09/06 (Sun)`, or `Sep 6 (Sun)`). The "Time Note" field is shown as the displayed time slot. You can also toggle whether the detail link and the reservation link (for open sessions) are shown.

=== REST API ===

Published schedules are available through the standard REST API:

`/wp-json/wp/v2/consultation_event`

Custom fields are included in the response `meta` as `start_at`, `time_note`, `location_name`, `location_address`, `status`, and `reservation_url`. The Google Maps link is generated from `location_address` at display time.

== Changelog ==

= 0.2.0 =
* Prepared the plugin for translation on translate.wordpress.org. The source language of all user-facing strings is now English, including the block editor, the block metadata, and the plugin description.
* Dates and weekday names are now formatted according to the site language.
* Until a translation is available for your language, the admin screens and the front end display English text.
* No changes to the stored data or the REST API response.

= 0.1.0 =
* Initial release. Adds the consultation event custom post type, REST API exposure, the event list block, and automatic detail appending on single event posts.
