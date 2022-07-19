=== GoogleSync - Bulk Product Editor for WooCommerce with Google Sheets ===
Contributors: nmedia
Tags: bulk products, bulk stock manage, bulk price editor, woocommerce products, woocommerce stock, stock update
Donate link: http://www.najeebmedia.com/donate
Requires at least: 4.3
Tested up to: 6.0
Requires PHP: 5.6
Stable tag: 6.10.3
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

GoogleSync is a WooCommerce plugin to manage products with Google Sheets in bulk.

== Description ==
GoogleSync is a brand-new, quick, and easy way to use Google Sheets to generate products and categories for your business. To add new products to WooCommerce or even to make a single adjustment, you must go through a long-form (product edit page).
Your time is wasted while you wait for login, website updates, and several clicks.

**One Click** *update/add thousands of products*
GoogleSync is a fantastic way to keep all of your products in one location, and it only takes one click to add thousands of products to your store. Please watch this video instruction to learn more about the features and how to use it.

= How It Works? =
[youtube https://youtu.be/pNdxG_otQ5c]

= Features =
* One Click Import
* Sync All Your Products
* Sync All Your Categories
* Add/remove product fields
* Sync from Store to Google Sheet
* Build using the latest Google App Script API

= PRO Features =
* Variable Products Supported
* Auto-Sync [hourly, twice daily, daily]
* Logging Auto Sync
* Export Existing Products into Google Sheet
* Export Existing Categories into Google Sheet
* Export Existing Variations into Google Sheet
* Metadata columns

[Learn More GoogleSync PRO](https://najeebmedia.com/wordpress-plugin/woocommerce-google-sync/)

== Use Cases ==
**Opening a new store** *Add all of your products to the Google sheet, select `Sync Products` and bask in the joy.*
**Managing an existing store** *To manage any updates or new products, export all of your products from your store to Google Sheets.*
**Managing the stock** If the present solution's stock management is driving you crazy, use GoogleSync to add `manage_stock` and `quantity` columns to the sheet, and you'll be free of the problem.
**Managing the price** *Price changes were frequent due to the economy's instability; however, GoogleSync will now adjust product prices in bulk for your store.*
**Variable products** *In WooCommerce, it is difficult to create variations and variable products; however, GoogleSync has made this process quite simple and quick.*
**Much more ...** *As per your needs*

== Installation ==
1. Download the plugin and activate it
2. Go to WooCommerce > Settings > Google Products
3. Enter your Google Credentials
4. Enter WooCommerce API/Secret Keys

== Frequently Asked Questions ==
= What is the difference between Pro and Free versions? =
The only import option in the free version is from a Google sheet to the store, and some fields, such as images, and variable products, cannot be imported. As mentioned above in the PRO features list, you can export (from store to Google sheet) and do much more when using the Pro version.
= Can I fetch my existing products/categories? =
Yes, in Pro version
= Should I need to connect with my own Google Account? =
Yes
= When connecting for the first time to Google Sheet, why do we notice a warning? Is this harmful? =
It's because the scripts we uploaded to the Google Sheet require certain permissions in order to run. Allowing these permissions is not problematic, since, for privacy reasons, we only have access to the current Google Sheet, not the whole Google Drive.
= How to link product metadata (custom fields)? =
[Watch this video](https://youtu.be/897tHE1PD7I)
= How to set up AutoSync? =
[Watch this video](https://youtu.be/vYPk73uGPcc)

== Screenshots ==
1. Import products from Google sheet to store
2. Showing products sync status in admin
3. Google sync columns
4. Settings page

== Changelog ==
= 6.10.3 July 19, 2022 =
* Bug Fixed: [Upsell Ids issue fixed](https://clients.najeebmedia.com/forums/topic/upsell_ids-product-data-is-not-fetched-and-causes-an-error/)
= 6.10.2- May 11, 2022 =
* Bug fixed: Variation image was not being fetched. Now it is fixed.
= 6.10.1 - May 11, 2022 =
* Feature: Fetch operation is optimized to make it more speedy.
* Bug fixed: A minor bug fixed due to the last update regarding the Reset function.
= 6.10 - May 11, 2022 =
* Bug fixed: [Product fetch issue fixed](https://wordpress.org/support/topic/error-while-fetching-the-products-in-google-sync/)
= 6.9 - April 28, 2022 =
* Bug fixed: Variations were not being fetched
= 6.8 - March 14, 2022 =
* Feature: Disconnect with current connect feature added.
= 6.7 - February 21, 2022 =
* Bug fixed: Dimensions update issue fixed
* Bug fixed: Fetch issue fixed when the dimensions are added
= 6.6 - February 1, 2022 =
* Bug fixed: [Product fetch issue fixed in PRO version](https://clients.najeebmedia.com/forums/topic/products-not-fetching-on-fetch-products/)
= 6.5 - January 31, 2022 =
* Bug fixed: [Meta data export issue fixed](https://clients.najeebmedia.com/forums/topic/googlesync-transferring-meta-data-to-googlesheet-from-wordpress/)
= 6.4 - January 26, 2022 =
* Bug fixed: Synback issue fixed with some keys like variations, cross_sell etc
* Tweaks: set_transient replaced with udpate_option function to save chunks.
= 6.3 - December 20 2021 =
* Feature: Now product status can be set for syncback (exporting to sheet) as pro feature
= 6.2 - December 2 2021 =
* Tweaks: Some links added on the admin side
= 6.1 - November 17 2021 =
* Connection issue fixed
= 6.0, October 13, 2021 =
* Feature: [Now sheet will connect is much easier with Google Service Account](https://www.youtube.com/watch?v=7J2H92wfOus)
= 5.2.1, October 13, 2021 =
* Bug fixed: Fetch products issue fixed when some fields have NULL values
= 5.2, October 13, 2021 =
* Fetch products issue fixed in PRO version
= 5.1, October 13, 2021 =
* Tweaks: Some error messages optimized
* Tweaks: [IDs not pull issue explain here](https://clients.najeebmedia.com/forums/topic/googlesync-latest-update-v5-stop-working-previous-version/)
= 5.0, September 18, 2021 =
* Feature: Removed un-used Google Libraries, now plugin files reduced from 17Mb to 1.5Mb
* Feature: Large chunks of data can be exported
* Feature: QuickConnect - No need to create Google credentials, all will be done via Najeebmedia Google App
= 4.0, August 22, 2021 =
* Features: Now product meta can be added as a separate column
* Features: Sync operation is optimized to handle more products in less time.
= 3.1 - August 4, 2021 =
* Bug fixed: [Tags were not adding from sheet to store, it is fixed](https://www.youtube.com/channel/UCEA9i5lXJMIo1u5aYbf2qgw)
= 3.0 - June 14, 2021 =
* Features: Major update to manage sync from the Google Sheet menu
* Features: Google App script used to send products from Google Sheet
= 2.6 - May 13, 2021 =
* Bug fixed: [Critical error fixed when google client is not set](https://wordpress.org/support/topic/critical-error-in-plugin-setting-page/)
= 2.5 - April 18, 2021 =
* Bug fixed: Error occurred in last version
= 2.4 - April 18, 2021 =
* Bug fixed: Images import issue fixed
= 2.3 - March 26, 2021 =
* Tweaks: Unnecessary files removed
* Bug fixed: Sync Back chunk size not linked, it is linked now.
= 2.2 - March 11, 2021 =
* Feature: Now the Orders & Customers data can be synced with Add-on
* Bug fixed: Metadata syncing issue fixed
= 2.1 - March 3, 2021 =
* Bug fixed: Variations syncing-back issue fixed
* Tweaks: Warnings removed, performance increased.
= 2.0 - February 23, 2021 =
* Features: Chunked syncing - best approach for larger data sets
* Features: Calling WC API internally, no need for WC API key and secret key
= 1.5 - February 10, 2021 =
* Tweaks: Optimized the sync speed
* Bug fixed: PRO: Variations images issue fixed when import/sync
= 1.4 - February 8, 2021 =
* Features: Response message added for sync-back
* Bug fixed: REST API endpoint warning issue fixed
* Bug fixed: PRO: Sync-back products/categories limits removed
= 1.3 - February 1, 2021 =
* Features: Now existing products can be added to Google Sheet
= 1.2 - December 11, 2020 =
* Features: Now images can be added via URL
= 1.1 - November 10, 2020 =
* Bug fixed: Product delete sync-back not working, fixed now
= 1.0.0 =
Initial Release

== Upgrade Notice ==
Nothing so far..