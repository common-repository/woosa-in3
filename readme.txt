=== Woosa - in3 voor WooCommerce ===
Contributors: woosa
Donate link: https://woosa.nl/
Tags: pay in 3 terms, payment method, pay later, 3 terms payment
Requires at least: 4.5
Tested up to: 5.3
Stable tag: trunk
Requires PHP: 7.0

Accepteer betalingen in drie (3) termijnen in jouw WooCommerce webshop.

== Description ==

Biedt betalen in 3 termijnen met 0% rente aan in jouw WooCommerce webshop. In3 staat garant voor de betaling, je loopt als webshop geen enkel risico!

Woosa - in3 voor WooCommerce, is een betaalmethode welke toegevoegd wordt aan jouw afrekenproces. Als het factuurbedrag tenminste €100,00 is, verschijnt in3 als betaalmethode. Zodra de klant kiest voor betalen met in 3 termijnen wordt er eerst een vlotte kredietcheck uitgevoerd waarna de klant direct weet of de aanvraag goedgekeurd wordt. Wanneer de klant door de kredietcheck heen gekomen is, dient de eerste termijnbetaling direct voldaan te worden met iDEAL. Na deze betaling kun je het product opsturen naar de klant. De klant betaald de tweede en derde termijn na 30 en 60 dagen. Hiervoor worden een aantal mail- en sms-herinneringen. Ongeacht of de klant betaald of niet, in3 betaald je het volledige factuurbedrag uit na 15 dagen.

Belangrijke functionaliteiten van de in3 WooCommerce plugin
* 	Aanbieden van de in3 betaalmethode
*	Automatisch controleren van de kredietwaardigheid van jouw klant
*	Eerste betaling via iDEAL
*	Mogelijkheid om direct vanuit WooCommerce een refund toe te passen
*	Verhoging van conversie door het noemen van een ‘al vanaf … per maand’ prijs.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/woosa-in3` directory, or install the plugin through the WordPress plugins section directly.
1. Activate the plugin through the 'Plugins' section in WordPress
1. Go to WooCommerce->Payments->in3 to configure the plugin


== Screenshots ==

1. General payment settings


== Frequently Asked Questions ==

= Wordt er rente berekend over de termijnen? =

Nee, er wordt geen rente berekend over de termijnen. Daarom wordt er een maximum van 3 termijnen aangeboden.

= Hoe werkt betalen in 3 termijnen? =

Nadat de klant is door de kredietcheck is gekomen, dient de eerste termijn direct voldaan te worden met iDEAL. De daaropvolgende 2 termijnen worden na 30 en 60 dagen gefactureerd.

= Hoe snel betaald in3 uit? =

In3 verzekerd het totaalbedrag en keert deze 15 dagen na besteldatum uit.


== Upgrade Notice ==

Every update comes with fixes and improvements.


== Changelog ==

= 1.3.1 - 2020-06-25 =

* [FIX] - Fixed missing in3 offer from cart page

= 1.3.0 - 2020-06-08 =

* [FIX] - Do not show in3 offer if the payment method is disabled
* [TWEAK] - Improve the style of displaying in3 logo image in checkout page
* [TWEAK] - Some translation changes
* [DEVELOPER] - Added filters for min and max amount of price at displaying in3 offer: `in3-gateway-min-price` and `in3-gateway-max-price`

= 1.2.2 - 2020-05-26 =

* [FIX] - Make sure the icon logo CSS is not overwritten by theme
* [TWEAK] - Replace datepicker field with a separate field for day, month and year

= 1.2.1 - 2020-05-19 =

* [FIX] - Fixed particular conflict with house number field in checkout page
* [FIX] - Fixed JS script conflicts with custom themes
* [FIX] - Fixed missing images from datepicker field in checkout page
* [TWEAK] - Add a new in3 logo behind the text

= 1.2.0 - 2020-05-14 =

* [FIX] - Fixed the problem with displaying the price excluding the tax
* [FIX] - Fixed `Uncaught Error: Class 'Woosa_IN3\Assets' not found` on activation for certain servers
* [FIX] - Fixed the conflicts with translated texts
* [FIX] - Fixed the conflict which leads to requiring house number even if it's provided
* [FEATURE] - Added new setting options to set the minimum and maximum amount of using in3 payment method
* [FEATURE] - Added new setting option to set whether or not do disable price per term in product or category page
* [FEATURE] - Added support for Product bundles plugin
* [TWEAK] - in3 payment method is available only in Netherlends
* [TWEAK] - Added in3 logo in product page and listing products page
* [TWEAK] - Added a tooltip with info in checkout page

= 1.1.2 - 2020-04-02 =

* [TWEAK] - Add a custom order status for unprocessed orders

= 1.1.1 - 2020-03-18 =

* [TWEAK] - Additional verification for transaction number

= 1.1.0 - 2020-03-03 =

* [FIX] - Fixed error in checkout page because of too long string
* [FIX] - Add cron job which runs every 15 minutes and check payment status for pending payment orders
* [FIX] - Do not ask for CoC number if there is no company field available

= 1.0.9 - 2019-11-11 =

* [FIX] - Fixed 500 internal server error on certain product types

= 1.0.8 - 2019-10-21 =

* [TWEAK] - Adjust birthdate format in checkout page

= 1.0.7 - 2019-09-19 =

* [FIX] - Fixed multisite dependency issue

= 1.0.6 - 2019-08-29 =

* [FIX] - Fixed some wrong text translations
* [FIX] - Fixed wrong customer's initials

= 1.0.5 - 2019-08-06 =

* [FIX] - Fixed incorrect price term payment displayed
* [TWEAK] - Tiny changes to font size

= 1.0.4 - 2019-07-19 =

* [FIX] - Adjustments to the returning URL parameter to avoid accessing a wrong URL by IN3
* [FIX] - Fixed the issue with changing order status after the payment is done

= 1.0.3 - 2019-06-19 =

* [FIX] - Solved the mispricing of the order total price
* [TWEAK] - Few changes to order processing logic

= 1.0.2 - 2019-06-11 =

* [FIX] - Fixed house number issue
* [TWEAK] - Added option to specify which checkout field to use for house number

= 1.0.1 - 2019-05-15 =

* [FIX] - Fixed wrong key of settings
* [TWEAK] - Added tooltip with description of IN3
* [TWEAK] - Display message in checkout if credit check is not accepted

= 1.0.0 - 2019-05-14 =

* This is the first release, yeey!