<?php

/**
 * This is the access point for displaying the map, paddlingArea, selection tool
 */
try {
    
    include_once (__DIR__.'/lib/GeoliveHelper.php');
    
    error_reporting(E_ALL ^ E_NOTICE); // report everything except notices
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', '../logs/siteSearch.log');
    
    if (GeoliveHelper::ScriptWasAccessedDirectlyFromUrl()) {
        HtmlBlock('paddlingareas.map', array(), __DIR__ . DS . 'scaffolds');
    } else {
        
        throw new Exception("Unrecognized Execution Environment");
    }
} catch (Exception $e) {
    die(print_r($e, true));
}

?>
