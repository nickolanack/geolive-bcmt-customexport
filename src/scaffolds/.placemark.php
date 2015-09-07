<?php

/*
 * Dont use this as a scaffold. just use the code in the callback for
 * Scaffold('kml', array('callback'=>function(){
 *
 * ...here...
 *
 * }));
 */
$config = array_merge(
    array(
        'coordinates' => array(
            0,
            0,
            5.0
        ),
        'name' => '',
        'description' => ''
    ), $params);
if (count($config['coordinates']) == 2) {
    $config['coordinates'][] = 5.0;
}
include_once MapsPlugin::Path() . DS . 'lib' . DS . 'features' . DS . 'encoder.php';
echo FeatureEncoder::ToKmlString(array_merge(array(
    'type' => 'marker'
), $config));