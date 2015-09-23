<?php

/**
 * Major changes to export function:
 * export is now done on selection buttons (kml or gpx) which opens the url to this file in a new tab.
 * no need for 'new search', as it is updateable at all times (no need for generate files either).
 * use core framework from geolive which provides access to database, (outside of Joomla too, so the script can run directly).
 * no need to maintain production and develpment site settings geolive detects this.
 * GeoliveHelper class contains static methods mostly for interacting with the database - and hiding geolive specific code
 * in methods that are named very descriptively...
 * no need to write output files to disk since this script can provide ajax like methods when accessed directly
 * seperated html template from code here. (uses Scaffolds)
 * Removed jquery, since the template uses mootools and the script is relatively small anyway. also moved the javascript into
 * its own file
 * Added ajax method list_sites. returns a list of all sites in paddling areas as well as an html article for the first 25 items
 * Added ajax method site_articles returns site articles for array of item id's
 *
 *
 * TODO:
 *
 *  - iterate-print kml and gpx output this will use alot less memory and will send to client sooner although it is relatively quick
 *  - use attribute filter instead of sql query to get items - this returns results that might get missed trimmed/case-insensitive
 *    and also uses client auth groups (so changes to readAccess are not a problem) and no need to sql escape things becuase it uses
 *    it's own syntax
 *  - provide ajax list which will print items and allow specific site selections for output
 */
try {
    
    include_once ('lib/GeoliveHelper.php');
    
    error_reporting(E_ALL ^ E_NOTICE); // report everything except notices
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', '../logs/siteSearch.log');
    
    if (GeoliveHelper::ScriptWasAccessedDirectlyFromCommandLine()) {
        
        // GeoliveHelper::LoadGeoliveFromCommandLine();
        // could run this script from command line. or could
        // implement asynrounous functions using shell_exec('php '.__FILE__.' ')
    } elseif (GeoliveHelper::ScriptWasAccessedDirectlyFromUrl()) {
        
        if (UrlVar('task') == 'export') {
            
            /**
             * Actually render the kml or gpx file directly to client
             * this happens when they sumbit the form to a new tab - which directly accesses this file.
             * at this point joomla is gone which is great becuase I don't want to wait for all the eventhandlers
             * etc. (now relying on Geolive to provide database access)
             */
            
            $paArray = UrlVar('paddlingAreas', array());
            if (!empty($paArray)) {
                include_once ('lib/Util.php');
                
                $sitesArray = array();
                // I am working on another method called FilteredSiteListInAreas
                // which will work using Attribute filters - this will hopefully
                // be more robust and ignore minor spelling differences
                
                $siteList = json_decode(UrlVar('siteList', '[]'));
                if (is_array($siteList) && !empty($siteList)) {
                    GeoliveHelper::QueriedSiteListInAreasInIdList($paArray, $siteList, 
                        function ($row) use(&$sitesArray) {
                            $sitesArray[] = get_object_vars($row);
                        });
                } else {
                    GeoliveHelper::QueriedSiteListInAreas($paArray, 
                        function ($row) use(&$sitesArray) {
                            $sitesArray[] = get_object_vars($row);
                        });
                }
                
                // die(Core::GetDatasource()->getQuery());
                
                if (UrlVar('exportOutput') == 'kml') {
                    
                    header('Content-Type: application/kmz+zip;');
                    header('Content-disposition: filename="export.kmz"');
                    $filename = tempnam(__DIR__, 'zip');
                    include_once ('lib/KmlWriter.php');
                    $kmlWriter = new KmlWriter();
                    $kmlWriter->writeKml($sitesArray);
                    
                    $zip = new ZipArchive();
                    $zip->open($filename);
                    $zip->addFromString('default.kml', $kmlWriter->writeKml($sitesArray));
                    
                    foreach ($kmlWriter->getStyles() as $icon) {
                        $zip->addFile(__DIR__ . DS . $icon, $icon);
                    }
                    $zip->close();
                    
                    readfile($filename);
                    unlink($filename);
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
        
        include_once 'lib/AjaxRequest.php';
        
        if (UrlVar('task') == 'list_sites') {
            
            AjaxRequest::ListSites();
            return;
        }
        
        if (UrlVar('task') == 'count_sites') {
            
            AjaxRequest::CountSites();
            return;
        }
        
        if (UrlVar('task') == 'site_articles') {
            
            AjaxRequest::ArticlesForSites();
            return;
        }
        
        if (UrlVar('task') == 'unit_test') {
            
            if (Core::Client()->isAdmin()) {
                
                // TODO: make a phpunit.xml and better unit tests
                // then phpunit --configuration phpunit.xml
                
                print_r(
                    htmlspecialchars(
                        shell_exec(
                            '/usr/local/bin/phpunit ' . escapeshellarg(__DIR__ . DS . 'lib' . DS . 'UtilTest') . ' 2>&1')));
            }
            
            return;
        }
        
        /*
         * could implement other ajax commands ie:
         * list number of results actively while user changes selection
         */
        echo 'Ajax Command List: [export]';
    } elseif (GeoliveHelper::ScriptWasIncludedFromJoomla()) {
        
        // display a dynamic form containing regions, and area selection.
        // TODO add layer selection.
        
        if (!file_exists(__DIR__ . DS . 'regions.json')) {
            
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
                file_put_contents(__DIR__ . DS . 'regions.json', json_encode($regionObjArray, JSON_PRETTY_PRINT));
            }
        } else {
            
            $regionObjArray = json_decode(file_get_contents(__DIR__ . DS . 'regions.json'));
        }
        
        if (empty($regionObjArray)) {
            throw new Exception('There were no regions');
        }
        
        // HtmlBock is used to seperate templates from code
        // look in scaffolds/html.form.select.php
        
        HtmlBlock('form.select', 
            array(
                'regions' => $regionObjArray,
                'layers' => array_map(
                    function ($layer) {
                        return array(
                            'id' => $layer->getId(),
                            'name' => $layer->getName()
                        );
                    }, GeoliveHelper::VisibleLayers()),
                'url' => UrlFrom(__FILE__)
            ), // route the downloads/ajax directly to this file - outside of joomla.
__DIR__ . DS . 'scaffolds');
        
        if (Core::Client()->isAdmin()) {
            
            // link to test for admin
            
            ?><a href="<?php echo UrlFrom(__FILE__); ?>?task=unit_test">run
	unit tests</a><?php
        }
    } else {
        
        throw new Exception("Unrecognized Execution Environment");
    }
} catch (Exception $e) {
    die(print_r($e, true));
}

?>
