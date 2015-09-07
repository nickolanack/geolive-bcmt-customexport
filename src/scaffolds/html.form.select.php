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
<script
	src="<?php echo UrlFrom(Core::AdminDir().'/js/Ajax/AjaxControlQuery.js'); ?>"
	type="text/javascript"></script>
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
		id="exportOutput" type="hidden" name="exportOutput" value="" /> <img
		id="regionImage" src="../images/stories/sixregions.jpg"
		alt="Six Regions" width="300px" style="float: left">
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
        }, $config['regions']));

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
			<td id="paSubmit" style="visibility: hidden"><a id="exportToKml"
				class="btn btn-success" style="margin-right: 30px" data-out="kml"
				onclick="return false;">Download results to Google Earth</a><a
				id="exportToGpx" class="btn btn-success"
				style="margin: 10px; margin-left: 0;" data-out="gpx"
				onclick="return false;">Download results for your GPS</a><a
				id="exportToGpx" class="btn btn-primary"
				style="margin: 10px; margin-left: 0;" data-out="preview"
				onclick="return false;">Preview Sites</a></td>
		</tr>
	</table>
	<br />
</form>
<div id="site_preview"></div>

