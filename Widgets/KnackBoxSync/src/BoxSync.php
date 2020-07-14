<?php

namespace bcmt;

class BoxSync {

	private $box;
	private $map;
	private $siteUrlsiteUrl;

	public function __construct($box, $map) {

		$this->box = $box;
		$this->map = $map;

	}
	public function setSiteUrl($siteUrl) {
		$this->siteUrl = $siteUrl;
		return $this;
	}
	public function syncFolder($id, $folder, $feature) {

		$boxItems = $this->box->listItems($id, array('name', 'tags', 'sha1'));

		$boxItems=array_values(array_filter($boxItems, function($item){

			$ext=explode('.',$item->name);
			$ext=strtolower(array_pop($ext));
			if(!in_array($ext, array('jpg', 'png'))){
				return false;
			}

			if($ext!='jpg'){
				error_log('Non jpg: '.$item->name);
			}

			return true;

		}));

		$mapSha1s = $this->getFeatureSha1s($feature);
		$allBoxSha1s =$this->getBoxSha1s($boxItems);
		$boxSha1s = $this->getTaggedBoxSha1s($boxItems);	


		error_log(print_r($boxItems, true));

		// echo json_encode($boxSha1s)."\n";
		// echo json_encode($mapSha1s)."\n";

		if (json_encode($mapSha1s) !== json_encode($boxSha1s)) {

			$existingImages = $this->getExistingImages($feature, $boxSha1s, $mapSha1s);
			
			$boxUrl = 'https://bcmarinetrailsnetworkassoc.app.box.com/folder/' . $id;
			
			// error_log( $boxUrl);
			// error_log( 'map: ' . print_r($mapSha1s, true) . "\n");
			// error_log( 'box: ' . print_r($boxSha1s, true) . "\n");
			// error_log( $id . print_r($boxItems, true) . "\n");

			$existingImages = array_intersect_key($existingImages, array_combine($boxSha1s, $boxSha1s));

			//if (count($existingImages) != count($boxSha1s)) {
				$diff = array_diff_key($existingImages, array_combine($boxSha1s, $boxSha1s));
				$new = array_diff_key(array_combine($boxSha1s, $boxSha1s), $existingImages);


				if(!empty($new)){
					$pulledImages=$this->pullImagesFromBox($new, $boxItems, $feature);
					$existingImages=array_merge($existingImages, $pulledImages);
					echo "pulled: ".print_r($pulledImages, true)."\n";
				}

				if(!empty($diff)){
					$this->pushImagesToBox($diff, $existingImages, $feature);
				}


			//}

			$sortedImages = array();
			foreach ($boxSha1s as $sha1) {
				if(!key_exists( $sha1, $existingImages)){
					//throw new \Exception('Missing: '.$sha1.' in existing: '.json_encode($feature));
					
					error_log('Missing: '.$sha1.' in existing: '.json_encode($feature).json_encode($this->boxItemWithSha1($boxItems, $sha1)));
					continue;
				}
				$sortedImages[] = $existingImages[$sha1];
			}

			$description = $this->stripImages($feature->description) . implode('', $sortedImages);

			error_log( 'description: ' . print_r($description, true) . "\n");
			$this->map->setFeatureData($feature->id, array(
				'description' => $description,
			));

			error_log( "\n");

		}

	}

	protected function pullImagesFromBox($new, $boxItems, $feature){

		$images=array();

		foreach($new as $sha1){
			foreach($boxItems as $item){
				if($item->sha1===$sha1){

					$metadata = $this->map->findFile(array(
						'name' => $item->name,
						'sha1' => $sha1
					));

					if($metadata){
						if(!$metadata->html){
							error_log("No html in metadata: ".json_encode($item).json_encode($metadata));
							continue;
						}
						$images[$sha1]=$metadata->html;
						continue;
					}


					$file= tempnam(__DIR__, '_box');
					file_put_contents($file, $this->box->getFile($item->id));
					echo $file."\n";

					$metadata=$this->map->uploadImage($file, $item->name);

					if(empty($metadata)||(!key_exists('html', $metadata))){
						//throw new \Exception('missing html: '.json_encode($metadata));
						error_log('missing html: '.json_encode($metadata));
					}else{
						$images[$sha1]=$metadata->html;
					}

					try{
						unlink($file);

							
					}catch(\Exception $e){
						error_log($e->getMessage());
					}

					
				}
			}
		}
		return $images;
	
	}
	protected function pushImagesToBox($diff, $existingImages, $feature){
		throw new \Exception('not implemented');
	}


	protected function getExistingImages($feature, $boxSha1s, $mapSha1s){
		$existingImages = array();
		foreach ((new \bcmt\ImageParser())->listExistingImages(__DIR__ . '/images', $feature) as $file) {
			$localSha = sha1_file($file);
			if (in_array($localSha, $boxSha1s) && (!in_array($localSha, $mapSha1s))) {
				echo 'Found missing file: ' . $file . "\n";

				$metadata = $this->map->findFile(array(
					'name' => basename($file),
					'sha1' => $localSha,
				));

				if (empty($metadata)) {
					throw new \Exception('File not found! ' . $file);
				}

				echo 'File metadata: ' . print_r($metadata->html, true) . "\n";

				$existingImages[$localSha] = $metadata->html;

			}
		}
		return $existingImages;
	}

	protected function boxItemWithSha1($boxItems, $sha1){
		$matches = array_filter($boxItems, function ($item) use($sha1){
			//error_log(gettype($item).json_encode($item));
			return $item->sha1===$sha1;
		});

		return empty($matches)?null:array_shift($matches);
	}

	protected function getTaggedBoxSha1s($boxItems){

		return $this->getBoxSha1s(array_filter($boxItems, function ($item) {
			return in_array('geolive', $item->tags);
		}));


	}

	protected function getBoxSha1s($boxItems){
		$boxSha1s = array_map(function ($item) {
			return $item->sha1;
		}, $boxItems);
		return $boxSha1s;
	}

	protected function getFeatureSha1s($feature) {

		$imageUrls = (new \bcmt\ImageParser())->urlsFromHtml($feature->description, $this->siteUrl);
		$imagePaths = array();
		$mapSha1s = array();

		foreach ($imageUrls as $imageIndex => $imageUrl) {
			$localFileFullRes = (new \bcmt\ImageParser())->getLocalFilePath(__DIR__ . '/images', $feature, $imageUrl);
			if (!file_exists($localFileFullRes)) {

				echo "Importing Full Ress File: " . $imageUrl . " -> " . $localFileFullRes . "\n";
				file_put_contents($localFileFullRes, file_get_contents($imageUrl));
			}
			$imagePaths[] = $localFileFullRes;
			$sha1 = sha1_file($localFileFullRes);

			if ($sha1 === "da39a3ee5e6b4b0d3255bfef95601890afd80709") {
				echo "Re-Importing Full Ress File: " . $imageUrl . " -> " . $localFileFullRes . "\n";
				file_put_contents($localFileFullRes, file_get_contents($imageUrl));
				$sha1 = sha1_file($localFileFullRes);

			}

			$mapSha1s[] = $sha1;

		}

		return $mapSha1s;

	}

	protected function stripImages($string) {
		return trim(preg_replace("/<img[^>]+\>/i", "", $string));
	}
}
