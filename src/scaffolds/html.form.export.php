<?php
$config = array_merge(array(
    
    'kmlFile' => '',
    'gpxFile' => ''
), $params);

?>
<a name="bcmtFormAnchor"></a>
<h3>Export sites by Region and Paddling Area</h3>
<form name="bcmtForm" method="POST"
	action="map/current-sites-on-bc-marine-trails-map-table#bcmtFormAnchor">
	<a class="btn btn-success" style="margin-right: 30px"
		href="<?php echo $config['kmlFile'] ?>">Download results to Google
		Earth</a> <a class="btn btn-success" style="margin-right: 30px"
		href="<?php echo $config['gpxFile'] ?>">Download results for your GPS</a>
	<input class="btn btn-primary" type="submit" value="New search" /> <br />
</form>
