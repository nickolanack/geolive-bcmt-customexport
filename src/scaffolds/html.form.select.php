<?php
$config = array_merge(array(
    'regions' => array(),
    'url' => ''
), $params);

// echo (Core::HTML()->isCaching() ? 'caching' : 'no-caching') . "<br/>";
// echo (Core::HTML()->isBuffered() ? 'buffering' : 'no-buffering (joomla buffered maybe)') . "<br/>";
// echo (Core::HTML()->isRaw() ? 'raw text' : 'document') . "<br/>";

// Behavior('ajax');

?>
<link rel="stylesheet" href="ext/css/siteSearch.css" type="text/css">
<script src="ext/js/siteSearch.js" type="text/javascript"></script>

<script type="text/javascript">
window.addEventListener("load", function(){
	PaddlingRegionSearchBehavior(
 <?php echo json_encode($config['regions'], JSON_PRETTY_PRINT); ?>, <?php echo json_encode($config['layers'], JSON_PRETTY_PRINT); ?>, (new Class({
	    Extends:AjaxControlQuery,
	    initialize:function(task, json){
	        var me=this;
	        me.parent(<?php echo json_encode($config['url']);?>, task, json);
		}
 })));
  });

</script>


<a name="bcmtFormAnchor"></a>
<h3>Search for site by Region and Paddling Area</h3>
<form id="exportForm" name="bcmtForm" method="POST"
	action="<?php echo $config['url'] ?>" target="_blank">
	<input type="hidden" name="task" value="export" /> <input
		id="exportOutput" type="hidden" name="exportOutput" value="" /> <input
		id="siteList" type="hidden" name="siteList" value="[]" />

	<table>
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
        }, $config['regions']));

?>
			</select></td>
		</tr>
	</table>

	<div id="paInstr" style="display: none;">
		<br />
		<div class="info">Choose the paddling areas you wish to view in either
			Google Earth or your GPS:</div>

	</div>
	<br />
	<table>

		<tr>

			<td id="areaChoices" style="vertical-align: top"></td>
			<td style="vertical-align: top"><img id="regionImage"
				src="../images/stories/sixregions.jpg" alt="Six Regions"></td>
		</tr>

	</table>


	<div id="paSubmit" style="visibility: hidden;">
		<a id="exportToKml" class="btn btn-success" data-out="kml"
			onclick="return false;">Download results to Google Earth</a> <a
			id="exportToGpx" class="btn btn-success" data-out="gpx"
			onclick="return false;">Download results for your GPS</a> <a
			id="exportToGpx" class="btn btn-primary" data-out="preview"
			onclick="return false;">Preview Sites</a><span id="siteCount"></span>
	</div>
	<br />
	<div id="sitePreviewHeader" style="visibility: hidden;">
		<div class="info">You can select or remove individual sites from the
			list below. Items will not appear in the exported files if they are
			not selected.</div>
		<br />
		<div>
			<a id="selectAllSites" onclick="return false" class="btn btn-info">select
				all</a><a id="removeAllSites" onclick="return false"
				class="btn btn-info">remove all</a><a id="gridView"
				class="btn btn-primary active"
				style="padding: 4px 7px; float: right;"><img
				src="/administrator/components/com_geolive/assets/Map%20Item%20Icons/xsm_table.png?tint=rgb(255,255,255)"></a><a
				id="tableView" class="btn" style="padding: 4px 8px; float: right;"><img
				src="/administrator/components/com_geolive/assets/Map%20Item%20Icons/xsm_list.png?tint=rgb(0, 68, 204)"></a>
		</div>
	</div>
	<div id="site_preview" class="grid-view"></div>
	<br />
	<div id="paSubmitFooter" style="visibility: hidden;">
		<a class="btn btn-success" data-out="kml" onclick="return false;">Download
			results to Google Earth</a> <a class="btn btn-success" data-out="gpx"
			onclick="return false;">Download results for your GPS</a>
	</div>
	<br />
</form>


