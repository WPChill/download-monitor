=== Download Monitor ===
Contributors: mikejolley
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=mike.jolley@me.com&item_name=Donation+for+Download+Monitor
Tags: download, downloads, monitor, hits, download monitor, tracking, admin, count, counter, files, versions, download count, logging
Requires at least: 3.8
Tested up to: 3.8
Stable tag: 1.4.2
License: GPLv3

Download Monitor is a plugin for uploading and managing downloads, tracking downloads, and displaying links.

== Description ==

Download Monitor provides an interface for uploading and managing downloadable files (including support for multiple versions), inserting download links into posts, and logging downloads.

= Features =

* Add, edit and remove downloads from a familiar WP interface; just like posts.
* Quick-add panel for adding files whilst editing posts.
* Add multiple file versions to your downloads.
* Define alternative links (mirrors) per version.
* Categorise, tag, or add other meta to your downloads.
* Display download links on the frontend using shortcodes.
* Change the way download links get displayed via template files.
* Track downloads counts and log user download attempts.
* Member only downloads.
* Customisable endpoints for showing pretty download links.

[Read more about Download Monitor](http://mikejolley.com/projects/download-monitor/).

= Documentation =

Documentation will be maintained on the [GitHub Wiki here](https://github.com/mikejolley/download-monitor/wiki).

= Add-ons =

Add-ons, such as the __legacy importer__ and __page addon__ can be [found here](http://mikejolley.com/projects/download-monitor/add-ons/). Take a look!

= Contributing and reporting bugs =

You can contribute code and localizations to this plugin via GitHub: [https://github.com/mikejolley/download-monitor](https://github.com/mikejolley/download-monitor)

= Support =

Use the WordPress.org forums for community support - I cannot offer support directly for free. If you spot a bug, you can of course log it on [Github](https://github.com/mikejolley/download-monitor) instead where I can act upon it more efficiently.

If you want help with a customisation, hire a developer!

== Installation ==

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't even need to leave your web browser. To do an automatic install, log in to your WordPress admin panel, navigate to the Plugins menu and click Add New.

In the search field type "Download Monitor" and click Search Plugins. Once you've found the plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by clicking _Install Now_.

= Manual installation =

The manual installation method involves downloading the plugin and uploading it to your webserver via your favourite FTP application.

* Download the plugin file to your computer and unzip it
* Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation's `wp-content/plugins/` directory.
* Activate the plugin from the Plugins menu within the WordPress admin.

== Frequently Asked Questions ==

= I used this before, so why is this version 1? =

Version 1.0.0 is a fresh start/complete rewrite of the legacy 3.0 version using modern best-practices such as custom post types and endpoints. Because of this, data from the legacy plugin won't work without migration using [the legacy importer](http://mikejolley.com/projects/download-monitor/add-ons/legacy-importer/). Since this upgrade process isn't straightforward nor automated I've reverted the version to 1.0.0 to prevent automatic updates.

Legacy versions can still be [found here](http://wordpress.org/plugins/download-monitor/developers/).

= X feature/shortcode is missing from the legacy version; why? =

The rewrite has trimmed the fat and only kept the best, most useful features. If something is missing, you can always code it yourself - the new system is very flexible and its easy to query files using [get_posts](http://codex.wordpress.org/Template_Tags/get_posts).

If you are missing the "Page Addon", this is now a separate plugin found here: [Download Monitor Page Addon](http://mikejolley.com/projects/download-monitor/add-ons/page-addon/).

= Can I upload .xxx filetype using the uploader? =

Download Monitor uses the WordPress uploader for uploading files. By default these formats are supported:

* Images - .jpg, .jpeg, .png, .gif
* Documents - .pdf, .doc, .docx, .ppt, .pptx, .pps, .ppsx, .odt, .xls, .xlsx
* Music - .mp3, .m4a, .ogg, .wav
* Video - .mp4, .m4v, .mov, .wmv, .avi, .mpg, .ogv, .3gp, .3g2

To add more you can use a plugin, or filters. This post is a good resource for doing it with filters: [Change WordPress Upload Mime Types](http://www.paulund.co.uk/change-wordpress-upload-mime-types).

= Can I link to external downloads? =

Yes, you can use both local paths and external URLs.

= My Download links 404 =

Download links are powered by endpoints. If you find them 404'ing, go to Settings > Permalinks and save. This will flush the permalinks and allow our endpoints to be added.

= Download counts are not increasing when I download something =

Admin hits are not counted, log out and try!

== Screenshots ==

1. The main admin screen lists your downloads using familiar WordPress UI.
2. Easily add file information and multiple versions.
3. The quick add panel can be opened via a link about the post editor. This lets you quickly add a file and insert it into a post.
4. Display regular download links or fancy ones all using shortcodes and templates.

== Changelog ==

= 1.4.2 = 
* Fix for site_url -> abspath
* Check if hash functions are supported before use.

= 1.4.1 =
* Fix file_exists error in download handlers

= 1.4.0 =
* MP6/3.8 admin styling. Requires 3.8.
* Polish translation.
* Turkish translation.
* Change capability required to view dashboard widget.
* Don't show "insert download" when editing a download.
* Allow pagination for the [downloads] shortcode. Simply add paginate=true to the shortcode.
* Reverted flush change in download handler to reduce memory usage on some hosting envrionments
* changed download handlers and fixed corruption when resuming files
* Calculate md5/sha1/crc32 hashes for files. Obtainable via methods or download_data, e.g. [download_data id="86" data="md5"]
* Added file_date data

= 1.3.2 =
* Cleaned up log table queries
* Tweaked download handler headers
* Tweaked logging
* Limit UA to 200
* Setcookie to prevent double logging
* Addons page (disable using add_filter( 'dlm_show_addons_page', '__return_false' ); )

= 1.3.1 =
* Added some new hooks
* FR and SR_RS updates

= 1.3.0 =
* Fix 0kb downloads in some hosting enviroments
* Added button to delete logs
* Fixed log page when no logs are present
* FR and HU updates
* Added dropdown for the default template option to make available templates more obvious
* Added version-list and title templates

= 1.2.0 =
* Option to redirect to files only (do not force)
* Fixed textdomains
* HU translation by Győző Farkas
* Fix dlm_upload folder when not using month/day upload folders.
* Fix IP lookup
* Resumable download support
* Tweaked download handler

= 1.1.2 =
* HTTPS headers for IE fix
* Italian locale

= 1.1.1 =
* Specify error statuses on wp_die messages e.g. 404 for missing files.
* Moved DONOTCACHEPAGE

= 1.1.0 =
* Fixed admin notices
* Added download link to admin 'file' column for copying and pasting
* Farsi localisation
* Wrapping content in a [download] shortcode will wrap it in a simple link.

= 1.0.6 =
* Hide taxonomies from nav menus
* Fix categories in download_data method.

= 1.0.5 =
* When do_not_force is enabled, still replace abspath with home_url
* Exclude dlm_download from search and disable query var
* Added category_include_children option for downloads shortcode
* Fixed logs time offset.

= 1.0.4 =
* Tweak admin page detection to work when no downloads exist.
* Fix dashboard widget warning.
* Add filters to logs and export csv function.
* Added extra columns to CSV.

= 1.0.3 =
* Fix config page to work with multibyte tab names.
* Japanese locale by hide92795
* Admin CSS/script conditonally loaded
* Versions are now strtolower to be compatible with version_compare and to standardise numbers.

= 1.0.2 =
* Only use wp_remote_head to get fielsize on remote files. Prevents timeouts when a file doesn't exist.
* If a filesize cannot be found, set to -1 to prevent re-tries.
* Insert button added to all CPT except downloads.
* French locale by Jean-Michel MEYER.

= 1.0.1 =
* Update blockui
* Workaround root relative URLS

= 1.0.0 =
* Complete rewrite of the plugin making use of custom post types and other best practices. Fresh start version '1' to prevent auto-updates (legacy importer needs to be used to migrate from old versions).