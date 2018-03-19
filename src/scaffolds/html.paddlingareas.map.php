<html>
<head>
<script
	src="https://s3-us-west-2.amazonaws.com/nickolanackbucket/mootools/mootools-core.js"
	type="text/javascript"></script>
<script
	src="https://s3-us-west-2.amazonaws.com/nickolanackbucket/mootools/mootools-more.js"
	type="text/javascript"></script>
<script
	src="https://s3-us-west-2.amazonaws.com/nickolanackbucket/mootools/mootools_compat.js"
	type="text/javascript"></script>
<script type="text/javascript"
	src="<?php

	echo UrlFrom('{plugins}/Maps/bower_components/js-simplekml/KmlReader.js')

//UrlFrom(dirname(dirname(__DIR__)) . DS . 'bower_components' . DS . 'js-simplekml' . DS . 'KmlReader.js');

?>">
    </script>

<script
	src="https://s3-us-west-2.amazonaws.com/nickolanackbucket/popover/Popover.js"
	type="text/javascript"></script>
<link rel="stylesheet"
	href="https://s3-us-west-2.amazonaws.com/nickolanackbucket/popover/popover.css"
	type="text/css" />
<script
	src="<?php echo UrlFrom(Core::AdminDir() . '/js/Controls/UIPopover.js'); ?>"
	type="text/javascript"></script>
<script
	src="<?php echo UrlFrom(Core::AdminDir() . '/js/Controls/UIMapPopover.js'); ?>"
	type="text/javascript"></script>
<script src="<?php echo UrlFrom(Core::AdminDir()); ?>/js/JSUtilities.js"
	type="text/javascript"></script>
<script
	src="<?php echo UrlFrom(Core::AdminDir() . '/js/Ajax/AjaxControlQuery.js'); ?>"
	type="text/javascript"></script>

<script
	src="<?php echo UrlFrom(dirname(__DIR__) . '/js/paddlingAreas.js'); ?>"
	type="text/javascript"></script>
<style>
#map {
	width: 100%;
	height: 100%;
}

html{
	height:100%;
}
body {
	height:100%;
	margin: 0;
}

.paddling-areas-detail {
	background-color: rgba(255, 255, 255, 0.6);
	padding: 10px 20px;
	color: #55acee;
	text-shadow: 0 0 3px white, 0 0 1px white;
	font-size: 15px;
	font-weight: bold;
	width: 100%;
	pointer-events: none;
}

.paddling-areas-detail .no-region {
	color: tomato;
	font-weight: 100;
}

button.btn.btn-danger {
	width: 50px;
	height: 38px;
	border: none;
	background-color: #55ACEE;
	color: white;
	font-size: 15px;
	cursor: pointer;
}

.UIMapPopover .tip-text {
	width: 150px;
	font-weight: 500;
}

.UIMapPopover .pop-area {
	color: cornflowerblue;
}

.UIMapPopover .pop-area.remove {
	color: crimson;
}
</style>
</head>
<body>



	<div id="map"></div>


	<script type="text/javascript">


function initMap(){

console.log("hello world");


var map = new google.maps.Map(document.getElementById('map'), {
	disableDefaultUI:true,
    center: {
        lat:50.50359739949432,
        lng:-125.25016503906248
        },
    zoom: 6,
    mapTypeId:google.maps.MapTypeId.ROADMAP,
    panControl:true,
    zoomControl:true
  });

PaddlingRegionMapSearchBehavior(
    <?php echo json_encode(GeoliveHelper::GetCachedRegionsList(), JSON_PRETTY_PRINT); ?>,
    map,
    <?php echo json_encode(UrlFrom(dirname(__DIR__) . DS . 'paddlingareas.kml')); ?>
    );

}


   </script>
	<script async defer
		src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBnTJCsJO2piovlyQfpmemfQXVjwkdB7R4&callback=initMap"></script>
</body>

</html>
