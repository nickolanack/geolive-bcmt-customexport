
<?php
try {
    
    include_once ('KmlWriter.php');
    include_once ('GpxWriter.php');
    include_once ('Util.php');
    include_once ('PaddlingArea.php');
    include_once ('Region.php');
    
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
    
    print_r(array(
        $sections,
        $regions
    ));
    
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
    /*
     * $res = $mysqli->query("SELECT distinct(section) $from order by a.section");
     * $rgArray = array();
     *
     * if (empty($res))
     * return;
     *
     * while ($row = $res->fetch_assoc()) {
     * $region = trim($row['section']);
     * if ($region == '') {
     * continue;
     * }
     * // compare region to everything in the region array and if there is a match skip it
     * $alreadyHave = false;
     * foreach ($rgArray as $rgn) {
     * if ($region == $rgn) {
     * $alreadyHave = true;
     * break;
     * }
     * }
     * if (!$alreadyHave) {
     * $rgArray[] = $region;
     * }
     * }
     */
    $regionObjArray = array();
    
    $query = 'Select DISTINCT a.paddlingArea as area FROM ( SELECT * FROM ' . $table . ' ) as m, ' . AttributesFilter::JoinAttributeFilterObject(
        json_decode(
            '{
                    "join":"join","table":"siteData","set":"*","filters":[
                        {"field":"section","comparator":"equalTo","value":"' . '[[REGION]]' . '", "table":"siteData"}
                    ]
                }'), 'm.id', 'm.type');
    
    foreach ($rgArray as $region) {
        
        $db->iterate(str_replace('[[REGION]]', $region, $query), 
            function ($result) {
                
                print_r($result);
            });
        
        $regionObj = new Region($region);
        // get a distinct list of paddling areas to populate the paddling area drop-down
        $res = $mysqli->query(
            "SELECT distinct(paddlingArea) $from AND a.section like '%$region%' order by a.paddlingArea");
        $paArray = array();
        while ($row = $res->fetch_assoc()) {
            $pdArea = trim($row['paddlingArea']);
            if ($pdArea == '') {
                continue;
            }
            // compare paddling area to everything in the paddling area array and if there is a match skip it
            $alreadyHave = false;
            foreach ($paArray as $pda) {
                if ($pdArea == $pda) {
                    $alreadyHave = true;
                    break;
                }
            }
            if (!$alreadyHave) {
                $paArray[] = $pdArea;
                $paddleObj = new PaddlingArea($pdArea);
                $regionObj->areas[] = $paddleObj; // add the new paddling area to the region's list of paddling areas
            }
        }
        $regionObjArray[] = $regionObj;
    }
    $paddlingJson = json_encode($regionObjArray, JSON_PRETTY_PRINT);
    
    ?>
<a name="bcmtFormAnchor"></a>
<h3>Search for site by Region and Paddling Area</h3>

<script src="../ext/js/siteSearch.js"></script>

<script>
    window.addEventListener("load", function(){
    	PaddlingRegionSearchBehavior(<?php echo $paddlingJson ?>, {

    		rgSelect:"rgSelect",
    		regionImage:"regionImage",
    	    areaChoices:"areaChoices",
    	    paInstr:"paInstr",
    	    paSubmit:"paSubmit",

        });
    });
</script>

<?php
    if (empty($sitesArray)) {
        ?>
<form name="bcmtForm" method="POST"
	action="sites-by-region-and-paddling-area#bcmtFormAnchor">
	<input type="hidden" name="prevRegion"
		value="<?php echo $currRegion ?>"> <img id="regionImage"
		src="../images/stories/sixregions.jpg" alt="Six Regions" width="300px"
		style="float: left">
	<table style="margin-left: 330px">
		<tr>
			<td>Region:</td>
			<td><select id="rgSelect" name="rgSelect" class="btn btn-success"
				style="height: 30px;">
					<option>choose a region</option>
			<?php
        
        echo implode(
            array_map(
                function ($region) {
                    $name = $region->rgName;
                    return '<option value="' . $name . '">' . $name . '</option>';
                }, $regionObjArray));
        
        ?>
			</select></td>
		</tr>
		<tr>
			<td id="paInstr" style="visibility: hidden" colspan=2>Choose the
				paddling areas you wish to view in either Google Earth or your GPS:</td>
		</tr>
		<tr>
			<td>&nbsp;&nbsp;&nbsp;</td>
			<td id="areaChoices" style="vertical-align: top"></td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;&nbsp;&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;&nbsp;&nbsp;</td>
			<td id="paSubmit" style="visibility: hidden"><input type="submit"
				value="Generate files" class="btn btn-primary"></td>
		</tr>
	</table>
	<br>
<?php
    } else {
        $kmlWriter = new KmlWriter();
        $gpxWriter = new GpxWriter();
        $kmlFileRef = $kmlWriter->writeKml($fileName, $sitesArray);
        $gpxFileRef = $gpxWriter->writeGpx($fileName, $sitesArray);
        ?>
	<p>
	
	
	<form name="bcmtForm" method="POST"
		action="map/current-sites-on-bc-marine-trails-map-table#bcmtFormAnchor">
		<a class="btn btn-success" style="margin-right: 30px"
			href="<?php echo $kmlFileRef ?>">Download results to Google Earth</a>
		<a class="btn btn-success" style="margin-right: 30px"
			href="<?php echo $gpxFileRef ?>">Download results for your GPS</a> <input
			class="btn btn-primary" type="submit" value="New search">
	</form>
	</p>
<?php
    }
    
    ?>
</form>

<?php
} catch (Exception $e) {
    die(print_r($e, true));
}

?>
