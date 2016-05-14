# Google-Shopping-feed-for-Magento
Google Shopping feed for Magento

Very simple xml feed generator for Google Shopping
It currently works in mltistore magento but with simple products only but you can "easily" extend it to operate with configurables, bundles, etc, with little knowledge in php

Just upload it into magento root directory and point your browser on it. For example:
http://www.yourdomain.com/google_shopping.php
It will generate an xml file in the same location: google.xml (make sure your directory permissions (CMOD) to be appropriate)
Then you can submit this file to google shopping: http://www.yourdomain.com/google.xml

You can setup the crontab to hit php file regularly to update your xml feed

Settings:
1st step

Open the file in text editor and modify the settings:
<pre>

$url_path = 'on'; //(@$_GET['url_path'] == "on") ? "on" : "off"; set to 'on' to get rid of ?___store= in multistore shop
$store = 'english'; // your ID. Ignore it if you have only one store
$use_Short_description = false;// use full (false) or short (true) description
$localfile = 'google.xml'; // name 
$specialprice = 'on'; // whether or not special price to be used in the feed
$show_mobile_link = 'on'; // include a ling for mobile version of your site
$availability_days = 5; // number of days for preordered items to get available
$use_cdata = 'on'; //whether to enclose values into <![CDATA[...]] or escape with htmlspecialchars when calling cdata() function
$force_mpn = 'on'; // If mpn is empty then sku will be inserted
$weight_unit = 'kg';
</pre>
2nd step - fields mapping

on the left side of this code change the attribute IDs as per your magento settings:
<pre>
$g_params_mapping = array(
'id' 						=> 'sku',
'title' 					=> 'name',
'description' 				=> 'description',
'google_product_category' 	=> 'google_category',
'product_type' 				=> 'google_product_type',
'condition'					=> 'condition',
'availability'				=> 'availability',
'availability_date'			=> 'availability_date',
'gtin'						=> 'gtin',
'mpn'						=> 'mpn',
'brand'						=> 'manufacturer',
'identifier_exists'			=> 'identifier_exists',
'color'						=> 'color',
'gender'					=> 'gender',
'age_group'					=> 'age',
'material'					=> 'material',
'pattern'					=> 'pattern',
'size'						=> 'size',
'size_type'					=> 'size_type',
'size_system'				=> 'size_system',
'shipping_weight'			=> 'weight',
'item_group_id'				=> '',
.....
</pre>
You're done!



