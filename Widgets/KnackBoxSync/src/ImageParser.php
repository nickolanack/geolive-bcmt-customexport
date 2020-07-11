<?php

namespace bcmt;

class ImageParser {

	public function urlsFromHtml($html, $baseUrl) {

		$images = explode('<img ', $html);
		array_shift($images);
		if (!count($images)) {

			return array();
		}

		$imageUrls = array_map(function ($imageStr) use ($baseUrl) {

			$urlParts = explode('src="', $imageStr);
			$url = $urlParts[1];
			$urlParts = explode('"', $url);
			$url = array_shift($urlParts);

			if (strpos($url, 'components/com_geolive') !== 0) {
				throw new \Exception('Expected `components\com_geolive` @{0}: ' . $url);
			}

			return $baseUrl . '/' . $url;

		}, $images);

		return $imageUrls;

	}


	public function getLocalFilePath($basePath, $feature, $imageUrl){

		$localFile=$this->getLocalFileBase($basePath, $feature).'/'.basename($imageUrl);
		return $localFile;
	}

	public function getLocalFileBase($basePath, $feature){

		$folder=$basePath;
		if(!file_exists($folder)){
			mkdir($folder);
		}
		$folder=$folder.'/layer-'.$feature->layerId;
		if(!file_exists($folder)){
			mkdir($folder);
		}
		$folder=$folder.'/item-'.$feature->id;
		if(!file_exists($folder)){
			mkdir($folder);
		}

		
		return $folder;
	}

	public function listExistingImages($basePath, $feature){
		$base=$this->getLocalFileBase($basePath, $feature);

		return array_map(function($path)use($base){
			return $base.'/'.$path;
		},array_values(array_filter(scandir($base), function($path){
			return strpos($path, '.')!==0;
		})));

	}


	public function getHeaders($url){

		$headers = array();

		foreach (get_headers($url) as $h) {
			$hParts = explode(':', $h, 2);
			if (count($hParts) == 2) {
				$headers[trim(strtolower($hParts[0]))] = trim($hParts[1]);
			}
		}
		return $headers;
	}

}