=== panelhelper ===
Contributors: Appalify
Tags: smm panel api, API, SMM Panel, SMM Services, SMM panel wordpress plugin
Requires at least: 3.9
Tested up to: 6.6.2
Stable tag: 2.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Connect your Wordpress website to an SMM Panel API.

== Description ==

The plugin will automate your order processing on an SMM panel.
Once a customer places an order via Woocommerce it is automatically sent to your connected SMM API Server for processing.

Since many SMM panel API servers don't have a wordpress plugin or don't have a clear dashboard with many functions, this plugin will help you in selling API Services, by automating tasks and giving you a clean overview, so you don't miss failed orders or empty balances.

Features:
- automatically order SMM Panel Services with each Woocommerce order
- track the status of these orders through our plugin
- track your servers balance
- get notified on failed orders in the dashboard, so that you can resend them
- automatically update woocommerce orders on API order completion
- import all API services automatically
- fast speed
- intuitive design

If you are missing some core features, don't be afraid to send us an email at team@appalify.com.


Requirements:
- Wordpress
- Woocommerce

== Installation ==

Installing "panelhelper" can be done either by searching for "panelhelper" via the "Plugins > Add New" screen in your WordPress dashboard, or by using the following steps:

1. Download the plugin via WordPress.org
1. Upload the ZIP file through the 'Plugins > Add New > Upload' screen in your WordPress dashboard
1. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Dashboard with status reports about orders and servers
2. Orders list
3. Woocommerce Plugin Settings

== Frequently Asked Questions ==

= What is panelhelper? =

Panelhelper is an API integration plugin that will help you connect your store to an SMM Panel.

= How can I connect my wordpress website to an SMM panel? =

You can use the smm wordpress plugin, panelhelper, to connect your wordpress website to an SMM panel.

Click on 'Servers' and then 'Add Server'.
This will open a window, where you can add the url and the API key that you got from the SMM panel.

= How can I add a service? =

Before adding a service make sure you have already added a Server. 
If you click on 'add service' in the 'Services' tab you will be greeted with an entry form.

For this explanation, we will be using this example service:

Server example:
ID- 111 Service- Plays Instant 10% drop	Rate per 1000 $0.14	MIN 1  MAX 100

Entry Example:
111, plays,100, 1, 0.14, Server1

You can enter the id in the service id field in the form. The service name is just there for you to find the product easily, so you can name this what you like. Enter the correct min and max order and enter the price with out the currency symbol and with a period. If the price is $0.14 like in the example, you enter 0.14.
In the dropdown you choose the server, where you got the service from.

If you entered everything correctly you will be returned to the services page (please allow up to 5 seconds for this to happen), where you will see your entry, if not, the form page will reload.
If the form page keeps reloading because you entered something incorreclty you can put a space in every box except for the id and the server or contact support and send a screenshot of what you entered.

Important: If your server changes any values of a service, except for the ID, don't worry, our plugin will automatically get the new correct data based on the service id. So you don't have to change it.

= How can I add a service to a woocommerce order? =

1. create a new woocommerce product and scroll down until you see the settings
2. set product type to variable product
3. Inventory -> Limit purchases to 1 item per order(on)
4. click on attributes and create an attribute named "Quantity". It must(!) be called exactly "Quantity", otherwise it won't work.  If you want to sell 10 and 20 of your service enter "10 | 20" in values. If you make a mistake in this step, you will have a quantity error in your panelhelper orders.
5. click used for variations and un-check the rest
6. click on variations and click on generate variations
7. enter the price for each product 
8. Click on the Panelhelper row
9. Click the activate Panelhelper checkbox
10. select your service and enter a title that should be placed above the user input field.
11. save or update the product

== Changelog ==


= 2.1.0 =
* 2024-09-27
* better error messages

= 2.0.1 =
* 2024-09-27
* bug fixes

= 2.0.0 =
* 2024-09-24
* custom comment support
* email notifications

= 1.3.0 =
* 2024-06-05
* added better debug messages


= 1.2.0 =
* 2024-05-29
* added import all services 
* added a better search algorithm
* Bug Fixes

= 1.1.0 =
* 2024-05-24
* added dashboard charts to the free version
* Bug Fixes

= 1.0.0 =
* 2024-05-18
* Initial release






== Upgrade Notice ==

= 1.3.0 =
* 2024-06-05
* added better debug messages
