<?php

namespace bcmt;

class Sync {

	protected $knack;
	protected $box;
	protected $map;
	protected $counter = 0;

	protected $siteUrl;
	protected $skipDir;

	protected $boxRootFolder;

	protected $handlers = array();
	protected $countPhotoUrlUpdates = 0;

	protected $knackIdFilter = null;

	public function addEventHandler($handler) {
		$this->handlers[] = $handler;
		return $this;
	}

	public function triggerEvent($event, $data) {
		foreach ($this->handlers as $handler) {
			$handler($event, $data);
		}
		return $this;
	}

	public function setKnackClient($knack) {
		$this->knack = $knack;
		return $this;
	}
	public function setBoxClient($box) {
		$this->box = $box;

		return $this;
	}
	public function setMapClient($map) {
		$this->map = $map;
		return $this;
	}

	public function setSiteUrl($siteUrl) {
		$this->siteUrl = $siteUrl;
		return $this;
	}
	public function cacheProgressTo($skipDir) {
		$this->skipDir = $skipDir;
		return $this;
	}

	public function getItemsProcessed() {
		return $this->counter;
	}

	public function resetDailyCache($ttl = 300) {

		$this->knack->resetDailyCache($ttl);
		return $this;
	}

	protected function getKnackItem($id) {

		$item = null;
		$this->knack->iterateRecords('mapitems', function ($record, $i) use ($id, &$item) {
			if ($record->knackid !== $id) {
				return;
			}
			$item = $record;
		});

		if (is_null($item)) {
			echo "getKnackItem(" . $id . ") not found\n";
		}

		return $item;
	}

	protected function iterateKnackItems($callback, $name, $longTaskProgress = null) {

		if (!$longTaskProgress) {

			$this->knack->iterateRecords('mapitems', function ($record, $i) use (&$callback) {

				if($record->title=="Fearney Point northwest"){
					print_r($record);
				}

				if ($record->type !== "marker") {

					if (!in_array($record->type, array('line', 'polygon'))) {
						$this->triggerEvent('recordError', array(
							'message' => 'Record has invalid type (`' . $record->type . '`) and will be ignored',
							'record' => $record,
						));
					}

					echo 'skip record: ' . $record->id . ' (' . $record->type . ')' . "\n";

					return;
				}
				$callback($record, $i);
			});

			return;
		}

		$list = array();
		$this->knack->iterateRecords('mapitems', function ($record, $i) use (&$list) {
			if ($record->type !== "marker") {

				if (!in_array($record->type, array('line', 'polygon'))) {
					$this->triggerEvent('recordError', array(
						'message' => 'Record has invalid type (`' . $record->type . '`) and will be ignored',
						'record' => $record,
					));
				}

				echo 'skip record: ' . $record->id . ' (' . $record->type . ')' . "\n";

				return;
			}
			$list[] = array($record, $i);

		});

		$longTaskProgress->iterateTaskActivity($name, $list, function ($c) use ($callback) {
			$record = $c[0];
			$i = $c[1];
			$callback($record, $i);
		});
	}

	public function importKnackRecords($longTaskProgress = null) {

		$this->iterateKnackItems(function ($record, $i) {

			//dont need to do anything as this is just triggering a cache reset (or not depending on age of cache)

		}, 'Caching Knack changes', $longTaskProgress);

		return $this;

	}

	public function initBoxFolders($longTaskProgress = null) {

		$this->iterateKnackItems(function ($record, $i) {
			$this->initializeBoxFolders($record, $i);
		}, 'Initialize Box folders', $longTaskProgress);

		return $this;

	}

	protected function scanBoxChanges($callback) {

		$syncFolders = array();
		$box = $this->box;

		$box->getEvents(function ($data) use ($box, &$syncFolders, $callback) {

			//print_r($data);

			if ($data->source->type === 'folder') {

				$path = $data->source->path_collection->entries;

				if (count($path) && (array_pop($path)->name == 'geolive-site-images')) {

					$syncFolders[$data->source->id] = $data->source->name;
					// print_r($data);

				}
			}

			if ($data->source->type === 'file') {

				$path = $data->source->path_collection->entries;
				$folder = array_pop($path);
				if (count($path) && (array_pop($path)->name == 'geolive-site-images')) {

					if (!key_exists($folder->id, $syncFolders)) {
						$callback($folder->id, $folder->name);
					}

					$syncFolders[$folder->id] = $folder->name;
					// print_r($data);
				}

			}
			foreach ($syncFolders as $id => $name) {
				$callback($id, $name);
			}

		});

	}

	protected function iterateBoxChanges($callback, $name, $longTaskProgress = null) {

		if (!$longTaskProgress) {

			$this->scanBoxChanges(function ($id, $folder) use (&$callback) {
				$callback($id, $folder);
			});

			return;
		}

		$list = array();
		$this->scanBoxChanges(function ($id, $folder) use (&$callback, &$list) {

			$list[] = array($id, $folder);

		});

		$longTaskProgress->iterateTaskActivity($name, $list, function ($c) use ($callback) {
			$id = $c[0];
			$folder = $c[1];
			$callback($id, $folder);
		});

	}

	protected function iterateFeatures($callback, $name, $longTaskProgress = null) {

		if (!$longTaskProgress) {

			foreach (array(1, 2, 3, 4, 6, 7, 8) as $layer) {
				foreach ($this->map->getFeatures($layer) as $feature) {
					if($feature->type!=='marker'){
						return;
					}
					$callback($feature, $feature->id);
				}
			}

			return;
		}

		$list = array();
		foreach (array(1, 2, 3, 4, 6, 7, 8) as $layer) {
			foreach ($this->map->getFeatures($layer) as $feature) {
				if($feature->type!=='marker'){
					return;
				}
				$list[] = array($feature, $feature->id);
			}
		}

		$longTaskProgress->iterateTaskActivity($name, $list, function ($c) use ($callback) {
			$id = $c[1];
			$feature = $c[0];
			$callback($feature, $id);
		});

	}

	public function withKnackIdFilter($filter) {

		$this->knackIdFilter = $filter;

		return $this;
	}

	protected function inFilter($id) {

		$filter = $this->knackIdFilter;
		if (is_array($filter)) {
			foreach ($filter as $filterItem) {
				if ($id == $filterItem->id) {
					return true;
				}
			}
		}
		return false;
	}

	public function syncAllBoxFolders($longTaskProgress = null) {

		$this->iterateKnackItems(function ($record, $i) {

			// if($record->id!=7461){
			// 	return;
			// }

			if (!(is_object($record->{'Photo link'}) && key_exists('url', $record->{'Photo link'}))) {
				//print_r($record);
				//throw new \Exception("Missing box photo-url");
				//
				$this->triggerEvent('recordError', array(
					'message' => 'Missing box photo-url (`' . $record->type . '`) and will be ignored',
					'record' => $record,
				));
				return;
			}

			$boxUrl = $record->{'Photo link'}->url;
			$id = explode('/', $record->{'Photo link'}->url);
			$id = array_pop($id);
			$folder = $record->knackid;
			$feature = $this->map->getFeature($record->id);

			$this->syncBoxFolder($id, $folder, $feature);

		}, 'Sync all box folders', $longTaskProgress);

		return $this;

	}

	public function syncBoxChanges($longTaskProgress = null) {

		echo "sync box changes\n";

		$this->iterateBoxChanges(function ($id, $folder) {
			try {

				echo "sync: " . $folder . ' - ' . $id . "\n";

				$record = $this->getKnackItem($folder);
				$feature = $this->map->getFeature($record->id);

				$this->syncBoxFolder($id, $folder, $feature);

			} catch (\Exception $e) {
				error_log($e->getMessage());

				$this->triggerEvent('syncError', array(
					'message' => $e->getMessage(),
				));

			}

		}, 'Sync box folders', $longTaskProgress);

		return $this;

	}

	public function syncBoxFolder($id, $folder, $feature) {

		return (new \bcmt\BoxSync($this->box, $this->map))
			->setSiteUrl($this->siteUrl)
			->syncFolder($id, $folder, $feature);

	}

	public function syncKnackUrls($longTaskProgress = null) {

		$this->iterateKnackItems(function ($record, $i) {
			$this->updateKnackPhotoUrl($record, $i);
		}, 'Update knack photo urls', $longTaskProgress);

		return $this;

	}

	public function syncMapitems($longTaskProgress = null) {

		$this->iterateKnackItems(function ($record, $i) {

			try {
				(new \bcmt\FeatureSync($this->map, $this->knack))->fromRecord($record, $i);
			} catch (\Exception $e) {
				echo $e->getMessage();

				$this->triggerEvent('syncError', array(
					'message' => $e->getMessage(),
				));
			}
		}, 'Sync map item data', $longTaskProgress);

		return $this;

	}

	public function removeFeatures($longTaskProgress = null) {

		echo "remove duplicates\n";

		// clear knack cache otherwise new items will get deleted
        $this->resetDailyCache(0);
		$featureSync=(new \bcmt\FeatureSync($this->map, $this->knack));
		$featureSync->resetCache();
		$this->iterateFeatures(function ($feature, $id) use($featureSync){
			$featureSync->fromFeature($feature, $id);
		}, 'Removing duplicates', $longTaskProgress);

		return $this;

	}

	public function checkDuplicates($longTaskProgress = null) {

		$this->iterateKnackItems(function ($record, $i) {
			(new \bcmt\KnackSync())->checkDuplicates($record, $i);
		}, 'Sync map item data', $longTaskProgress);

		(new \bcmt\KnackSync())->printDuplicates();

		return $this;

	}

	public function syncBoxRootFolder($folder) {
		$this->boxRootFolder = $folder;

		$box = $this->box;
		$boxRootFolder = $folder; //73221484385
		if (!$box->hasPath($boxRootFolder)) {
			$box->makeFolder($boxRootFolder);
		}

		return $this;
	}

	public function setBoxCollaborators() {

		//$box->addCollaborator($boxRoot, 7365369423);

		return $this;
	}

	protected function initializeBoxFolders($record, $i) {

		return;

		$boxRoot = $this->boxRootFolder; //73221484385

		$box = $this->box;
		$map = $this->map;
		$knack = $this->knack;
		$siteUrl = $this->siteUrl;
		$skipDir = $this->skipDir;

		//echo $i.': '.substr(print_r($record, true), 0, 75)."\n\n";

		$this->counter++;
		$cachePath = false;

		if ($skipDir) {
			$cachePath = $skipDir . '/knack-' . $record->knackid . '.json';
		}

		$cacheData = array();

		$boxPath = $boxRoot . '/' . $record->knackid;
		$box->makeFolder($boxPath);

		//echo $i.': '.substr(print_r($record->{'Photo link'}, true), 0, 75)."\n\n";

		if (file_exists($cachePath)) {
			echo "Box: Skipping item found in cache. " . $box->getFolderId($boxPath) . ": " . $cachePath . "\n";
			return;
		}

		$tags = array('gid-' . $record->id, 'title: ' . $record->title, 'layer: ' . $record->{'layer title'});
		$box->setFolderTags($boxPath, $tags);

		try {
			$feature = $map->getFeature($record->id);
			echo $i . ': ' . substr(print_r($feature, true), 0, 75) . "\n\n";
		} catch (\Exception $e) {
			//error_log(print_r($e));
			echo "Missing feature: " . $record->id . "\n";
			return;
		}

		$this->syncBoxImagesFromMap($record, $feature);

		$imageUrls = (new \bcmt\ImageParser())->urlsFromHtml($feature->description, $siteUrl);

		$cacheData = array(
			'geolive' => $feature->id,
			'knack' => $record->id,
			'box' => $box->getFolderId($boxPath),
			'tags' => $tags,
			'countImages' => count($imageUrls),
		);

		file_put_contents($cachePath, json_encode($cacheData, JSON_PRETTY_PRINT));

	}

	protected function syncBoxImagesFromMap($record, $feature) {

		$siteUrl = $this->siteUrl;
		$imageUrls = (new \bcmt\ImageParser())->urlsFromHtml($feature->description, $siteUrl);
		$box = $this->box;

		$boxRoot = $this->boxRootFolder; //73221484385
		$boxPath = $boxRoot . '/' . $record->knackid;

		foreach ($imageUrls as $imageIndex => $imageUrl) {

			$localFileFullRes = (new \bcmt\ImageParser())->getLocalFilePath(__DIR__ . '/images', $feature, $imageUrl);

			if (!file_exists($localFileFullRes)) {

				echo "Importing Full Ress File: " . $imageUrl . " -> " . $localFileFullRes . "\n";
				file_put_contents($localFileFullRes, file_get_contents($imageUrl));
			}

			//$exif=new \bcmt\ExifData($localFileFullRes);

			$tags = array('geolive');
			$filename = basename($localFileFullRes);
			$filename = ($imageIndex + 1) . substr($filename, strrpos($filename, '.'));

			if (!$box->hasFile($boxPath, $filename)) {
				$box->uploadFile($boxPath, $localFileFullRes, $filename);
				//if(count($tags)){

				//}
			}
			$box->setFileTags($boxPath . '/' . $filename, $tags);

		}

	}

	protected function updateKnackPhotoUrl($record) {

		$boxRoot = $this->boxRootFolder; //73221484385
		$box = $this->box;
		$knack = $this->knack;

		$boxPath = $boxRoot . '/' . $record->knackid;

		$folderId = $box->getFolderId($boxPath);
		if ($folderId <= 0) {
			$folderId = $box->makeFolder($boxPath);
		}

		$boxUrl = 'https://bcmarinetrailsnetworkassoc.app.box.com/folder/' . $folderId;

		$photoLink = $record->{'Photo link'};

		if (is_object($photoLink)) {
			$photoLink = $photoLink->url;
		}
		//echo $boxUrl.":  ".$photoLink."\n";
		if ($photoLink !== $boxUrl) {

			if (!$knack->hasReachedLimit()) {

				$this->countPhotoUrlUpdates++;

				$knack->setRecordValues('mapitems', $record->knackid, array(
					'Photo link' => $boxUrl,
				), function ($record) {
					echo 'update photo link: ' . print_r($record, true) . "\n";
				});
			} else {

				echo 'Knack: Reached max api calls today. ' . $record->knackid . ": " . $photoLink . ": " . $boxUrl . "\n";
			}
		}

	}

}