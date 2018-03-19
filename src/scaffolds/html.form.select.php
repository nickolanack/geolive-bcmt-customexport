<?php
$config = array_merge(array(
    'regions' => array(),
    'url'     => '',
), $params);

// echo (Core::HTML()->isCaching() ? 'caching' : 'no-caching') . "<br/>";
// echo (Core::HTML()->isBuffered() ? 'buffering' : 'no-buffering (joomla buffered maybe)') . "<br/>";
// echo (Core::HTML()->isRaw() ? 'raw text' : 'document') . "<br/>";

// Behavior('ajax');

?>
<script
	src="<?php echo UrlFrom(Core::AdminDir() . '/js/Ajax/AjaxControlQuery.js'); ?>"
	type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo UrlFrom(dirname(__DIR__) . '/css/siteSearch.css'); ?>" type="text/css">

<script src="<?php echo UrlFrom(dirname(__DIR__) . '/js/siteSearch.js'); ?>" type="text/javascript"></script>

<script type="text/javascript">
window.addEventListener("load", 
function(){
	PaddlingRegionSearchBehavior(
 <?php echo json_encode($config['regions'], JSON_PRETTY_PRINT); ?>, <?php echo json_encode($config['layers'], JSON_PRETTY_PRINT); ?>, (new Class({
	    Extends:AjaxControlQuery,
	    initialize:function(task, json){
	        var me=this;
	        me.parent(<?php echo json_encode($config['url']); ?>, task, Object.append(json,{
	        	"widget":"paddlingAreasTool"
	        }));
		}
 	})), window.location.href.split('?')[0]+'?show=map');
  });

</script>


<a name="bcmtFormAnchor"></a>
<h3>Search for site by Region and Paddling Area</h3>



<div id="mapIframeContainer"></div>
	<!--<iframe id="mapFrame" class="map-view"
	src="<?php //echo UrlFrom(dirname(__DIR__) . DS . 'paddlingAreas.php'); ?>"
	style="border: none; width: 100%; height: 550px;"></iframe>-->


<form id="exportForm" action="<?php echo $config['url']; ?>" name="bcmtForm" method="POST"
	target="_blank">
	<input type="hidden" name="task" value="export" /> <input id="exportJson" type="hidden" name="json" value="{}" /><input
		id="exportOutput" type="hidden" name="exportOutput" value="" /> <input
		id="siteList" type="hidden" name="siteList" value="[]" />
	<div id="formFrame" class="form-view"
		style="position: absolute; visibility: hidden;">
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
			<div class="info">Choose the paddling areas you wish to view in
				either Google Earth or your GPS:</div>

		</div>
		<br />
		<table>

			<tr>

				<td id="areaChoices" style="vertical-align: top"></td>
				<td style="vertical-align: top"><img id="regionImage"
					src="../images/stories/sixregions.jpg" alt="Six Regions"></td>
			</tr>

		</table>
	</div>

	<div id="paSubmit" style="">
		<button type="button" id="" class="btn" data-out="preview" onclick="return false;"
			disabled="true">Preview Sites</button>
		<button type="button" id="exportToKml" class="btn"
			data-out="kml" onclick="return false;" disabled="true">Download
			results to Google Earth</button>
		<button type="button" id="exportToGpx" class="btn"
			data-out="gpx" onclick="return false;" disabled="true">Download
			results for your GPS</button> <span id="siteCount"></span>
	</div>
	<br />
	<div id="sitePreviewHeader" style="visibility: hidden;">
		<div class="info">You can select or remove individual sites from the
			list below. Items will not appear in the exported files if they are
			not selected.</div>
		<br />
		<div>
			<button type="button" id="selectAllSites" onclick="return false" class="btn btn-info">select
				all</button><button id="removeAllSites" onclick="return false"
				class="btn btn-info">remove all</button>
			<button type="button" id="gridView" class="btn"
				style="padding: 4px 7px; float: right;"><img
				src="<?php echo UrlFrom('{assets}/Map Item Icons/xsm_table.png');?>?tint=rgb(0, 68, 204)"></button>
			<button type="button" id="tableView" class="btn btn-primary active"
				style="padding: 4px 8px; float: right;"><img
				src="<?php echo UrlFrom('{assets}/Map Item Icons/xsm_list.png'); ?>?tint=rgb(255,255,255)"></button>
		</div>
	</div>
	<!-- there are two views available table-view an grid-view, the css class name below sets the default view -->
	<div id="site_preview" class="table-view"></div>
	<br />
	<div id="paSubmitFooter" style="visibility: hidden;">
		<button type="button" class="btn btn-success" data-out="kml" onclick="return false;">Download
			results to Google Earth</button>
		<button type="button" class="btn btn-success" data-out="gpx"
			onclick="return false;">Download results for your GPS</button>
	</div>
	<br />

</form>


