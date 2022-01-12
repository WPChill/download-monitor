=== Download Monitor ===
Contributors: wpchill, silkalns, barrykooij, mikejolley
Tags: download manager, document management, file manager, digital store, ecommerce, document management plugin,  download monitor, download counter, password protection, download protection, password, protect downloads, tracker, sell, shop, ecommerce, paypal
Requires at least: 5.4
Tested up to: 5.8
Stable tag: 4.4.14
License: GPLv3
Text Domain: -
Requires PHP: 5.6

Download Monitor is a plugin for selling, uploading and managing downloads, tracking downloads and displaying links.

== Description ==

Download Monitor provides an interface for uploading and managing downloadable files (including support for multiple versions), inserting download links into posts, logging downloads and selling downloads!

= Features =

* Add, edit and remove downloads from a familiar WP interface; Your downloads are just like posts.
* Sell your downloads from within your WordPress website!
* 100% Gutenberg compatible, including a new Download Monitor Download Block. Type /download to use it!
* Quick-add panel for adding downloads / files whilst editing posts.
* Add multiple file versions to your downloads each with their own data like download count and file links.
* Define alternative links (mirrors) per download version.
* Categorize, tag, or add other meta to your downloads.
* Display download links on the frontend using shortcodes.
* Change the way download links get displayed via template files.
* Track downloads counts and log user download attempts.
* Member only downloads, requires users to be logged in to download your files.
* Customisable endpoints for showing pretty download links.

Download Monitor has been featured on the websites of some of the most popular and leading businesses in the WordPress ecosystem, such as WPBeginner, Pagely, Jilt, WP Fusion & Kinsta.

> #### Download Monitor Extensions
> Extend the core Download Monitor plugin with it's powerful extensions.
>
> Some of our popular extensions include: 
-  [Gravity Forms Gated Content](https://www.download-monitor.com/extensions/gravity-forms/?utm_source=wp-plugin-repo&utm_medium=link&utm_campaign=description-block-gravity-forms-lock) - easily create a download gate with Gravity Forms. Require users to fill-in a form before accessing a PDF any other type of download.
- [Page Addon](https://www.download-monitor.com/extensions/page-addon/?utm_source=wp-plugin-repo&utm_medium=link&utm_campaign=description-block-page-addon)
- [MailChimp Download After Sign up](https://www.download-monitor.com/extensions/mailchimp-lock//?utm_source=wp-plugin-repo&utm_medium=link&utm_campaign=description-block-mailchimp-lock) - Allow access to file downloads only for people who already belong to a MailChimp mailing list. This extensions facilitates the download after sign up in a MailChimp list.
- [Email Lock](https://www.download-monitor.com/extensions/email-lock/?utm_source=wp-plugin-repo&utm_medium=link&utm_campaign=description-block-email-lock)


>
> Want to see more? [Browse All Extensions](https://www.download-monitor.com/extensions/?utm_source=wp-plugin-repo&utm_medium=link&utm_campaign=description-block-browse-all)

= Documentation =
We have a large Knowledge Base on our [Download Monitor website](https://www.download-monitor.com/kb/?utm_source=wp-plugin-repo&utm_medium=link&utm_campaign=description-documentation) that contains documentation about how to how to setup and use Download Monitor.

Are you a new Download Monitor user? Read these articles on how to get your files ready for download with Download Monitor:

1. [How to install Download Monitor](https://www.download-monitor.com/kb/installation/?utm_source=wp-plugin-repo&utm_medium=link&utm_campaign=description-documentation)
2. [How to add your first download in Download Monitor](https://www.download-monitor.com/kb/adding-downloads/?utm_source=wp-plugin-repo&utm_medium=link&utm_campaign=description-documentation)
3. [How to list your first download on your website with the download shortcode](https://www.download-monitor.com/kb/shortcode-download/?utm_source=wp-plugin-repo&utm_medium=link&utm_campaign=description-documentation)

More advanced topics that a lot of people find interesting:

1. [Learn more about the different ways you can style your download buttons](https://www.download-monitor.com/kb/content-templates/?utm_source=wp-plugin-repo&utm_medium=link&utm_campaign=description-documentation)
2. [Learn more about how to customize your download buttons](https://www.download-monitor.com/kb/overriding-content-templates/?utm_source=wp-plugin-repo&utm_medium=link&utm_campaign=description-documentation)
3. [Learn more about what actions and filters are available in Download Monitor](https://www.download-monitor.com/kb/action-and-filter-reference/?utm_source=wp-plugin-repo&utm_medium=link&utm_campaign=description-documentation)

= Contributing and reporting bugs =

You can contribute code to this plugin via GitHub: [https://github.com/download-monitor/download-monitor](https://github.com/download-monitor/download-monitor)

You can contribute localizations via Transifex [https://www.transifex.com/projects/p/download-monitor/](https://www.transifex.com/projects/p/download-monitor/)

= Support =

Use the WordPress.org forums for community support. If you spot a bug, you can of course log it on [Github](https://github.com/download-monitor/download-monitor) instead where we can act upon it more efficiently.

Unfortunately we can't offer you help with a customisation. Please consider hiring a developer for your website's customizations.

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

More documentation can be found in our [Knowledge Base](https://www.download-monitor.com/kb/).

== Screenshots ==

1. Easily add downloads to your website with our Gutenberg block!
2. The main admin screen lists your downloads using familiar WordPress UI.
3. Easily add file information and multiple versions.
4. The quick add panel can be opened via a link about the post editor. This lets you quickly add a file and insert it into a post.

== Changelog ==

See <a href="https://github.com/WPChill/download-monitor/blob/master/changelog.txt" target="_blank">changelog</a>
