<?php
/*
** Handling the Transients
*/

function wcgs_set_transient($key, $data) {
    
    set_transient($key, $data);
}

function wcgs_get_transient($key) {
    
    $t = get_transient($key);
    return $t;
}

function wcgs_delete_transient($key) {
    
    delete_transient($key);
}