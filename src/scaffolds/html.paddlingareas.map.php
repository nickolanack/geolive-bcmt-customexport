
<script src="https://google.mapsapis.com/maps/api/js"> </script>
<script type="text/javascript"
	src="<?php echo UrlFrom(Core::ViewerDir().DS.'SimpleKml.js');?>">
    </script>

<div id="map"></div>
<style>
#map {
	width: 100%;
	height: 400px;
}
</style>
<script type="text/javascript">


function loadMap(){

console.log("hello world");


var map = new google.maps.Map(document.getElementById('map'), {
	disableDefaultUI:true,
    center: {
        lat:50.50359739949432,
        lng:-125.25016503906248
        },
    zoom: 6,
    mapTypeId:google.maps.MapTypeId.ROADMAP
  });
  <?php
/*
 * (new XMLControlQuery(<?php echo json_encode(UrlFrom(dirname(__DIR__).DS.'paddlingareas.kml')); ?>, "",
 * {})).addEvent("success",function(xml){
 *
 * //console.log(xml);
 *
 * (new SimpleParser({
 * polygonTransform:function(polygonParams, xmlSnippet){
 * //console.log(polygonParams);
 *
 * var polygon= new google.maps.Polygon((function(){
 * var polygonOpts={
 * paths:polygonParams.coordinates.map(function(coord){
 * return {lat:coord[0], lng:coord[1]};
 * })(),
 * fillColor:'#000000',
 * fillOpacity:0.5,
 * strokeColor:'#000000',
 * strokeWidth:1,
 * strokeOpacity:1
 * };
 * console.log(polygonOpts);
 * return polygonOpts;
 * })());
 * polygon.setMap(map);
 *
 * }
 * })).parsePolygons(xml);
 *
 * }).execute();
 */
?>
}


   </script>

<?php
