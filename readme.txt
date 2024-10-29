=== Asciify ===
Contributors: cyclonecode
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=VUK8LYLAN2DA6
Tags: ascii, image, attachment
Requires at least: 2.9.0
Tested up to: 5.6
Requires PHP: 5.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

This plugin creates text based images for uploaded attachments.

== Support ==

If you run into any trouble, don’t hesitate to add a new topic under the support section:
<a href="https://wordpress.org/support/plugin/asciify">https://wordpress.org/support/plugin/asciify</a>

== Installation ==

- First unzip the archive under the `wp-content/plugins` folder and then activate the asciify plugin.
- Now configure the plugin by going to `wp-admin/tools.php?page=asciify`.
- When enabled a text based image will be created; this image size can then be used when attaching existing
media to posts or pages.

== Screenshots ==

1. A asciified image representation.
2. Original and asciified image.

== Changelog ==

= 1.1.0 =
- Fixed an issue where image resources were not destroyed.
- Improved image rescale method.
- Add autoloader.
- Use admin_post action.
- Refactor code.

= 1.0.0 =
- Initial commit.
