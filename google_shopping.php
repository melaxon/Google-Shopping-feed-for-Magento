<?php
ignore_user_abort();
error_reporting(E_ALL^E_NOTICE);
$_SVR = array();
//ini_set('memory_limit', '512M');
# CURRENTLY ONLY CONFIGURABLE AND SIMPLE PRODUCTS WILL BE INCLUDED INTO THE FEED

#
# 
# Options
# @url_param show_stores=off (on,off) 
# @url_param store= STORE_NAME - generate datafeed with products from specific store
# @url_param add_vat=off (on,off) 
# @url_param vat_value=24 (VAT_VALUE) 
# @url_param shipping=off (on,off) 
# @url_param add_tagging=on (on,off) 
# @url_param tagging_params=utm_source=shopmania&utm_medium=cpc&utm_campaign=direct_link (TAGGING_PARAMS) 
# @url_param description=on (on,off) 
# @url_param image=on (on,off) 
# @url_param on_stock=off (on,off) 
# @url_param url_path = on,off -  different url types
# @url_param currency= (CURRENCY_CODE) 
#
#  
##########################################################################################
$add_tagging = (@$_GET['add_tagging'] == "off") ? "off" : "on";
$tagging_params = (@$_GET['tagging_params'] != "") ? urldecode($_GET['tagging_params']) : "";

//$url_path - set to 'on' to get rid of ?___store= in multistore shop
$url_path = 'on'; //(@$_GET['url_path'] == "on") ? "on" : "off"; Whether to extract url path or product full url
$store = 'english';
$use_Short_description = false;
$localfile = 'google.xml';
$specialprice = 'on';
$show_mobile_link = 'on';
$availability_days = 5; // number of days for preordered items to get available
$use_cdata = 'on'; //whether to enclose values into <![CDATA[...]] or escape with htmlspecialchars when calling cdata() function
$force_mpn = 'on'; // Im mpn is empty then sku will be inserted
$weight_unit = 'kg';

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
'shipping'					=> '',
'price'						=> '',
'sale_price'				=> '',
'sale_price_effective_date'	=> '',
'link'						=> '',
'mobile_link'				=> '',
'image_link'				=> '',
'additional_image_link'		=> '',
'shipping_label'			=> '',
'multipack'					=> '',
'is_bundle'					=> '',
'adult'						=> '',
'adword_redirect'			=> '',
'custom_label_0'			=> '',
'custom_label_1'			=> '',
'custom_label_2'			=> '',
'custom_label_3'			=> '',
'custom_label_4'			=> '',
'excluded_destination'		=> '',
'expiration_date'			=> ''
);

$param_attributes = array(
// УСТАНОВИТЬ СООТВЕТСТВИЕ КОДОВ И НАЗВАНИЙ АТТРИБУТОВ

'dimensions' 			=> 'Dimensions',

);


// Set no time limit only if php is not running in Safe Mode
if (!ini_get("safe_mode")) {
    @set_time_limit(0);
	if (((int)substr(ini_get("memory_limit"), 0, -1)) < 1024) {
		ini_set("memory_limit", "1024M");
	}
}

// Force Server settings
if (@$_GET['force'] == "settings") {
	ini_set("memory_limit", "1024M");
	ini_set("max_execution_time", 0);
}



##### Include configuration files ################################################

$site_base_path = "./";

// Include configuration file
if(!file_exists($site_base_path . 'app/Mage.php')) {
	exit('<HTML><HEAD><TITLE>404 Not Found</TITLE></HEAD><BODY><H1>Not Found</H1>Please ensure that this file is in the root directory, or make sure the path to the directory where the configure.php file is located is defined corectly above in $site_base_path variable</BODY></HTML>');
}
else {
	require_once $site_base_path . 'app/Mage.php';
}

##### Set store  #########################################################

//  Get site basepath
$script_basepath = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

// Get store
#if (isset($_GET['store']) && ($_GET['store'] != "")) {
	$stores = Mage::app()->getStores();
	foreach ($stores as $i) {
		if ($i->getCode() == $store) {
			$storeId = $i->getId();
		}
	}
#}
// Get default store
if (!isset($storeId)) {
	// Get store ID use this to filter products
	$storeId = Mage::app()->getStore()->getId(); 
}

// Get list of stores
if ( (isset($_GET['show_stores']) && ($_GET['show_stores'] == 'on')) || (!isset($storeId)) || (@$_GET['mode'] == "debug")) {
	
	$stores = Mage::app()->getStores();
	foreach ($stores as $i) {
		print $i->getId() . " : " . $i->getCode() . " <a href=\"" . $script_basepath . "?store="  . $i->getCode() . "\" >" . $script_basepath . "?store=" . $i->getCode() . "</a> <br /> Current currency: " . Mage::app()->getStore($i->getId())->getCurrentCurrencyCode() . "<br /> Base currency: " . Mage::app()->getStore($i->getId())->getBaseCurrencyCode() . "<br />Website Id: " . Mage::app()->getStore($i->getId())->getWebsiteId() . "<br />";
		$prods = Mage::getModel('catalog/product')->getCollection()->addStoreFilter($i->getId());
		Mage::getSingleton('catalog/product_visibility')->addVisibleInSiteFilterToCollection($prods);
		print "Number of products: " . $prods->getSize(). "<br />";
		print Mage::app()->getStore($i)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . "<br />";
		print "--------------------------------------<br />";
	}
	if (isset($_GET['debug'])) {
		// Continue
	}
	else {
		exit;
	}
}

$shop_name = Mage::getStoreConfig('general/store_information/name', $storeId);
$shop_company = $shop_name;

$websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
$custGroup = Mage::getSingleton('customer/session')->getCustomerGroupId();

$shop_url = Mage::getStoreConfig('web/unsecure/base_url', $storeId);

// Get dsplay out of stock settings
//$show_out_of_stock_products = Mage::getStoreConfig('cataloginventory/options/show_out_of_stock');

######################################################################
	ini_set("memory_limit", "1024M");
	ini_set("max_execution_time", 0);



// Debuging
if (isset($_GET['debug'])) {
	print "memory limit " . ini_get("memory_limit") . "\n";
	print "max_execution_time " . ini_get("max_execution_time") . "\n\n";
}

##### Extract params from url ################################################

$show_image = (@$_GET['image'] == "off") ? "off" : "on";
$show_specialprice = (@$_GET['specialprice'] == "on") ? "on" : "off";
$on_stock_only = (@$_GET['on_stock'] == "on") ? "on" : "off";

$currency_code = (@$_GET['currency'] != "") ? $_GET['currency'] : "";
$use_localtime = (@$_GET['use_localtime'] == "on") ? "on" : "off";
$limit = (@$_GET['limit'] > 0) ? $_GET['limit'] : "";
$display_currency = (@$_GET['display_currency'] != "") ? $_GET['display_currency'] : "";


##### Extract products from database ###############################################

// Get current date
$datetime = date("Y-m-d G:i:s");

try{
	
	// Get shop currency
	$baseCurrencyCode = Mage::app()->getStore($storeId)->getBaseCurrencyCode();
	$currentCurrencyCode = Mage::app()->getStore($storeId)->getCurrentCurrencyCode();
	
	//$default_currency = Mage::getModel('directory/currency')->getConfigBaseCurrencies();
	//$datafeed_default_currency = $default_currency[0];
	
	$datafeed_default_currency = $baseCurrencyCode;
	
	if ($currency_code != "") {
		$currency_value_rate = Mage::getModel('directory/currency')->getCurrencyRates($datafeed_default_currency, $currency_code);
		$convert_rate = $currency_value_rate[$currency_code];
		$datafeed_currency = $currency_code;
	}
	else {
		$datafeed_currency = $datafeed_default_currency;
	}
	
	// Force displayed currency
	$datafeed_currency = ($display_currency != "") ? $display_currency : $datafeed_currency;
	
	$CAT = getCategories();
	
	$GROUPED = array();
	$grouped_prodIds = array();
	$bundle_prodIds = array();
	$conf_prodIds = array();
	$prodIds = array();

	// Get grouped products
	$grouped_products = Mage::getModel('catalog/product')->getCollection();
	$grouped_products->addAttributeToFilter('status', 1);//enabled
	$grouped_products->addAttributeToFilter('type_id', 'grouped');//catalog, search
	$grouped_products->addAttributeToSelect('entity_id');
	$grouped_products->setStoreId($storeId);
	$grouped_products->addStoreFilter($storeId); 
	$grouped_prodIds = $grouped_products->getAllIds();
		
	// Get bundle products
	$bundle_products = Mage::getModel('catalog/product')->getCollection();
	$bundle_products->addAttributeToFilter('status', 1);//enabled
	$bundle_products->addAttributeToFilter('type_id', 'bundle');//catalog, search
	$bundle_products->addAttributeToSelect('entity_id');
	$bundle_products->addStoreFilter($storeId);
	$bundle_prodIds = $bundle_products->getAllIds();
	
	// Get configurable products
	$conf_products = Mage::getModel('catalog/product')->getCollection();
	$conf_products->addAttributeToFilter('status', 1);//enabled
	$conf_products->addAttributeToFilter('type_id', 'configurable');//catalog, search
	$conf_products->addAttributeToSelect('entity_id');
	$conf_products->setStoreId($storeId);
	$conf_products->addStoreFilter($storeId);
	$conf_prodIds = $conf_products->getAllIds();


	// ******************************************************************************
	// Get the products
	$products = Mage::getModel('catalog/product')->getCollection();
	$products->addAttributeToFilter('status', 1);//enabled
//	$products->addAttributeToFilter('yml', 1);//enabled
	$products->addAttributeToFilter('visibility', 4); //catalog, search
	$products->addAttributeToFilter('type_id', 'simple'); //catalog, search
	//$products->addAttributeToSelect('*');
	$products->addAttributeToSelect('entity_id');
	$products->setStoreId($storeId);
	$products->addStoreFilter($storeId);
	$prodIds = $products->getAllIds();

	// Merge all products simpe, configurable, bundle, grouped
	$ALL_PRODS = array();
	$ALL_PRODS = array_merge($prodIds, $grouped_prodIds, $bundle_prodIds, $conf_prodIds, $GROUPED);

//	$storeUrl = new Mage_Core_Model_Url;
// 	$shop_url = Mage::app()->getStore($storeId)->isCurrentlySecure()
//             ? $storeUrl->getBaseUrl('', array('_secure' => true))
//             : $storeUrl->getBaseUrl('');
#	$storeData = new Mage_Core_Model_Store;
#	$shop_name = $storeData->getName();
	
	$tmp_file = $localfile . ".tmp";
	$fp=fopen($tmp_file, "w");
	header('Content-type: application/xml');	

$collection = '';

	$collection .=  <<<XXXX
<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"> 
XXXX
;

$collection .=  "<channel>\n";
$collection .=  "<title><![CDATA[$shop_name Data feed]]></title>\n";
$collection .=  "<link><![CDATA[$shop_url]]></link>\n";
$collection .=  "<description><![CDATA[$shop_company datafeed]]></description>\n";

	fwrite($fp, utf8_encode($collection));
	echo $collection;


$media_path = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product';


$offerID = array();
$offer_id = '';



foreach($ALL_PRODS as $productId) {

	$product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($productId);


	if($product->getTypeId() == 'configurable'){
			$associated_prods = $product->getTypeInstance()->getUsedProducts();
//			$associated_prods = $product->getTypeInstance()->getAssociatedProducts();
			
			$param = '';
			$group_id = $productId;

			
			foreach ($associated_prods as $assoc_product) {
				$offer_id = $assoc_product->getId();
                if(!empty($offerID[$offer_id])){
                    $offer_id = $offer_id . $group_id;
                    if(!empty($offerID[$offer_id])) {
                        continue;
                    }
                    else {$offerID[$offer_id] = true;}
                }
                else {$offerID[$offer_id] = true;}
                
		    	if ($assoc_product->isSaleable()) {


					$a_product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($assoc_product->getId());
					$itemImages = $a_product->getMediaGalleryImages();
					$gallery = '';

					$firstImage = '';
					if ($show_image != "off"){
						if (count($itemImages) > 0) {
							$i = 0;
							foreach($itemImages as $itemImage){
								if($itemImage != ''){
									if ($i == 0) {$firstImage = $media_path . $itemImage->getFile();}
									$gallery .= "<picture>" . $media_path . $itemImage->getFile() . "</picture>\n";
									$i++;
								}
							}
						}
						elseif($a_product->getImage() != 'no_selection' && $a_product->getImage()){
							$firstImage = $media_path . $a_product->getImage();
							$gallery .= "<picture>" .$media_path . $a_product->getImage()  . "</picture>\n";
						}
					}
					$collection = 	"<item>\n";

		        	
		        	
		        	


					
//<!-- Product Variants -->
		        	$collection .= 	"<g:item_group_id><![CDATA[" . $group_id . "]]></g:item_group_id>\n";
//<!-- AdWords attributes -->
		        	$collection .= 	"</item>";


	fwrite($fp, $collection);
	echo $collection;
	$collection = '';				
					
				}
			}
	} elseif ($product->getTypeId() == 'simple'){
		

		if ($product->isSaleable()){
		
			$offer_id = $product->getId();
            if(!empty($offerID[$offer_id])) {
                        continue;
            }
            else {$offerID[$offer_id] = true;}
            
            
			$itemImages = Mage::getModel('catalog/product')->load($product->getId())->getMediaGalleryImages();
			$gallery = '';
			$firstImage = '';
			$additionalImages = array();
			if ($show_image != "off"){		
				if (count($itemImages) > 0) {
						$i = 0;
						foreach ($itemImages as $itemImage) {
							if($i == 0) {
								$firstImage = $media_path . $itemImage->getFile();
							}
							else {
								$additionalImages[] = $media_path . $itemImage->getFile();
							}
							$i++;
						}
				}
				elseif($product->getImage() != 'no_selection' && $product->getImage()){
					$firstImage = $media_path . $product->getImage();
				}
			}

            
            
            
            
            
			$value = '';
			$collection = 	"<item>\n";
//id
			$method = getMethod($g_params_mapping, 'id');
			if(!empty($method)){$value = $product->$method();} else {$value = '';}
		    $collection .= 	"<g:id>" . cdata($value) . "</g:id>\n";
//title
		    $method = getMethod($g_params_mapping, 'title');
		    if(!empty($method)){$value = $product->$method();} else {$value = '';}
		    $collection .= 	"<title>" . cdata($value) . "</title>\n";
//description		    
			$this_value = '';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['description']);
			if(!empty($this_attrib)) $this_value = cdata(trim($this_attrib->getFrontend()->getValue($product)));
			$collection .= 	"<description>$this_value</description>\n";
		    
		    
//google_product_category		    		    
			$this_value = '';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['google_product_category']);
			if(!empty($this_attrib)) $this_value = cdata(trim($this_attrib->getFrontend()->getValue($product)));
			$collection .= 	"<g:google_product_category>$this_value</g:google_product_category>\n";
		    
		     	
//product_type
			$method = getMethod($g_params_mapping, 'product_type');
			$value = '';
			if(!empty($method)){
				$value = trim($product->$method());
			}
			if(!empty($value)) {$product_type = cdata($value);}
			else {$product_type = getProductType($product);}
			 $collection .=  "<g:product_type>$product_type</g:product_type>\n";
   	
//link
		    $collection .= 	"<link>" . cdata(trim(get_product_url($product, $url_path), '?')) . "</link>\n";   	
//mobile_link
			if($show_mobile_link == 'on'){
				$collection .= 	"<g:mobile_link>" . cdata(trim(get_product_url($product, $url_path), '?')) . "</g:mobile_link>\n";
			}
//image_link
			if(!empty($firstImage)) {
				$collection .= 	"<g:image_link>" . cdata($firstImage) . "</g:image_link>\n";
			}
//additional_image_link
			if(!empty($additionalImages)) {
				foreach ($additionalImages as $img) {
					$collection .= 	"<g:additional_image_link>" . cdata($img) . "</g:additional_image_link>\n";
				}
			}
//condition
			$this_value = '';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['condition']);
			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
			if($this_value !='' && $this_value != 'No')  {
				$collection .= 	"<g:condition>$this_value</g:condition>\n";  
			}

		    
//availability
			$this_value = '';
			$availability = '';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['availability']);
			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
			if($this_value !='' && $this_value != 'No')  {
				$collection .= 	"<g:availability>$this_value</g:availability>\n";
				$availability = $this_value;
			}
		    

//availability_date
			if($availability == 'preorder') {
				$this_value = '';
				$this_attrib = $product->getResource()->getAttribute($g_params_mapping['availability_date']);
				if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
				$this_value = trim($this_value);
				if($this_value != '')  {
					//$ndays = $this_value*86400;
					$collection .= 	"<g:availability_date>" . date("Y-m-d\TG:i", time()+trim($this_value)*86400) . "</g:availability_date>\n";  
				}
			}
//price
//sale_price
//sale_price_effective_date

			$prod_prices = getProductPrice($product);
			$prod_price = $prod_prices[0];
			$old_price = $prod_prices[1];
			list($specialFrom, $sec) = explode(' ', $product->getData('special_from_date'));
			list($specialTo, $sec) = explode(' ', $product->getData('special_to_date'));
			if($old_price > $prod_price && ($specialTo > date("Y-m-d") || empty($specialTo))) {
				$collection .= 	"<g:price>$old_price $datafeed_currency</g:price>\n";
				$collection .= 	"<g:sale_price>$prod_price $datafeed_currency</g:sale_price>\n";
				if(!empty($specialFrom) && !empty($specialTo) && $specialTo > $specialFrom) {
					$specialTo .= $specialTo . 'T00:00';
					$specialFrom .= $specialFrom . 'T00:00';
					$collection .= 	"<g:sale_price_effective_date>$specialFrom/$specialTo</g:sale_price_effective_date>\n";
				}
				
			}
			else {
				$collection .= 	"<g:price>$prod_price $datafeed_currency</g:price>\n";
			}
//gtin
//mpn
//			
			$this_value = '';
			$gtin = false;
			$identifier_exists = 'FALSE';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['gtin']);
			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
			if($this_value !='')  {
				$collection .= 	"<g:gtin>" . cdata($this_value) . "</g:gtin>\n";
				$gtin = true;
				$identifier_exists = 'TRUE';
			}

			$this_value = '';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['mpn']);
			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
			if($this_value !='')  {
				$collection .= 	"<g:mpn>" . cdata($this_value) . "</g:mpn>\n";  
			} 
			elseif ($force_mpn == 'on') {
				$collection .= 	"<g:mpn>" . $product->getSku() . "</g:mpn>\n";
			}
//brand
			$this_value = '';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['brand']);
			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
			if($this_value !='' && $this_value !='No')  {
				$collection .= 	"<g:brand>" . cdata($this_value) . "</g:brand>\n";  
			}
//identifier_exists
			$this_value = '';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['identifier_exists']);
			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
			if($this_value !='' && $this_value !='No')  {
				$collection .= 	"<g:identifier_exists>$this_value</g:identifier_exists>\n";  
			}
			else {
				$collection .= 	"<g:identifier_exists>$identifier_exists</g:identifier_exists>\n";  
			}
//item_group_id
//			$collection .= 	"<g:item_group_id>$productId</g:item_group_id>\n";  
			
//color
			$this_value = '';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['color']);
			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
			if($this_value !='')  {
				$collection .= 	"<g:color>" . cdata($this_value) . "</g:color>\n";  
			}
//gender
			$this_value = '';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['gender']);
			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
			if($this_value !='' && $this_value !='No')  {
				$collection .= 	"<g:gender>" . cdata($this_value) . "</g:gender>\n";  
			}
//age_group
			$this_value = '';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['age_group']);
			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
			if($this_value !='' && $this_value !='No')  {
				$collection .= 	"<g:age_group>" . cdata($this_value) . "</g:age_group>\n";  
			}
//material
			$this_value = '';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['material']);
			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
			if($this_value !='' && $this_value !='No')  {
				$collection .= 	"<g:material>" . cdata($this_value) . "</g:material>\n";  
			}
//pattern
			$this_value = '';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['pattern']);
			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
			if($this_value !='' && $this_value !='No')  {
				$collection .= 	"<g:pattern>" . cdata($this_value) . "</g:pattern>\n";  
			}
//size
			$this_value = '';
			$size_exists = false;
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['size']);
			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
			if($this_value !='' && $this_value !='No')  {
				$collection .= 	"<g:size>" . cdata($this_value) . "</g:size>\n"; 
				$size_exists = true;
			}

			if($size_exists === true){
//size_type				
				$this_value = '';
				$this_attrib = $product->getResource()->getAttribute($g_params_mapping['size_type']);
				if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
				if($this_value !='' && $this_value !='No')  {
					$collection .= 	"<g:size_type>$this_value</g:size_type>\n";
				}
				else{
					$collection .= 	"<g:size_system>regular</g:size_system>\n";
				}
//size_system				
				$this_value = '';
				$this_attrib = $product->getResource()->getAttribute($g_params_mapping['size_system']);
				if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
				if($this_value !='' && $this_value !='No')  {
					$collection .= 	"<g:size_system>$this_value</g:size_system>\n";
				}
			}

// 'shipping'					=> '',
// 'shipping_weight'			=> 'weight',
			$this_value = '';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['shipping_weight']);
			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
			if($this_value !='')  {
				$this_value = number_format($this_value, 2, '.', '');
				$collection .= 	"<g:shipping_weight>$this_value $weight_unit</g:shipping_weight>\n"; 
			}
//shipping_label
			$this_value = '';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['shipping_label']);
			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
			if($this_value !='')  {
				$collection .= 	"<g:shipping_label>" . cdata($this_value) . "</g:shipping_label>\n"; 
			}
//multipack
			$this_value = '';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['multipack']);
			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
			if($this_value !='')  {
				$collection .= 	"<g:multipack>$this_value</g:multipack>\n"; 
			}
//is_bundle
// 			$this_value = '';
// 			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['is_bundle']);
// 			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
// 			if($this_value !='')  {
// 				$collection .= 	"<g:is_bundle>$this_value</g:is_bundle>\n"; 
// 			}
//adult
			$this_value = '';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['adult']);
			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
			if($this_value !='')  {
				$collection .= 	"<g:adult>$this_value</g:adult>\n"; 
			}
//adword_redirect
			$this_value = '';
			$this_attrib = $product->getResource()->getAttribute($g_params_mapping['adword_redirect']);
			if(!empty($this_attrib)) $this_value = trim($this_attrib->getFrontend()->getValue($product));
			if($this_value !='')  {
				$collection .= 	"<g:adword_redirect>" . cdata($this_value) . "</g:adword_redirect>\n"; 
			}
		
// 'custom_label_0'			=> '',
// 'custom_label_1'			=> '',
// 'custom_label_2'			=> '',
// 'custom_label_3'			=> '',
// 'custom_label_4'			=> '',
// 'excluded_destination'		=> '',
// 'expiration_date'			=> ''
//			$collection .= 	"<isSaleable>" . $product->getIsInStock() . "</isSaleable>\n";
			$collection .= 	"</item>\n\n";
	fwrite($fp, $collection);
	echo $collection;
                    
// 
// 			$param = '';
// 			foreach ($param_attributes as $attribute_id=>$attribute_name){
// 					Mage::app()->setCurrentStore('russian');
// 					$attribute = $product->getResource()->getAttribute($attribute_id);
// 					if ($attribute) {
// 						$attribute_value = $attribute->getFrontend()->getValue($product);
// 						if ($attribute_value and $attribute_value != 'No'){
// 							$param .= "<param name=\"$attribute_name\">$attribute_value</param>\n";
// 						}
// 					}
// 			}
// 

	$collection = '';
		}
	}

	
	
	//The below code will only provide associated products that are saleable as shown above in the if statment.
//	$product->setAllowProducts($assoc_products);
//	print_r($product->getData('allow_products'));
//	print_r($assoc_product);
//	exit;

 //   $collection = "<pr_type>" . $product->getTypeId() . "</pr_type>";
//	if ($product->isSaleable() && $product->getTypeId() == 'configurable') break;
}
	
$collection = "\n\t</channel>\n</rss>\n";	

	fwrite($fp, $collection);
	echo $collection;
	fclose($fp);
	rename($tmp_file, $localfile);

	// Array to check if product is already send
	$already_sent = array();
	$prod_count = 0;
	
	##### Print product data ####################################################
	
	foreach($ALL_PRODS as $productId) {

		// If we've sent this one, skip the rest - this is to ensure that we do not get duplicate products
		if (@$already_sent[$productId] == 1) continue;

		$PRODUCT = array();

		if ($PRODUCT['show_product'] == 1) {
		
			$prod_count ++;
		
			// Limit displayed products
			if ($limit > 0 && $prod_count >= $limit) {
				exit;
			}	
				
		}	
		$already_sent[$productId] = 1;
	}
	
	######################################################################
		
}

catch(Exception $e){
	die($e->getMessage());
}


##### Functions ############################################################


// Function to return the Product URL based on your product ID
function get_product_url($product, $url_path){
	global $add_tagging, $tagging_params;
	if ($url_path == "on") {
		$product_url = $product->getUrlPath();
	}
	else {
		$product_url = $product->getProductUrl();
	}
	$current_file_name = basename($_SERVER['REQUEST_URI']);
	$product_url = str_replace($current_file_name, "index.php", $product_url);
	$product_url = str_replace("datafeed_shopmania_magento", "index", $product_url);
	
	// Eliminate id session 
	$pos_SID = strpos( $product_url, "?SID");
	if ($pos_SID) {
		$product_url = substr($product_url, 0, $pos_SID);
	}
	if ($url_path == "on") {$product_url = "http://" . $_SERVER['SERVER_NAME'] . "/" . $product_url;}

	
	// Add GA Tagging parameters to url
	if ($add_tagging == "on") {
		$and_param = (preg_match("/\?/", $product_url)) ? "&" : "?";
		$product_url = $product_url . $and_param . $tagging_params;
	}
	return $product_url;
}

function smfeed_replace_not_in_tags($find_str, $replace_str, $string) {
	
	$find = array($find_str);
	$replace = array($replace_str);	
	preg_match_all('#[^>]+(?=<)|[^>]+$#', $string, $matches, PREG_SET_ORDER);	
	foreach ($matches as $val) {	
		if (trim($val[0]) != "") {
			$string = str_replace($val[0], str_replace($find, $replace, $val[0]), $string);
		}
	}	
	return $string;
}

function getProductPrice($product) {
	
	global $Mage, $specialprice, $storeId, $websiteId, $custGroup, $use_localtime;
	$productId = $product->getId();
	$type = $product->getTypeId();
	
	$_taxHelper  = Mage::helper('tax');
	$local_time = ($use_localtime == "on") ? time() : Mage::app()->getLocale()->storeTimeStamp($storeId);

		// Get min price from grouped products
	if ($type == "grouped") {
		$aProductIds = $product->getTypeInstance()->getChildrenIds($product->getId());

		$prices = array();
		
		foreach ($aProductIds as $ids) {
			foreach ($ids as $id) {
			
				$aProduct = Mage::getModel('catalog/product')->load($id);
				if ( $aProduct->getSpecialPrice() && ( (@$specialprice == "on") || ( (date("Y-m-d G:i:s") > $aProduct->getSpecialFromDate() || !$aProduct->getSpecialFromDate()) &&  (date("Y-m-d G:i:s") < $aProduct->getSpecialToDate() || !$aProduct->getSpecialToDate()) ) ) ){
					$finalPrice = $aProduct->getSpecialPrice();
				} 
				else {
					$finalPrice = $aProduct->getPrice();
				}
				// Get ruleprice
				$rulePrice = Mage::getResourceModel('catalogrule/rule')->getRulePrice($local_time, $websiteId, $custGroup, $id);
		
				if ($rulePrice !== null && $rulePrice !== false) {
					$finalPrice = min($finalPrice, $rulePrice);
				}
				// Build prices array
				$prices[] = $finalPrice;
			}
		}
		// Get min value of all prices
		$finalPrice = min($prices);
	}
	// Get product price
	else {
		if ( $product->getSpecialPrice() && ( ($specialprice == "on") || ( (date("Y-m-d G:i:s") >= $product->getSpecialFromDate() || !$product->getSpecialFromDate()) &&  (date("Y-m-d G:i:s") <= $product->getSpecialToDate() || !$product->getSpecialToDate()) ) ) ){
			$finalPrice = $product->getSpecialPrice();
		} 
		else {
			$finalPrice = $product->getPrice();
		}
	
		// Get ruleprice
		$rulePrice = Mage::getResourceModel('catalogrule/rule')->getRulePrice($local_time, $websiteId, $custGroup, $productId);
		//$rulePrice
		if ($rulePrice !== null && $rulePrice !== false) {
			$finalPrice = min($finalPrice, $rulePrice);
		}
	}
	$oldPrice = $product->getPrice();
//return array($finalPrice, $oldPrice);
    $oldPrice = number_format($oldPrice, 2, '.', '');
    $finalPrice = number_format($finalPrice, 2, '.', '');
    
    if($oldPrice > $finalPrice) {return array($finalPrice, $oldPrice);}
	else {return array($finalPrice);}
}




function get_category_descriptions(){

	global $Mage, $storeId;
//------------------
	$collection = Mage::getModel('catalog/category')->getCollection()->addAttributeToSelect("name");
	
	$collection->setStoreId($storeId);
	
	$catIds = $collection->getAllIds();

	$cat = Mage::getModel('catalog/category');
	$cat->setStoreId($storeId);
	
	$max_level = 0;


	foreach ($catIds as $catId) {
		$cat_single = $cat->load($catId);
		$level = $cat_single->getLevel();
		if ($level > $max_level) {
			$max_level = $level;
		}
		
		$parent = $cat_single->getParentId();
		if ($parent != 1 && $catId > 1){
			$parentDescription = '';
			if ($shownParent != $parent){$parentDescription = "parentId=\"$parent\"";}
			$desc .=  "<category id=\"$catId\" $parentDescription>" . $cat_single->getName() . "</category>\n";
		}
		else{$shownParent = $catId;}
	}
	return $desc;

}


// Get all categories whith breadcrumbs
function getCategories(){

	global $Mage, $storeId;

	$collection = Mage::getModel('catalog/category')->getCollection()->addAttributeToSelect("name");
	$collection->setStoreId($storeId);
	$catIds = $collection->getAllIds();

	$cat = Mage::getModel('catalog/category');
	//$cat->setStoreId($storeId);
	$max_level = 0;

	foreach ($catIds as $catId) {
		$cat_single = $cat->load($catId);
		$level = $cat_single->getLevel();
		if ($level > $max_level) {
			$max_level = $level;
		}

		$CAT_TMP[$level][$catId]['name'] = $cat_single->getName();
		$CAT_TMP[$level][$catId]['childrens'] = $cat_single->getChildren();
	}

	$CAT = array();
	
	for ($k = 0; $k <= $max_level; $k++) {
		if (is_array($CAT_TMP[$k])) {
			foreach ($CAT_TMP[$k] as $i=>$v) {
				if (isset($CAT[$i]['name']) && ($CAT[$i]['name'] != "")) {
					$CAT[$i]['name'] .= " > " . $v['name'];
					$CAT[$i]['level'] = $k;
				}
				else {
					$CAT[$i]['name'] = $v['name'];
					$CAT[$i]['level'] = $k;
				}

				if (($v['name'] != "") && ($v['childrens'] != "")) {
					if (strpos($v['childrens'], ",")) {
						$children_ids = explode(",", $v['childrens']);
						foreach ($children_ids as $children) {
							if (isset($CAT[$children]['name']) && ($CAT[$children]['name'] != "")) {
								$CAT[$children]['name'] = $CAT[$i]['name'];
							}
							else {
								$CAT[$children]['name'] = $CAT[$i]['name'];
							}
						}
					}
					else {
						if (isset($CAT[$v['childrens']]['name']) && ($CAT[$v['childrens']]['name'] != "")) {
							$CAT[$v['childrens']]['name'] = $CAT[$i]['name'];
						}
						else {
							$CAT[$v['childrens']]['name'] = $CAT[$i]['name'];
						}
					}
				}
			}
		}
	}
	unset($collection);
	unset($CAT_TMP);
	return $CAT;
}
function getProductType ($product) {
	$pathArray = array();
	$collection1 = $product->getCategoryCollection()
		->setStoreId(Mage::app()->getStore()->getId())
		->addAttributeToSelect('path')
		->addAttributeToSelect('is_active');
		

	foreach($collection1 as $cat1){            
		$pathIds = explode('/', $cat1->getPath());            
		$collection = Mage::getModel('catalog/category')->getCollection()
			->setStoreId(Mage::app()->getStore()->getId())
			->addAttributeToSelect('name')
			->addAttributeToSelect('is_active')
			->addFieldToFilter('entity_id', array('in' => $pathIds));

		$pahtByName = array();
		foreach($collection as $cat){                
			if ($cat->getId() > 1) {$pahtByName[] = $cat->getName();}
		}
		
		$pathArray[] = implode('/', $pahtByName);

	}
	
	return cdata($pathArray[0]);
}
function getMethod($g_params_mapping, $element){
	if(isset($g_params_mapping[$element]) && !empty($g_params_mapping[$element])) {
		$el_array = explode('_', $g_params_mapping[$element]);
		$method = '';
		foreach($el_array as $el) {
			$method .= ucfirst($el);
		}
		return 'get' . $method;
	}
	else {return false;}
}

function cdata($value) {
	global $use_cdata;
	if ($use_cdata == 'on') {return "<![CDATA[" . $value . "]]>";}
	else {return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');}
}



######################################################################

exit;

?>