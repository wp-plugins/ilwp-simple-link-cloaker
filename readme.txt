=== ILWP Simple Link Cloaker ===
Contributors: stevejohnson
Plugin URI: http://ilikewordpress.com/simple-link-cloaker/
Author URI: http://ilikewordpress.com
Donate link: http://ilikewordpress.com/donate/
Tags: redirection, tinyurl, 301, url shortener, url, affiliate link cloaker
Requires at least: 2.7
Tested up to: 3.0.1
Stable tag: trunk

A very simple affiliate link cloaker plugin for WordPress. Doesn't do tracking, tie your shoes, doesn't do anything but redirects.

== Description ==

Experienced affiliate marketers know the necessity of 'cloaking' outgoing affiliate URLs. But it wasn't easy. You either had to build the redirects by hand, use hokey meta refresh tags, upload a bunch of PHP files to your server, or use some complicated WordPress plugin that basically tried to wipe your rear for you while guessing what link was supposed to do what and go where. No more. This plugin is simplicity in action. If you're at all capable of copying/pasting or writing down a simple URL, and don't need fancy tracking and CTR stats, this plugin's for you. You're not limited to a certain folder name or names, you can make the outgoing URL as long or short as you want it, make it say anything you want. Doesn't matter.

Enjoy.

If you like the plugin, and it helps you through your affiliate day, a small donation would be welcome and sincerely appreciated. You can donate at http://ilikewordpress.com/donate/. Thank you.

== Installation ==

1. FTP the entire simple-cloaker directory to your WordPress blog's plugins folder (usually /wp-content/plugins/).

2. Activate the plugin on the "Plugins" tab of the administration panel.

3. Refer to the usage section of this guide or visit the plugin page at http://ilikewordpress.com/simple-link-cloaker/.


== Upgrading ==
1. Deactivate plugin
2. Upload updated files
3. Reactivate plugin

Deactivation notes:
*  If you want to deactivate and discontinue use of this plugin, you should click the "Delete All Simple Link Cloaker Redirects" button at the bottom of the options page. This will remove all existing redirects from the .htaccess file and from the WordPress options table. Then deactivate the plugin on the plugins page and remove the files from the plugin folder.

== Frequently Asked Questions ==

= How do I add a redirect? =
General Sequence for a new redirect:
1. Write your post, highlight your anchor (link) text, then click the 'link' button (assuming you're using the Visual Editor).
2. In the Link URL box, enter the link address without your blog URL, starting with a slash.
3. Add your other link goodies like 'target' or 'class', click Insert.
4. Finish and publish your post.
5. Go to Settings -> Simple Link Cloaker.
6. In the Add New section, enter the Link URL you just used into the left box.
7. Enter your affiliate link in the right box.
8. Click "Add Cloaked Links".
9. Done.

Say you want to redirect the URL "http://myblog.com/recommends/hostgator/" to your HostGator affiliate link. In the Link URL box, you'd enter "/recommends/hostgator/". You can use any 'folder' name you want, or none at all. So, you could have one link show as "/recommends/product1/" and another as "/go/product2". Select your Link URL and copy it to the clipboard for later use, or just remember it. 

= What do I use for a link URL? =
Doesn't matter a whit, as long as you don't use a URL that already exists on your blog. If you do, you're likely to experience unpredictable results, like sending your visitor to a porno site (just kidding). Seriously though, try not to. There're lots of URL variations you can use, your imagination's the limit.


== Changelog ==
= 1.4 =
* Added javascript URL validation in an attempt to cut down on htaccess errors
* that would cause the server to throw 500 Server errors. While the method is not
* foolproof, it should help.
		
= 1.3.2 =
* Use of WP's sanitize_title_with_dashes filter inadvertently removed slashes
* within entered redirect slugs. Copied and modified WP's function to preserve
* entered slashes
		
= 1.3.1 =
* Added facility to sanitize entered URL (post) slugs for redirect. Replaces
* illegal URL chars with -; allows only underscore and dash.
		
= 1.3 =
* Fixed slight bug that caused PHP notice on initial installation
* Fixed PHP warning caused by absence of posts
* Updated for WP 2.8

= 1.2 =
* Fixed redirect to work with multi-value query strings
		
= 1.1 =
* Fixed bug where existing redirects were deleted upon addition of new ones.
* Caused by duplicate keys in options


== Screenshots ==
1. The Simple Link Cloaker management page in the WordPress Admin.


== Known Issues ==

= .htaccess errors =
Minimal validation is done on your address inputs. I can't make this plugin completely stupid-proof. If you make a mistake, it is possible you could cause your site to be unusable. If that happens, FTP into your server and delete the .htaccess file. Before you delete, you can try to edit it first: remove everything between the ## BEGIN SLC and ## END SLC lines in the file, save, and re-upload to your server.

= IIS (Windows) Servers =
I have not been able to test this plugin on a Windows server, so I don't know if it will work or not.