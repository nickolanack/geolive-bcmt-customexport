
<?php
try {
    
    include_once ('lib/Util.php');
    include_once ('lib/PaddlingArea.php');
    include_once ('lib/Region.php');
    
    error_reporting(E_ALL ^ E_NOTICE); // report everything except notices
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', '../logs/siteSearch.log');
    
    /*
     * This is what I am working on.
     * TODO use Geolive framework to replace:
     *
     * - sql query with attribute filter.
     * - sql list of sections
     * - sql list of areas
     */
    
    if (!defined('DS')) {
        define('DS', DIRECTORY_SEPARATOR);
    }
    include_once dirname(__DIR__) . DS . 'administrator' . DS . 'components' . DS . 'com_geolive' . DS . 'core.php';
    Core::LoadPlugin('Attributes');
    
    /* @var $db CoreDatabase */
    $db = Core::Get('Maps')->getDatabase();
    
    $table = $db->getTableNames()['M'];
    
    $tableMetadata = AttributesTable::GetMetadata('siteData');
    $rgArray = array_map(function ($region) {
        return $region->section;
    }, AttributesField::GetDefinedList(AttributesField::GetMetadata('section', $tableMetadata)));
    // $regions = AttributesField::GetDefinedList(AttributesField::GetMetadata('paddlingArea', $tableMetadata));
    
    if (class_exists('JFactory')) {
        
        // using joomla's db connection,
        // no need to maintain credentials
        // both dev and prod sites will work
        
        $dbo = JFactory::getDbo();
        $dbPrfx = $dbo->getPrefix();
        $mysqli = $dbo->getConnection();
    } else {
        throw new Exception('Expected Joomla class: JFactory');
    }
    
    $from = "FROM " . $dbPrfx . "GeoL_Attrib_Table_3544 a inner join " . $dbPrfx .
         "GeoL_Map_MapItem m on a.mapitem = m.id WHERE (m.lid=1 OR m.lid=2 OR m.lid=3 OR m.lid=7)";
    
    $paArray = $_POST['paddlingAreas'];
    $fileName = "";
    $sitesArray = array();
    if (!empty($paArray)) {
        $paWhere = '';
        foreach ($paArray as $pa) {
            $fileName .= $pa;
            $paWhere = "AND a.paddlingArea LIKE '%$pa%'"; // NOTE: using LIKE because some Paddling Area entries have
                                                          // whitespace around them
            $res = $mysqli->query("SELECT * $from $paWhere order by m.name");
            while ($row = $res->fetch_assoc()) {
                $sitesArray[] = $row;
            }
        }
    }
    $fileName = "paddling_areas_" . hash('crc32b', $fileName);
    
    $regionObjArray = array();
    
    $readAccessFilter = array(
        'readAccess' => array(
            'comparator' => 'IN',
            'value' => '(\'' . implode('\', \'', Core::Client()->getAccessGroups()) . '\')',
            'qoutes' => false
        )
    );
    $layers = MapController::GetAllLayers($readAccessFilter);
    
    // TODO: could add checkboxes to select items from layers as well
    
    $query = 'Select DISTINCT a.paddlingArea as area FROM ( SELECT * FROM ' . $table . ' ) as m, ' . AttributesFilter::JoinAttributeFilterObject(
        json_decode(
            '{
                    "join":"join","table":"siteData","set":"*","filters":[
                        {"field":"section","comparator":"equalTo","value":"' . '[[REGION]]' . '", "table":"siteData"}
                    ],"show":"paddlingArea"
                }'), 'm.id', 'm.type') . ' AND m.lid IN (' . implode(
        ', ', array_map(function ($layer) {
            return $layer->getId();
        }, $layers)) . ') ORDER BY a.paddlingArea';
    
    echo ($query) . "<br/><br/><br/>";
    
    // die($query);
    
    foreach ($rgArray as $region) {
        
        $regionObj = new Region($region);
        $areas = array();
        $db->iterate($q = str_replace('[[REGION]]', $region, $query), 
            
            function ($result) use(&$areas) {
                $areas[] = ucwords(trim($result->area));
            });
        
        $paArray = array();
        foreach (array_unique($areas) as $pdArea) {
            $paArray[] = $pdArea;
            $paddleObj = new PaddlingArea($pdArea);
            $regionObj->areas[] = $paddleObj; // add the new paddling area to the region's list of paddling areas
        }
        
        $regionObjArray[] = $regionObj;
    }
    
    ?>





<?php
    if (empty($sitesArray)) {
        
        HtmlBlock('form.select', array(
            'regionObjArray' => $regionObjArray
        ), __DIR__ . DS . 'scaffolds');
    } else {
        
        include_once ('lib/KmlWriter.php');
        include_once ('lib/GpxWriter.php');
        
        $kmlWriter = new KmlWriter();
        $gpxWriter = new GpxWriter();
        
        ?>


	<?php
        
        HtmlBlock('form.export', 
            array(
                'kmlFile' => $kmlWriter->writeKml($fileName, $sitesArray),
                'gpxFile' => $gpxWriter->writeGpx($fileName, $sitesArray)
            ), __DIR__ . DS . 'scaffolds');
        
        ?>




<?php
    }
    
    ?>


<?php
} catch (Exception $e) {
    die(print_r($e, true));
}

?>
