=== Bulk Product Sync - Bulk Product Editor for WooCommerce with Google Sheets™ ===
Contributors: nmedia
Tags: bulk products, bulk stock manage, bulk price editor, woocommerce products, woocommerce stock, stock update
Donate link: http://www.najeebmedia.com/donate
Requires at least: 4.3
Tested up to: 6.6.2
Requires PHP: 5.6
Stable tag: 9.3
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Bulk Product Sync is a WooCommerce plugin to manage products with Google Sheets™ in bulk.

== Description ==
Bulk Product Sync with Google Sheets™ is a creative, quick, and easy way to use Google Sheets™ to bulk update or create products and categories for your WooCommerce store. 
To add new products to WooCommerce or even to make a single adjustment, you must go through a long-form (product edit page). Your time is wasted while you wait for login, website updates, and several clicks.
Use Google Sheets™ to add products and categories, and it only takes one click to add thousands of products to your store. Please watch this video instruction to learn more about the features and how to use it.

= How It Works? =
[youtube https://youtu.be/aCjnnOXXiP8?si=6QNHWX9QUHFbX7iv]

****
= Live Demo =
* Check all features in [Live Demo](https://najeebmedia.com/bps-demo)
* This demo has already installed **BPS** Free and Pro version.
* When demo is ready, please follow the instructions as [explained here](https://youtu.be/aCjnnOXXiP8?si=6QNHWX9QUHFbX7iv)

= Features =
* One Click Import
* Sync All Your Products
* Sync All Your Categories
* Add/remove product fields
* Sync from Store to Google Sheet™
* Build using the latest Google App Script API

= PRO Features =
* Variable Products Supported
* Auto-Sync [hourly, twice daily, daily]
* Logging Auto Sync
* Export Existing Products into Google Sheet™
* Export Existing Categories into Google Sheet™
* Export Existing Variations into Google Sheet™
* Metadata columns

[Learn More Bulk Product Sync PRO](https://najeebmedia.com/wordpress-plugin/woocommerce-google-sync/)

== Use Cases ==
**Opening a new store** *Add all of your products to the Google sheet, select `Sync Products` and bask in the joy.*
**Managing an existing store** *To manage any updates or new products, export all of your products from your store to Google Sheets.™*
**Managing the stock** If the present solution's stock management is driving you crazy, use Bulk Product Sync to add `manage_stock` and `quantity` columns to the sheet, and you'll be free of the problem.
**Managing the price** *Price changes were frequent due to the economy's instability; however, Bulk Product Sync will now adjust product prices in bulk for your store.*
**Variable products** *In WooCommerce, it is difficult to create variations and variable products; however, Bulk Product Sync has made this process quite simple and quick.*
**Much more ...** *As per your needs*

== Installation ==
1. Download the plugin and activate it
2. Go to WooCommerce > Settings > Google Products
3. Enter your Google Credentials
4. Enter WooCommerce API/Secret Keys

== Frequently Asked Questions ==
= What is the difference between Pro and Free versions? =
The only import option in the free version is from a Google Sheet™ to the store, and some fields, such as images, and variable products, cannot be imported. As mentioned above in the PRO features list, you can export (from store to Google Sheet™) and do much more when using the Pro version.
= Can I fetch my existing products/categories? =
Yes, in Pro version
= Should I need to connect with my own Google Account? =
Yes
= Can I see live Demo? =
* Check all features in [Live Demo](https://najeebmedia.com/bps-demo)
* This demo has already installed **BPS** Free and Pro version.
* Make sure you follow the instructions as [explained here](https://youtu.be/aCjnnOXXiP8?si=6QNHWX9QUHFbX7iv)
= Can we use formulas in Google Sheet for e.g. prices, stock?
Yes


== Screenshots ==
1. Import products from Google Sheet™ to store
2. Showing products sync status in admin
3. Google Sync™ columns
4. Settings page

== Changelog ==
= 9.3 April 26, 2025 =
Bug fixed: [Images by ids were syncing, it is fixed now](https://clients.najeebmedia.com/forums/topic/error-capturing-remote-image/)
= 9.2 April 26, 2025 =
* Bug fixed: CORS issue fixed while synced.
= 9.1 April 25, 2025 =
* Bug fixed: Sync issue fixed caused by last udpate
= 9.0 April 23, 2025 =
* Feature: Improved error handling for large POST body uploads during product sync
* Feature: Added validation to all REST endpoints with fallback for empty/missing params
* Feature: Optimized product/category linking using `prepare()` safely (PHP 7.0+ support)
* Bug Fix: Fixed positional argument unpacking in `wpdb->prepare` when deleting meta
* Tweaks: Refined plugin admin UI for better clarity during setup
= 8.6 November 19, 2024 =
* Feature: [Brands is now core part of WooCommerce, thus BPS now can handle brands just like categories](https://clients.najeebmedia.com/forums/topic/brands-taxonomy-is-not-syncing/)
* Feature: Serialized meta now can be fetched and synced
= 8.5 October 28, 2024 =
* Feature: [Some suggested improvements](https://clients.najeebmedia.com/forums/topic/some-improvements-to-the-pro-version/)
* Feature: Taxonomies now can be added/updated via Google sheet.
= 8.4 October 5, 2024 =
* Bug fixed: Dimensions fields were not being fetched for variations, now it is fixed.
= 8.3 April 13, 2024 =
* Feature: Compatible with WordPress version 6.5.2
= 8.2 November 13, 2023 =
* Tweaks: AuthCode added in options
= 8.1 November 5, 2023 =
* Feature: Product attributes now can be fetched
= 8.0 October 30, 2023 =
* Feature: Setup wizard now is super easy in less than 10 secconds.
= 7.15 October 18, 2023 =
* Bug fixed: Fetch failed when pro version deactivated, not it has message
= 7.14 October 16, 2023 =
* Bug fixed: AutoSync issue fixed, stop sending Fetch request on AutoSync
= 7.13 October 10, 2023 =
* Bug fixed: AutoFetch not working now it is fixed.
= 7.12.2 September 20, 2023 =
* Tweaks: Check with latest version of WordPress
= 7.12.1 August 24, 2023 =
* Tweaks: Demo link updated & some code optimized.
* Info: No Major bug found
= 7.12 July 27, 2023 =
* Bug fixed: When type column is not given it show warning
= 7.11 July 20, 2023 =
* Bug fixed: categories sync issue fixed
= 7.10.1 June 25, 2023 =
* Feature: Sync operation is more optimized for larger set of data
* Feature: Fetch now has option to Reset/Refresh.
= 7.8 June 20, 2023 =
* Bug fixed: Tags by names were not syncing, now these are fixed
= 7.7 June 17, 2023 =
* Bug fixed: Fetch error fixed when used few columns like cross_sell_ids, upsell_ids etc
= 7.6 June 6, 2023 =
* Feature: Settings page added inside plugin for connection status
* Feature: Pro activate process made easy
= 7.5.3 June 4, 2023 =
* Bug fixed: Debuggin removed which cause error in fetching.
= 7.5.2 June 3, 2023 =
* Bug fixed: Larger chunks for product issue is fixed.
= 7.5 May 24, 2023 =
* Bug fixed: Due to PHP version code is updated to work with all versions.
= 7.4 May 10, 2023 =
* Bug fixed: Sync operation slow issue fixed.
= 7.3.1 May 8, 2023 =
* Bug fixed: During sync operation old installations were stucked.
= 7.3 May 6, 2023 =
* Bug fixed: [Due to last update, sync was not working. It is fixed now](https://clients.najeebmedia.com/forums/topic/synchronization-never-starts/#post-151067)
* Bug fixed: [Error on AutFetch enable fixed](https://clients.najeebmedia.com/forums/topic/google-sync-button-in-woocommerce-settings-not-displaying/)
= 7.2 May 2, 2023 =
* Feature: [AutoFetch is optimized](https://najeebmedia.com/blog/how-to-enable-autofetch-in-bulk-product-sync-for-real-time-store-updates-in-your-google-sheet/)
* Feature: [AutoSync feature is added](https://najeebmedia.com/blog/how-to-enable-auto-sync-in-bulk-product-sync-for-real-time-store-updates-in-your-google-sheet/)
= 7.1 April 20, 2023 =
* Feature: [AutoFetch is optimized with new way](https://najeebmedia.com/blog/how-to-enable-autofetch-in-bulk-product-sync-for-real-time-store-updates-in-your-google-sheet/)
= 7.0 April 5, 2023 =
* Feature: [Bulk Product Sync is now released as Google Addon](https://workspace.google.com/u/0/marketplace/app/bulk_product_sync_with_google_sheets/267586530797)
* Feature: Removed Time-out issues for larger chunks of products
* Feature: Manage columns names
* Feature: Product attributes generator for variable and variations in one click
= 6.15 March 4, 2023 =
* Feature: Googleclient libraries updated
= 6.14 December 1, 2022 =
* Bug fixed:[Data validations applied as per standards](https://developer.wordpress.org/apis/security/escaping/)
* Due to some branding issues name changed from GoogleSync to Bulk Product Sync
= 6.13 December 1, 2022 =
* Bug fixed: Grouped products were not being synced and fetched, now it is fixed.
= 6.12 September 1, 2022 =
* Bug fixed: HTML entities were not decoding in short_description and title
= 6.11 August 22, 2022 =
* Bug fixed: All categories were being pulled, now it is fixed now
* Feature: Categories and Tags now can be set with Names also.
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
* Features: Google App script used to send products from Google Sheet™
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
* Features: Now existing products can be added to Google Sheet™
= 1.2 - December 11, 2020 =
* Features: Now images can be added via URL
= 1.1 - November 10, 2020 =
* Bug fixed: Product delete sync-back not working, fixed now
= 1.0.0 =
Initial Release

== Upgrade Notice ==
Nothing so far..