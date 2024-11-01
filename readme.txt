=== WP File Hide ===
Contributors: MrFlannagan
Tags: secret files, file request, secure files, email, file handling, documents, hide files, expiring links, expire, zip files, email expiring link, share PDFs, user information, forms, file downloads, product information
Donate link: http://whoischris.com/donations/
Requires at least: 3.5
Tested up to: 4.3.1
Stable tag: trunk
License: GPL2
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html

This plugin allows website admins to let their users request files such as PDFs using a link with an expiration date.
== Description ==
Tag file attachments in your media library with a unique file code.  This list can be added to any page with a simple shortcode.  Every item on the list has a checkbox next to it.

The point is to keep the direct URL to a file hidden from users.  For example, a machine company that wants to avoid letting it's competition easily download all of their PDF spec files.  They also want to collect contact information for potential clients.  Here they setup a form that collects email, name and company name.  The form let's users select which PDF file they'd like to download then emails them a link to a zip file that will expire within the amount of hours set by the website admin.

Users are then able to select which files they would like, enter some basic information then have a link to a zip file emailed to them.  The zip file contains all the files they requested and the link to the zip will expire within a week.

== Installation ==
Simply upload the plugin to to your wp-content/plugins directory and activate.

1) Add tags to any files you want to belong to a list.  The default tag to use would be filehide.  This tag should be added to the description and the title should be what you would like it to be listed as

2) Under Settings > WP File Hide you can set which fields you would like the user to fill out before downloading and the number of hours before the file is to expire.

3) Use shortcode [filehide] to display your files with form, set your custom tag by using [filehide filetag='your-custom-tag']

== Screenshots ==
1. Settings page
2. Tag a media file with 'filehide' be sure to set a title too
3. Displaying the form via Short Code