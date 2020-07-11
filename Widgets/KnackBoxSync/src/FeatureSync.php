<?php

namespace bcmt;

class FeatureSync {

	protected $map;
	protected $knack;

	protected $iconCache = array();

	public function __construct($map, $knack) {
		$this->map = $map;
		$this->knack = $knack;
	}

	public function fromFeature($feature) {

		if (!$this->hasFeature($feature->id)) {
			$this->map->removeFeature($feature->id);

			echo "Remove: " . print_r($feature, true) . "\n";
		}

	}

	public function fromRecord($record, $i) {

		echo 'record: ' . $record->id . "\n";

		$map = $this->map;

		$recordMap = array(
			'landTenure' => 'Land Tenure',
			'section' => 'Region',
			'paddlingArea' => 'Paddling Area',
			'siteUsage' => 'Site Usage',
			'siteFunction' => 'Site Function',
			'tentSites' => 'Tent Sites',
			'landingComments' => 'Landing Comments',
			'campComments' => 'Camp Comments',
			'otherComments' => 'Other Comments',
			'siteConditionNotes' => 'Site Condition Notes',
		);

		$attributeFields = array_keys($recordMap);
		$featureMap = array(
			'name' => 'title',
			'description' => 'Description',
			'layerId' => 'layer id from Geolive',
			'coordinates' => 'coordinates',
		);

		if (key_exists('id', $record) && empty($record->id)) {
			echo $record->knackid . ': Missing feature: ' . $record->id . "\n";

			$feature = (object) (new \Marker())->getMetadata();
			$attributes = array_combine($attributeFields, array_fill(0, count($attributeFields), ''));
			$record->id = 0;
		}

		if (!is_numeric($record->id)) {
			throw new \Exception('Not numeric id: ' . print_r($record, true));
		}

		try {
			$feature = $map->getFeature($record->id);
			$attributes = array();
			if (!empty($attributeFields)) {
				$attributes = $map->getFeatureAttributes($record->id, 'siteData', $attributeFields);
			}

		} catch (\Exception $e) {

			//error_log(print_r($e));
			echo $record->knackid . ': Missing feature: ' . $record->id . " " . $e->getMessage() . "\n";

			$feature = (object) (new \Marker())->getMetadata();
			$attributes = array_combine($attributeFields, array_fill(0, count($attributeFields), ''));

			$record->id = 0;

			$items = $map->findExistingByName($record->title);
			if (count($items) == 1) {
				if (!$this->hasFeature($items[0]->id)) {
					$feature = $items[0];
					echo 'Using feature: ' . $feature->id . ' ' . $feature->name;

					$knack = $this->knack;
					$knack->setRecordValues('mapitems', $record->knackid, array(
						'id' => $feature->id,
					), function ($record) {
						echo 'Fix Knack record: ' . $record->knackid . " id=" . $record->id . "\n";
						//$this->updateCache($record->id, $record);
					});

					$record->id = $feature->id;
				}
			}

			//exit('quit!!!');
			//return;
		}

		$featureDiff = array();
		$featurePrev = array();
		foreach ($featureMap as $featureName => $recordName) {

			if (!key_exists($featureName, $feature)) {
				throw new \Exception('Invalid field: ' . $featureName . ': ' . print_r($feature, true));
			}
			if (!key_exists($recordName, $record)) {
				throw new \Exception('Invalid field: ' . $recordName . ': ' . print_r($record, true));
			}

			if ($featureName == 'description') {
				$record->{$recordName} = $this->embedImages((new \bcmt\FieldFormatter($this))->formatKnackField($featureName, $record->{$recordName}), $feature);
			}

			$oldV = (new \bcmt\FieldFormatter($this))->formatFeatureField($featureName, $feature->{$featureName}, $feature);
			$newV = (new \bcmt\FieldFormatter($this))->formatKnackField($featureName, $record->{$recordName}, $record);

			if ($featureName == 'layerId') {
				$newIcon = $this->getIconFor($newV);
				//echo  $record->{$recordName}.": ".$newV.': '.$newIcon."\n";
				if ($feature->icon !== $newIcon) {
					$featureDiff['icon'] = $newIcon;
					$featurePrev['icon'] = $feature->icon;
				}
			}

			if ($oldV !== $newV) {

				try {
					(new \bcmt\FieldFormatter($this))->sanityCheck($featureName, $oldV, $newV);
				} catch (\Exception $e) {
					echo $record->knackid . ': ' . $e->getMessage() . "\n";
					continue;
				}

				$featureDiff[$featureName] = $newV;
				//$featureDiff[$featureName.'_']=$record->{$recordName};
				$featurePrev[$featureName] = $oldV;

			}

		}

		//if($feature->id===6532){
		if ($record->knackid == '5bec783593d20548a4905e71') {
			echo json_encode($feature, JSON_PRETTY_PRINT) . "\n";
			echo json_encode($record, JSON_PRETTY_PRINT) . "\n";
		}

		$attributeDiff = array();
		$attributePrev = array();
		foreach ($recordMap as $fieldName => $recordName) {

			if (!key_exists($fieldName, $attributes)) {
				throw new \Exception('Invalid field: ' . $fieldName . ': ' . print_r($attributes, true));
			}
			if (!key_exists($recordName, $record)) {
				throw new \Exception('Invalid field: ' . $recordName . ': ' . print_r($record, true));
			}

			$newV = (new \bcmt\FieldFormatter($this))->formatKnackField($fieldName, $record->{$recordName});
			$oldV = (new \bcmt\FieldFormatter($this))->formatAttributeField($fieldName, $attributes[$fieldName]);

			try {
				(new \bcmt\FieldFormatter($this))->sanityCheck($fieldName, $oldV, $newV);
			} catch (\Exception $e) {
				echo $record->knackid . ': ' . $e->getMessage() . "\n";
				continue;
			}

			if ($oldV !== $newV) {
				$attributeDiff[$fieldName] = $newV;
				//$attributeDiff[$fieldName.'_']=$record->{$recordName};
				$attributePrev[$fieldName] = $oldV;
			}
		}

		if ((!empty($attributeDiff)) || (!empty($featureDiff))) {

			//echo $i . ': ' . (print_r($feature, true)) . "\n\n";
			//echo $i . ': ' . (print_r($attributes, true)) . "\n\n";
			//echo $i . ': ' . (print_r($record, true)) . "\n\n";

			if (!empty($featureDiff)) {
				echo "\e[32m" . $i . ': feature new: ' . (json_encode($featureDiff)) . "\e[0m\n";
				echo "\e[31m" . $i . ': feature old: ' . (json_encode($featurePrev)) . "\e[0m\n";
			}

			if (!empty($attributeDiff)) {
				echo "\e[32m" . $i . ': attribute new: ' . (json_encode($attributeDiff)) . "\e[0m\n";
				echo "\e[31m" . $i . ': attribute old: ' . (json_encode($attributePrev)) . "\e[0m\n";
			}

			echo "\n\n\n";

			if (!empty($attributeDiff)) {
				$featureDiff['attributes'] = array('siteData' => $attributeDiff);
			}

			if ($record->id <= 0) {

				$knack = $this->knack;

				if ($knack->hasReachedLimit()) {
					echo $record->knackid . ': Cannot create anymore features today: ' . $record->id . " " . $e->getMessage() . "\n";
				}

				$id = $map->createFeature($featureDiff);
				if ($id <= 0) {
					echo $record->knackid . ': Failed to create feature: ' . $record->id . "\n";
					return;
				}
				$knack->setRecordValues('mapitems', $record->knackid, array(
					'id' => $id,
				), function ($record) {
					echo 'Updated Knack record: ' . $record->knackid . " id=" . $record->id . "\n";
					//$this->updateCache($record->id, $record);
				});

				$record->id = $id;

			} else {

				$map->setFeatureData($record->id, $featureDiff);
			}

		}

		$duplicates = $map->getDuplicates($record->id);

		if (count($duplicates)) {
			echo "duplicates: " . print_r($duplicates, true) . "\n";
			echo "original: " . print_r($feature, true) . "\n";

			foreach ($duplicates as $duplicate) {
				if (!$this->hasFeature($duplicate->id)) {
					echo 'remove: ' . print_r($duplicate, true) . "\n";
					$map->removeFeature($duplicate->id);
				} else {
					echo 'exists: ' . $duplicate->name . ' ' . $duplicate->id . ':' . print_r($this->searchFeature($duplicate->id), true) . "\n";
				}
			}
		}

	}

	protected static $cacheFeatures = array();

	protected function hasFeature($id) {
		return $this->searchFeature($id) !== false;
	}

	public function resetCache() {
		self::$cacheFeatures = null;
		return $this;
	}

	protected function searchFeature($id) {

		if (empty(self::$cacheFeatures)) {
			$this->knack->iterateRecords('mapitems', function ($record, $i) {
				if ($record->type !== "marker") {
					return;
				}
				self::$cacheFeatures[] = $record->id;
			});
		}

		return array_search($id, self::$cacheFeatures);

	}

	protected function getIconFor($layer) {

		if (key_exists($layer, $this->iconCache)) {
			return $this->iconCache[$layer];
		}

		$icon = $this->map->getIconForLayer($layer);
		//throw new \Exception("Failed to find Icon: ".$icon);
		$this->iconCache[$layer] = $icon;
		return $icon;

	}

	protected function embedImages($string, $feature) {

		preg_match_all("/<img[^>]+\>/i", $feature->description, $result);
		//echo print_r($result, true )."\n";

		return $string . implode('', $result[0]);
	}

	protected static $layerNamesMap = array();

	public function getLayerIdFromName($name) {

		if (!key_exists($name, self::$layerNamesMap)) {
			$layer = $this->map->getLayerFromName($name);
			if (!$layer) {
				throw new \Exception('Not valid layer: ' . $name);
			}
			self::$layerNamesMap[$name] = $layer->id;
		}

		return self::$layerNamesMap[$name];

	}

}