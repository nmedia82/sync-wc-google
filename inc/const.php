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

// Syncing Array
function wcgs_sync_array(){
    
    $sync_array = ['products'=>__("Products","wcgs"), 'categories'=>__("Categories","wcgs")];
    return apply_filters('wcgs_sync_array', $sync_array);
}

// All datatypes
function wcgs_datatypes() {
    
    $datatypes = [  'products'  => ['meta_data'=>'array'],
                    'orders'    => ['id'=>'int','sync'=>'string','billing'=>'object','shipping'=>'object','line_items'=>'array']
                ];
                
    return apply_filters('wcgs_datatypes', $datatypes);
}

// Get the data types by keys
function wcgs_get_datatype_by_keys($context, $key) {
    $datatypes = wcgs_datatypes();
    
    return isset($datatypes[$context][$key]) ? $datatypes[$context][$key] : 'string';
    
}