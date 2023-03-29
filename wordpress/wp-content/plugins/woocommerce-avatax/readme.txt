=== Avalara AvaTax ===
Contributors: Avalara
Donate link: https://www.avalara.com/us/en/index.html
Tags: avalara, avatax, sales tax, taxes, tax, compliance, avalara avatax, collect taxes, tax returns, vat, sales taxes
Requires at least: 5.2 or higher
Tested up to: 6.1.1
Requires PHP: 5.3 or higher
Stable tag: 2.2.0

Automate sales tax calculation for your online business with the AvaTax plugin from Avalara! AvaTax determines and calculates the latest rates based on location, item, legislative changes, regulations, and more.

== Description ==

Automate sales tax calculation for your online business with the AvaTax plugin!

= AvaTax Plugin =
With WooCommerce AvaTax, you can stop guessing at your tax rates and immediately streamline tax rate calculation and management. 
Rather than manually adding a bunch of tax and shipping rates to your store, you can use Avalara's tax codes to automatically calculate the appropriate tax for each order based on your store address and the customer address. 
All tax information will be recorded properly in your Avalara account to make return filings as (relatively) painless as possible.


= Demos =

* Coming Soon

= Documentation =
* [See Full Documentation](http://docs.woocommerce.com/document/woocommerce-avatax/)

= Troubleshooting =
* [See Full Documentation](http://docs.woocommerce.com/document/woocommerce-avatax/)

== Installation ==

= First time user =
Upload the entire 'woocommerce-avatax' folder to the '/wp-content/plugins/' directory. And then activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= Which customer address is used for tax calculations? =
If the shipping address is completed, then that address is used. Otherwise, the customer billing address is assumed to also be the shipping address, and this address is used for tax calculation. 
In either case, the customer email address is used as the primary customer ID in Avalara.

= Does this plugin support AvaTax Cross-Border features to calculate duties and import taxes? =
Yes! If you’re using Cross-Border calculation with AvaTax, you’ll automatically see those duties or import taxes reflected in your WooCommerce AvaTax tax calculation. No further configuration needed!

= Does this plugin support the calculation of the new Colorado Retail Delivery Fee? =
Yes, this plugin will automatically send the needed data to Avalara to determine if orders should include the new Retail Delivery Fee in Colorado. 
Our plugin is taking into consideration orders that include only virtual products, that have a local pickup shipping method in Colorado and tax exempt merchants and doesn’t charge them the tax fee. 
Also, while we’re sending the required data, you will need to map an item in your Avalara account to correctly assign the tax to a customer’s order. 
Please set up a new Retail Delivery Fee item and map it to the Retail Delivery Fee Tax Code (OF400000). 
Also, make sure that the Item Code is set as retail-delivery-fee You can find more information on mapping items to Avalara tax codes hereLastly, when creating manual orders, please add a “Retail Delivery Fees” fee item valued at $0.27 to automatically apply the fee to the transaction in Avalara. 
You can learn about manually adding fee items here. Note that this may result in two RDF fees appearing on the transaction in Avalara. To correct, please open the transaction in your Avalara Dashboard and delete the fee with the following format: fee_ID.
We are following the developer documentation for updates and will make improvements when possible. more information on how to do that here.

== Screenshots ==
1. Automate Sales Tax and Compliance with Avalara
2. Automate Sales Tax Returns with Avalara
3. Manage Your Tax Exempt Customers with Avalara
4. Where to Locate Your Account and Account ID Information
5. Where to Find Your Account and License Keys

== Changelog ==

= 1.17 =
* Please visit WooCommerce for the older changelogs [Avalara AvaTax](https://woocommerce.com/products/woocommerce-avatax/)

For more, please visit the Avalara website.
