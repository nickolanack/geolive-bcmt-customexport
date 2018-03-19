<?php


$regions=json_decode(file_get_contents(__DIR__.'/regions.json'));

$inserts=array();
foreach($regions as $region){
	$regionName=$region->rgName;

	$insert='INSERT INTO {table} (paddlingArea, isSelectable) VALUES ("'.$regionName.'", 0);';
	$inserts[]=$insert;

	$parentId=count($inserts)-1;

	foreach($region->areas as $area){
		$areaName=$area->paName;
		$insert='INSERT INTO {table} (paddlingArea, parent) VALUES ("'.$areaName.'", (SELECT id FROM {table} WHERE paddlingArea="'.$regionName.'"));';
		$inserts[]=$insert;
	}
}

foreach ($inserts as $insert) {

	$table='cabc_GeoL_Attrib_Table_3544_paddlingArea';
	echo str_replace('{table}', $table, $insert)."\n";
}