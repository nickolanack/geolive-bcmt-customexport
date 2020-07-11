<?php

$dir = __DIR__;
while ((!file_exists($dir . DIRECTORY_SEPARATOR . 'core.php') && (!empty($dir)))) {
	$dir = dirname($dir);
}

if (file_exists($dir . DIRECTORY_SEPARATOR . 'core.php')) {
	include_once $dir . DIRECTORY_SEPARATOR . 'core.php';
} else {
	throw new Exception('failed to find core.php');
}

HtmlDocument()->setDocumentRoot('/srv/www/vhosts/production/bcmarinetrails.s54.ok.ubc.ca/http');

include_once __DIR__ . '/vendor/autoload.php';

$box = (new \box\Client(
	GetWidget(44)->getParameter('boxAuth')
))
	->useCachePath(GetPath('{front}/../') . '/box-items')
	->cacheItemsInPath('/geolive-site-images'); //optimization



$syncFolders=array();


$box->getEvents(function($data)use($box, &$syncFolders){


    if($data->source->type==='folder'){

        $path=$data->source->path_collection->entries;

        if(count($path)&&(array_pop($path)->name=='geolive-site-images')){
            
            $syncFolders[$data->source->id]=$data->source->name;
            print_r($data);

        }
    }

    if($data->source->type==='file'){

        $path=$data->source->path_collection->entries;
        $folder=array_pop($path);
        if(count($path)&&(array_pop($path)->name=='geolive-site-images')){

            $syncFolders[$folder->id]=$folder->name;
            print_r($data);
          
        }
        
    }

   

});



print_r($syncFolders);
