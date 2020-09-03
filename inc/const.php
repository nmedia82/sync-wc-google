<?php
/**
 * Constants Here
 * 
 **/
 

function wcgs_get_header_column_by_index($index) {
    
    $column_names = array('A', 'B', 'C',
                    'D', 'E', 'F',
                    'G', 'H',
                    'I', 'J',
                    'K', 'L');
    // wcgs_pa($column_names[$index]);
    return isset($column_names[$index]) ? apply_filters('wcgs_product_header_column', $column_names[$index], $index) : null;
}