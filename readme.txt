=== Plugin Name ===
Charge Anywhere Payment Gateway For WooCommerce
Contributors: Charge Anywhere
Tags: woocommerce Charge Anywhere, Anywhere, payment gateway, woocommerce, woocommerce payment gateway, woocommerce subscriptions, recurring payments, pre order, PCI Compliant
Requires at least: 3.0.1
Tested up to: 6.3.1
Requires PHP: 7.0.1
Stable tag: 1.13
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Charge Anywhere payment gateway integration for WooCommerce to accept credit cards directly on WordPress e-commerce websites.

== Description ==

[Charge Anywhere](https://www.chargeanywhere.com/) Payment Gateway for [WooCommerce](https://woocommerce.com/) allows you to accept credit cards payments into your Charge Anywhere merchant account from all over the world on your websites.

WooCommerce is one of the oldest and most powerful e-commerce solutions for WordPress. This platform is very widely supported in the WordPress community which makes it easy for even an entry level e-commerce entrepreneur to learn to use and modify.

#### Features
* **Easy Install**: This plugin installs with one click. After installing, you will have only a few fields to fill out before you are ready to accept credit cards on your store.
* **Secure Credit Card Processing**: Securely process credit cards without redirecting your customers to the gateway website.
* **Refund via Dashboard**: Process full or partial refunds, directly from your WordPress dashboard! No need to search order in your Charge Anywhere account.
* **Authorize Now, Capture Later**: Optionally choose only to authorize transactions, and capture at a later date.
* **Gateway Receipts**: Optionally choose to send receipts from your Charge Anywhere merchant account.
* **Enabled CREDIT & ACH service and convenience charges 
* **No PCI required**

#### Requirements
* Active  [Charge Anywhere](https://www.chargeanywhere.com/)  account – Request sandbox account  [here](https://corporate.chargeanywhere.com/contact-us/?Reason=SIPBasic)  if you need to test.
* [**WooCommerce**](https://woocommerce.com/)  version 3.3.0 or later.

== Installation ==

1. Login to Admin, choose Plugins > Add new. Search for the plugin "chargeanywhere" with in the search bar in the top right corner. After finding the plugin, click "Install Now".
    OR 
   Upload `chargeanywhere-woocommerce` folder/directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to WooCommerce => Settings
4. On the "Settings" page, select "Payment Gateways" tab.
5. Under "Payment Gateways" you will find all the available gateways, select "Charge Anywhere SIP Payment Gateway For WooCommerce" option
6. On this page you wil find option to configure the plugin for use with WooCommerce
7. Enter the API details (Login ID, Transaction Key)
8. Configurable elements:
9. To customize taxes and fee items order, copy review-order.php file from the `chargeanywhere-woocommerce` directory to `YOUR_THEME/woocommerce/checkout/review-order.php`

== Upcoming feature highlights ==
1. Implementation of a refund policy that offers users a discounted refund amount. For instance, if a user has applied a 10% coupon to a 10 USD cart and paid 9 USD, requesting a refund would result in a 10 USD refund.


**IMPORTANT:** You must obtain a Live Merchant Account before going to production. Information used on sandbox **CANNOT** be used for Live Merchants


== Frequently Asked Questions ==

= Do you Need to be PCI Compliant to use this Plugin
No. This plugin does not transmit card data to woocommerce, nor does it store full card data in woocommerce. All card data is sent directly to  Charge Anywhere, a level 1 PCI Compliant Gateway.

= Is SSL Required to use this plugin? =
Yes.

== Woocommerce Plugin to change ==

= If you need to change the order of fee structure to show Fees below Tax, copy review-order.php in plugin "woocommerce" and paste in wp-content/theme/[THEME]/woocommerce/checkout/
  https://docs.chargeanywhere.com/wp-content/uploads/2023/04/review-order.zip

== Changelog ==
= 1.0 =
* First Version
= 1.1 =
* Added the option for Email customer & Email Merchant and fixed spelling mistake
= 1.2 = 
* Plugin text changes
= 1.4 = 
* Added validation for data Sanitized, Escaped, and Validated
= 1.5 =
* Missed some data Sanitized, Escaped, and Validated
= 1.6 =
* chargeanywhere live url changed, Text field labels changed
= 1.7 =
* Enabled CREDIT & ACH service and convenience charges 
= 1.8 =
* Enabled Request & Response log
= 1.9 =
* Fixed some validation issues
= 1.10 =
* Fixed issues in tax & shipping amount at refund stage
= 1.11 =
* Fixed refund service fee issue
= 1.12 =
* Added conditions for transaction capture & return, service fee label made dynamic
= 1.13 =
* Removed additional fees sort. Convenience Fee will always come after Service Fee.
= 2.0 =
* Added support to WooCommerce Blocks.