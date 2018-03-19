<?php

/**
 * This is the access point for displaying the map, paddlingArea, selection tool
 */
try {
    
    include_once (__DIR__.'/lib/GeoliveHelper.php');
        
   // if (GeoliveHelper::ScriptWasAccessedDirectlyFromUrl()) {
        HtmlBlock('paddlingareas.map', array(), __DIR__ . DS . 'scaffolds');
    // } else {
        
    //     throw new Exception("Unrecognized Execution Environment");
    // }
} catch (Exception $e) {
    die(print_r($e, true));
}

?>
