<?php

/**
 * Major changes to export function:
 * export is now done on selection buttons (kml or gpx) which opens the url to this file in a new tab
 * no need for 'new search', as it is updateable at all times
 */
try {
    
    include_once ('lib/GeoliveHelper.php');
    GeoliveHelper::LoadCoreLibs();
    
    error_reporting(E_ALL ^ E_NOTICE); // report everything except notices
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', '../logs/siteSearch.log');
    
    if (GeoliveHelper::ScriptWasAccessedDirectlyFromCommandLine()) {
        
        // GeoliveHelper::LoadGeoliveFromCommandLine();
        // echo 'Hello World - Terminal Command List: [ -empty- ]';
    } elseif (GeoliveHelper::ScriptWasAccessedDirectlyFromUrl()) {
        
        /**
         * Actually render the kml or gpx file directly to client
         * this happens when they sumbit the form to a new tab - which directly accesses this file.
         * at this point joomla is gone which is great becuase I don't want to wait for all the eventhandlers
         * etc. (now relying on Geolive to provide database access)
         */
        
        if (UrlVar('task') == 'export') {
            $paArray = UrlVar('paddlingAreas', array());
            if (!empty($paArray)) {
                include_once ('lib/Util.php');
                
                $sitesArray = array();
                GeoliveHelper::QueriedSiteListInAreas($paArray, 
                    function ($row) use(&$sitesArray) {
                        $sitesArray[] = get_object_vars($row);
                    });
                
                if (UrlVar('exportOutput') == 'kml') {
                    header('Content-Type: application/kml+xml;');
                    header('Content-disposition: filename="export.kml"');
                    include_once ('lib/KmlWriter.php');
                    $kmlWriter = new KmlWriter();
                    echo $kmlWriter->writeKml($sitesArray);
                } else {
                    
                    header('Content-Type: application/gpx+xml;');
                    header('Content-disposition: filename="export.gpx"');
                    include_once ('lib/GpxWriter.php');
                    $gpxWriter = new GpxWriter();
                    echo $gpxWriter->writeGpx($sitesArray);
                }
            }
            
            return;
        }
        /*
         * could implement other ajax commands ie:
         * list number of results actively while user changes selection
         */
        echo 'Ajax Command List: [export]';
    } elseif (GeoliveHelper::ScriptWasIncludedFromJoomla()) {
        
        include_once ('lib/PaddlingArea.php');
        include_once ('lib/Region.php');
        
        $regionObjArray = array();
        
        foreach (GeoliveHelper::DefinedRegionsList() as $region) {
            
            $regionObj = new Region($region);
            
            foreach (GeoliveHelper::DistinctPaddlineAreas($region) as $pdArea) {
                
                $paddleObj = new PaddlingArea($pdArea);
                $regionObj->areas[] = $paddleObj;
            }
            
            $regionObjArray[] = $regionObj;
        }
        
        if (empty($regionObjArray)) {
            throw new Exception('There were no regions');
        }
        
        // HtmlBock is used to seperate templates from code
        // look in scaffolds/html.form.select.php
        HtmlBlock('form.select', 
            array(
                'regionObjArray' => $regionObjArray,
                'url' => UrlFrom(__FILE__)
            ), __DIR__ . DS . 'scaffolds');
    } else {
        
        // testing environment?
    }
} catch (Exception $e) {
    die(print_r($e, true));
}

?>
