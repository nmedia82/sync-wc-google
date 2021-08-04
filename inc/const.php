<?php
/**
 * Constants Here
 * 
 **/
 
// Sync column index
define('WCGS_SYNC_COLUMN_INDEX', 1);

function wcgs_get_header_column_by_index($index) {
    
    $column_names = array('A', 'B', 'C',
                          'D', 'E', 'F',
                          'G', 'H', 'I', 'J',
                          'K', 'L','M','N','O','P',
                          'Q','R','S','T','U','V','W',
                          'X','Y','Z','AA','AB','AC','AD','AE',
                          'AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS');
                    
    // wcgs_pa($column_names[$index]);
    return isset($column_names[$index]) ? apply_filters('wcgs_product_header_column', $column_names[$index], $index) : null;
}

function wcgs_header_letter_to_index($letter) {
    
    $column_names = array('', 'A', 'B', 'C',
                          'D', 'E', 'F',
                          'G', 'H', 'I', 'J',
                          'K', 'L','M','N','O','P',
                          'Q','R','S','T','U','V','W',
                          'X','Y','Z','AA','AB','AC','AD','AE',
                          'AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS');
                    
    $key = array_search($letter, $column_names);
    return $key !== false ? apply_filters('wcgs_header_letter_to_index', $key, $letter) : '';
}

// Syncing Array
function wcgs_sync_array(){
    
    $sync_array = ['products'=>__("Products","wcgs"), 'categories'=>__("Categories","wcgs")];
    return apply_filters('wcgs_sync_array', $sync_array);
}

// All datatypes
function wcgs_datatypes() {
    
    $datatypes = [  'products'  => ['id'=>'int','sync'=>'string','meta_data'=>'array','name'=>'string','slug'=>'string','permalink'=>'string',
                                    'date_created'=>'date-time','date_created_gmt'=>'date-time','date_modified'=>'date-time','status'=>'string',
                                    'date_modified_gmt'=>'date-time','type'=>'string','shipping_class_id'=>'int','catalog_visibility'=>'string',
                                    'sold_individually'=>'boolean','short_description'=>'string','price'=>'string','download_expiry'=>'int',
                                    'date_on_sale_from'=>'date-time','regular_price'=>'string','date_on_sale_to'=>'date-time','sale_price'=>'string',
                                    'date_on_sale_from_gmt'=>'date-time','sku'=>'string','date_on_sale_to_gmt'=>'date-time','price_html'=>'string',
                                    'on_sale'=>'boolean','purchasable'=>'boolean','downloads'=>'array','download_limit'=>'int','tags'=>'array',
                                    'virtual'=>'boolean','button_text'=>'string','tax_status'=>'string','tax_class'=>'string','weight'=>'string',
                                    'external_url'=>'string','stock_status'=>'string','manage_stock'=>'boolean','grouped_products'=>'array',
                                    'backorders'=>'string','backorders_allowed'=>'boolean','backordered'=>'boolean','downloadable'=>'boolean',
                                    'description'=>'string','categories'=>'string','shipping_required'=>'boolean','shipping_taxable'=>'boolean',
                                    'dimensions'=>'object','shipping_class'=>'string','reviews_allowed'=>'boolean','average_rating'=>'string',
                                    'rating_count'=>'int','related_ids'=>'array','upsell_ids'=>'array','parent_id'=>'int','menu_order'=>'int',
                                    'purchase_note'=>'string','cross_sell_ids'=>'array','attributes'=>'array','default_attributes'=>'array',
                                    'variations'=>'array','stock_quantity'=>'int','images'=>'array','total_sales'=>'int','featured'=>'boolean'],
                    
                    'categories'=> ['id'=>'int','sync'=>'string','name'=>'string','slug'=>'string','parent'=>'int','count'=>'int','image'=>'object',
                                    'description'=>'string','display'=>'string','menu_order'=>'int'],
                                    
                    'variations'=> ['id'=>'int','sync'=>'string','date_created'=>'date-time','date_created_gmt'=>'date-time','meta_data'=>'array',
                                    'date_modified'=>'date-time','description'=>'string','date_modified_gmt'=>'date-time','date_on_sale_to'=>'date-time',
                                    'date_on_sale_from'=>'date-time','date_on_sale_from_gmt'=>'date-time','attributes'=>'array','menu_order'=>'int',
                                    'date_on_sale_to_gmt'=>'date-time','downloads'=>'array','price'=>'string','image'=>'object','virtual'=>'boolean',
                                    'sale_price'=>'string','on_sale'=>'boolean','status'=>'string','regular_price'=>'string','purchasable'=>'boolean',
                                    'downloadable'=>'boolean','download_limit'=>'int','sku'=>'string','download_expiry'=>'int','tax_status'=>'string',
                                    'tax_class'=>'string','manage_stock'=>'boolean','stock_quantity'=>'int','stock_status'=>'string','weight'=>'array',
                                    'backorders'=>'string','backorders_allowed'=>'boolean','backordered'=>'boolean','shipping_class_id'=>'string',
                                    'dimensions'=>'object','shipping_class'=>'string','permalink'=>'string'],
                                    
                    'orders'    => ['id'=>'int','sync'=>'string','parent_id'=>'int','number'=>'string','order_key'=>'string','created_via'=>'string',
                                    'version'=>'string','status'=>'string','currency'=>'string','date_created'=>'date-time','discount_tax'=>'string',
                                    'date_modified'=>'date-time','date_modified_gmt'=>'date-time','discount_total'=>'string','line_items'=>'array',
                                    'shipping_total'=>'string','shipping_tax'=>'string','cart_tax'=>'string','total'=>'string','total_tax'=>'string',
                                    'prices_include_tax'=>'boolean','billing'=>'object','customer_user_agent'=>'string','customer_note'=>'string',
                                    'customer_ip_address'=>'string','customer_id'=>'int','payment_method'=>'string','payment_method_title'=>'string',
                                    'date_paid'=>'date-time','date_paid_gmt'=>'date-time','date_completed'=>'date-time','transaction_id'=>'string',
                                    'cart_hash'=>'string','meta_data'=>'array','line_items'=>'array','tax_lines'=>'array','coupon_lines'=>'array',
                                    'shipping_lines'=>'array','fee_lines'=>'array','refunds'=>'array','set_paid'=>'boolean','shipping'=>'object',
                                    'date_completed_gmt'=>'date-time','date_created_gmt'=>'date-time'],
                    
                    'customers' => ['id'=>'int','sync'=>'string','role'=>'string','date_created'=>'date-time','date_created_gmt'=>'date-time',
                                    'date_modified'=>'date-time','date_modified_gmt'=>'date-time','email'=>'string','first_name'=>'string',
                                    'last_name'=>'string','username'=>'array','password'=>'string','billing'=>'object','shipping'=>'object',
                                    'is_paying_customer'=>'boolean','avatar_url'=>'string','meta_data'=>'array']   
                ];
                
    return apply_filters('wcgs_datatypes', $datatypes);
}

// Get the data types by keys
function wcgs_get_datatype_by_keys($context, $key) {
    $datatypes = wcgs_datatypes();
    
    return isset($datatypes[$context][$key]) ? $datatypes[$context][$key] : 'string';
    
}

// Field that need to be formatted
function wcgs_fields_format_required() {
    
    return apply_filters('wcgs_fields_format_required', 
                        ['categories'=>'array','tags'=>'array','images'=>'array', 'attributes'=>'array','image'=>'array','meta_data'=>'array']);
}