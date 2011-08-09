=== ggis-Subscribe ===
Contributors: bujanga
Donate link: http://dvector.com/oracle/2008/11/04/donate-to-ggis-plugins-and-modules/
Tags: email, subscription, list, form
Requires at least: 2.5
Tested up to: 3.2.1
Stable tag: 1.0.2

Manages subscriptions to email lists. Simply add [-ggis-subscribe-] to your post.

== Description ==

ggis-Subscribe gives authors an easy way to insert a form that performs emailing list subscription management. Simply add [-ggis-subscribe-] to your post and it will create an email list subscription management form. Depending on the settings and parameters you choose, it will insert either a short form, just an email address box with submit button or a full management form.

== ChangeLog ==

= 1.0.2 =
* FIX:	WordPress function wp_mail() now requires body content. Plugin now insures body content.

= 1.0.1 =
* UPDATE:	Change mail function to use the WordPress function wp_mail()

= 1.0.0 =
* NEW:	Add Plugin Usage and Information to the admin options page
* NEW:	Add option that sets the form's title
* NEW:	Add ability to use an alias in place of the list's email address


### Features and Requirements ###

+ Wordpress 2.5 or greater
+ Designation of success page
+ An email list to subscribe to. Currently supports:
1. ezmlm lists - subscribe by sending email to listname-subscribe-your=address.com@domain.com
2. mailman lists - subscribe by sending email to listname-request@domain.com

== Installation ==

1. Download and unzip the plugin
2. Upload the ggis-subscribe to the '/wp-content/plugins/' directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Edit the ggis Subscribe options from the Settings menu.
* Add the email address of your mailing list. If you have more than one list, use commas between each address.
* If you prefer a nickname instead of the email address to show in the subscription form, precede the list address with the Nickname and a colon(:).
* Enter the URL of your thank you page (next page)
* Enter the URL of you main subscription management page.
+ Yes. Create and publish your subscription management first and then finish with your ggisSubscribe options.	
5. Place `[-ggis-subscribe-]` in your posts.


== Frequently Asked Questions ==

= Does ggis Subscibe support XXX mailing list server? =

At this time, it only supports;

1. ezmlm lists - subscribe by sending email to listname-subscribe-your=address.com@domain.com
2. mailman lists - subscribe by sending email to listname-request@domain.com

If you would like additional email server support, please submit a comment to that effect.

= Can this be used to unsubscribe? =

Yes, that is part of the full subscription management form.

= How can I adjust the form's appearance =

You should be able to fully control your form's appearance using CSS. The HTML output is fully embedded with CSS tags to make personalization easy. As an example:
>	`input.ggis-subscribe-email { width:90%; }`

== Screenshots ==

1. A screenshot of the subscription management form, screen1.png.
2. A screenshot of the ggis Subscribe Settings page, screen2.png

== Usage ==

A subscription form may be inserted on a post, page, or text widget by including the following code in your text.

>	`[-ggis-subscribe %formtype "%listname"-]`

Here is an explanation of the fields:

1. ggis-subscribe - identifies the code (required)
2. formtype - identifies the form type
* 0, default - full subscription management form
* 1 - subscribe only form , requires "listname"
3. listname - identifies the list to include in a subscription only form

### In a Widget? ###

A subscription form may be placed into the standard text widget using the methods above. For widget use, I suggest using only formtype=1, the short form.
