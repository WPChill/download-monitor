=== Plugin Name ===
Contributors: mikejolley
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=10691945
Tags: download, downloads, monitor, hits, download monitor, tracking, admin, count, counter, files
Requires at least: 2.8
Tested up to: 3.3.2
Stable tag: 3.3.5.9

Plugin with interface for uploading and managing download files, inserting download links in posts, and monitoring download hits.

== Description ==

Download Monitor is a plugin for uploading and managing downloads, tracking download hits, and displaying links.

You can contribute code to this plugin via GitHub: https://github.com/mikejolley/download-monitor

Note, my work on this plugin is on hold due to other projects.

For older versions of wordpress use the older Download Monitor version 2.2.3 which is available from http://wordpress.org/extend/plugins/download-monitor/download/ (tested and working in Wordpress 2.0 and 2.3).

Plugin contains filetype icons from the Fugue Icon Pack by Yusuke Kamiyamane.

= Features =

*	Built in Download Page function with built in sorting, pagination, and search. This was going to be a paid addon but i'm too nice - so please donate if you use it!
*	Records file download hits but does **not** count downloads by wordpress admin users.
*	Stats on downloads and a download log for viewing who downloaded when.
*	Uses shortcodes (backward compatible with old [download#id] style).
*	Editor button - upload and add a download stright from a post.
*	Custom redirects to downloads.
*	Add downloads to text widgets, the content, excerpts, and custom fields.
*	Mirror support (selected at random) + mirror deadlink checker
*	Download Categories and tags.
*	Member only downloads, can also have a minimum user level using custom fields.
*	Localization support.
*	Admin for managing downloads and also changing hit counts - just in case you change servers or import old downloads that already have stats.
*	Custom URL's/URL hider using mod_rewrite.

= Localization =

Need it in a different language? Some users have been kind enough to provide some translation files. Note, I am not responsible for any of these.

*	Polish Translation by Maciej Baur
*	Spanish translation by David Bravo
*	Albanian translation by romeolab
*	Hebrew translation by David Tayar
*	German translation by Frank Weichbrodt
*	German translation by Michael Fitzen
*	French translation by Li-An
*	Finish translation by Ari Kontiainen
*	Polish (alt) translation by Krzysztof Machocki aka Halibutt
*	Japanese Translation by Chestnut
*	Indonesian Translation by Hendry Lee
*	Brazilian translation by Rodrigo Coimbra
*	Lithuanian translation by Nata
*	Italian translation by Gianni Diurno

Plugin contains filetype icons from the Fugue Icon Pack by Yusuke Kamiyamane.

== Installation ==

= First time installation instructions =

Installation is fast and easy. The following steps will guide get you started:

   1. Unpack the *.zip file and extract the /download-monitor/ folder and the files.
   2. Using an FTP program, upload the /download-monitor/ folder to your WordPress plugins directory (Example: /wp-content/plugins).
   3. Ensure the <code>/wp-content/uploads</code> directory exists and has correct permissions to allow the script to upload files.
   4. Open your WordPress Admin panel and go to the Plugins page. Locate the "Wordpress Download Monitor" plugin and
      click on the "Activate" link.
   5. Once activated, go to the Downloads admin section.
   
Note: If you encounter any problems when downloading files it is likely to be a file permissions issue. Change the file permissions of the download-monitor folder and contents to 755 (check with your host if your not sure how).

== Frequently Asked Questions ==

You can now view the FAQ in the documentation: http://mikejolley.com/projects/download-monitor/

== Screenshots ==

1. Wordpress 2.7 admin screenshot
2. Download page single listing
3. Download page listings
4. More download page listings

== Changelog ==

= 3.3.5.9 =
* 	XSS Fixes

= 3.3.5.8 =
*	Clear cache after updating category
* 	Fix image path
* 	coolhpy updated the chinese lang

= 3.3.5.7 =
*	File browser path fix
* 	When using 'wrap' with shortcodes, output the wrap even if its not ul
 
= 3.3.5.6 =
*	GitHub screwed my merge

= 3.3.5.5 =
*	Fixed XSS issue in uploader.php
* 	Added parameter "front_order" to download_page shortcode (by holtgrewe)
*	Alphabetized subcategories prior to display. Non-elegant approach required changing the link to only the count
*	Changed handling of post_id field. Formerly, only one ID was allowed. Now multiple IDs can all be linked (comma separated). (by bkloef)

= 3.3.5.4 = 
*	Removed the sponsorship messages and links to translators sites due to Automattic's request
*	Added SSL url support (thanks cliffpaulick)
*	wp_dlm_download hook in download.php before download starts

= 3.3.5.3 = 
*	Updated links + other info
*	table prefix fix
*	renamed dlm_let_to_num function to prevent conflicts
*	Fixed download page search in wp 3.1
*	Ampersand filename fix
*	Small change to download.php mimes

= 3.3.5.2 = 
*	Corrected taxonomy hierarchy bug

= 3.3.5.1 =
*	Fixed download.php to server multiple files at once
*	Updated localisation
*	Small change to IP detection
*	Few query optimisations
*	Used Transients API in certain parts for speed (2.8+ wordpress requirement with this version)

= 3.3.5 =
*	Sort Asc/Desc on admin page
*	Updated URL for IP lookup (log page)
*	Changed IP Address detection code to deal with proxies
*	Added Japanese translation
*	Fixed download of a file with a relative path, inside the wordpress directory
*	Fixed delete/replace download code
*	IP Blacklist option added (for preventing hits/logs)
*	get_downloads/downloads shortcode can exclude a category using a minus symbol
*	'total_downloads' and 'total_files' shortcodes for showing global info

= 3.3.4.4.1 =
*	Quick wpdb fix

= 3.3.4.4 =
*	Changes to download.php to fix problems on certain (crappy) hosting
*	General bug fixes

= 3.3.4.3 =
*	Fixed some invalid path bugs on certain installs
*	'the_content' hook on download descriptions was causing dupe content so Ive added my own filters called 'download_description'
*	Fixed empty delimiter bug in admin
*	Force download option when adding directory is fixed
*	wp_dlm_log_timeout option added to stop multiple log/hit increases by the same user
*	Fixed a load of notices shown in WP_DEBUG mode

= 3.3.4.2 =
*	Uploader permission error fix
*	VIP Function fix (thanks to Vincent Prat)
*	orderby version added to get_downloads function
*	Case of filesize units modified
*	$user_ID fix in download.php - used to be null for non-logged in, now it equals 0
*	Removed Display Table from images on download page to prevent stretching

= 3.3.4.1 =
*	Menu hiccup repaired

= 3.3.4 =
*	WordPress 3.0 compatibility checked
*	strpos bug fix (thanks toemon)
*	do_shortcode added to download page custom meta
*	apply_filters('the_content') on descriptions so third party plugins work (like audio player)
*	Changed styles of download page to make it fill the width, and work better with wp 3.0's default theme
*	Modified capibilities so edit and add are both not required
*	Downloads outside of wordpress (file paths) are now forced to download regardless of your settings.

= 3.3.3.11 =
*	Fixed tags issue in downloadable_file.class.php

= 3.3.3.10 = 
*	Added new translations
*	Global member only option in configuration
*	Should work with ftp:// downloads
* 	Mirror fix
*	Better error message for remote files
*	Added hook: do_action('download_added', $download_insert_id);
*	Added fix for all downloads being displayed when cat is empty.
*	Other get_downloads bits and pieces, for example, no point checking tags and cats if the 'include' option is used because that should take prority.
* 	Shortcode handling patch by chilano
*	Forced all tags lowercase since the download page could not handle a mixture

= 3.3.3.9 = 
*	Pre-loading meta and relationships again - less queries, better performance
*	Improved download page loading time
*	Using built in stripslashes_deep to fix magic_quotes stuff
*	Made filesize save to custom fields so we don't need to look it up every pageload
*	Fixed strstr bug on delete
*	Added option in config to disable mirror selection
*	wp_dlm_ins_button dropdown on post screen is history - use the media button instead. Dropdown was slow and not very usable (when you had shed loads of downloads).
*	htmlspecialchars_decode in download.php for people with dodgy file names

= 3.3.3.8 = 
*	Updated German translation
*	Fatal error: Unsupported operand types in /var/www/wp-content/plugins/download-monitor/classes/downloadable_file.class.php on line 226 glitch should be fixed

= 3.3.3.7 = 
*	Added {mirror_1_url} {mirror_2_url} etc for custom formats.
*	Removed cache for download info as it cannot support using different formats in succession
*	New download_data shortcode lets you add the format inline example: [download_data id="1"]<a href="{url}">{title}</a>[/download_data]
*	Decendents glitch in get_downloads
*	Improved upload error messages on add page
*	Tidied up config page and added some example custom formats
*	Attempt to make wp_roles exist if not loaded
*	Added option to disable the file/folder browser
*	Top 5 Downloads widget shows downloads per category
*	file_browser_root filter added
*	Filesize tweakage
*	Include option added to get_downloads
*	req-role option as a replacement to min-level. Comma separate values.
*	Download logs as CSV file
*	Mod to paths shown when using file browser
*	Added new download page option - default_order which can be 'title', 'hits' or 'date'.
*	Added 'author' option to get_downloads - use user name e.g. [downloads query="author=admin"]

= 3.3.3.5 = 
*	Added an extra add_cap block for when it does not init correctly.
*	Fixed a missing image in admin
*	Added 'lang' to dl page search in case it exists in url.
*	Change wp_load code in download.php
*	Had to remove some of the DB prefetching, e.g. downloads+$meta_data since they were taking up wayyy to much memory. DB call less expensive in this case I believe!
*	^ But don't despair, I added some caching via wp_cache_add

= 3.3.3.5 = 
*	Forced init script to only run when in admin
*	Wrapped 'add_cap' functions in a check to prevent errors on activation when wp_roles is not available.
*	Updated download.php with better handling of relative>absolute urls
*	Bug in download.php where arguments were wrong way round in strstr functions
*	Made external download (when forced) work

= 3.3.3.4 =
*	Changed 'descendants' finder code
*	Updated a classname on download page to prevent a conflict with another plugin
*	German translation by Frank Weichbrodt added
*	Another German translation by Michael Fitzen added
*	{filetype_icon} added + icons

= 3.3.3 =
*	Multiple Category Support
*	Merged and improved add new/add existing download
*	Thumbnail Uploader
*	Bulk add from a directory on your server
*	Broken down plugin to make it easier to manage - new classes
*	Downloads now stored in wp-content/<upload dir>/downloads/ in date folders. This should keep them more organised
*	Fixed path bugs in download.php
*	Changed some headers in download.php. Theres a hook here to customize further - 'download_monitor_dlm_upload_dir'
*	Download admin shows download url in file column (linked)
*	Check for meta values on post uploader
*	Stripped slashes on meta values
*	Optimized download pattern/replace code
*	Updated download.php download logic to allow local downloads that are not within the wordpress installation!
*	Fixed admin search bug - named an input wrongly!
*	Attempted to fix file size reporting
*	Made Tables Lower cased finally - may need to BACKUP then reinstall tables
*	Changes to htaccess
*	LinkValidator Fix
*	Added jad and cod mime types to download.php
*	Ability to load custom format by name [download id="1" format="name_here"]
*	{filetype} added for custom formats

= 3.2.3 =
*	Download Page Query errors fixed
*	Download Page category exclusion fix
*	Updated build version
*	Oops. Changes root_dir to root to make file browser work. Thanks sklang

= 3.2.2 =
*	Small bugfix in uploader.php - cat ID
*	Changed stats graph calculation - thanks lggemini
*	Changes to headers in download.php to avoid caching
*	File Browser fixes - $root was clashing with something....
*	exclude_cat works in all sections of download_page now
*	Removed hardcoding of /uploads/
*	Added action to download.php - should be able to use it to stop a download if you want - maybe limiting downloads per day or something? Whatever you want...
*	Made it so if you post new file on 'edit' screen, the post date is updated.
*	Fixed the 'blank meta' section which blanks out custom field values when nothing is set.
*	Moved 'allow_url_fopen' check.
*	Someone said downloads don't work with spaces in the name. They do! Wasting my time sonny...
*	All work and no play make jolley a dull boy
*	Had to rename capibilities so they work. Apologies if you have to set this up again! Cheers to Mark Dingemanse.
*	{category_ID} custom format tag added. Useful if you want to send someone to its category on the DL page I guess. Also added {category_other} so when no category is set "other" is shown - this is because the download page can show an 'other' section if you want it to.
*	You can now manually edit the post date on the edit download screen.

= 3.2.1 =
*	Made meta query more efficient
*	Updated main localisation file
*	Added activation hook so admin should work now

= 3.2 =
*	{user} tag added for custom formats
*	'autop' option fix
*	Download page buttons applied with CSS so they are easier to customise/translate.
*	Fix for pagination bug after editing a download
*	Category output fix on edit downloads screen
*	Category urls on download page use ID rather than name to prevent errors when cats have the same names.
*	exclude_cat added to download_page shortcode
*	Localised 'hits' 'date' 'title' on download page
*	Option to disable the download logging
*	Read file 'chunked' some people found large files were corrupted so this should help (fingers crossed)
*	Added show_tags option to download page - displays x amount of tags on the download page.
*	File Browser root setting and download.php logic/mime types modified thanks to Jim Isaacs (jidd.jimisaacs.com)
*	Interface Improvements
*	Bulk edit categories, custom fields, tags, member only downloads
*	Added roles for download monitor admin - should be able to use with a role manager plugin if you want anyone other than admin to access the admin section e.g. http://wordpress.org/extend/plugins/capsman/
*	Change redirect after add
*	Edit Cat names/parents
*	Dedicated tags and thumbnails fields (they still use meta table though)

= 3.1.6 =
*	Nothing major - unreleased

= 3.1.5 =
*	Changed custom urls to make them more friendly for people with wordpress in a sub directory.
*	wp_die on download.php to make cleaner error messages
*	Much better pagination in admin
*	Order by 'meta' in downloads shortcode/get_downloads function - also must provide 'meta_name' and define the meta field to sort by. e.g. [downloads query="orderby=meta&meta_name=meta_sort"]

= 3.1.4 =
*	Added {referrer} option to the member redirect - now you could redirect to http://yourdomain.com/wp-login.php?redirect_to={referrer} for instance and they will go straight to the download right after.
*	Updated 'force' logic.
*	Moved mo/po file.

== Usage ==

Full Usage instructions and documentation can be found here: http://mikejolley.com/projects/download-monitor/
