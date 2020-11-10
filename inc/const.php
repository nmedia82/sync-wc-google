<?php
/**
 * Constants Here
 * 
 **/
 

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