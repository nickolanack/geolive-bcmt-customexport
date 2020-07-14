<?php

namespace bcmt;




class LocalClient{

	public function __construct(){
		GetPlugin('Maps');
		GetPlugin('Attributes');
	}

	public function getApiCallCount(){
		return 0;
	}

	public function getFeature($id){
		return json_decode(json_encode((new \spatial\FeatureLoader())->fromId($id)->getMetadata()));
	}

	
	public function getFeatures($layer){



		return json_decode(json_encode(
			(new \spatial\Features())
				->listLayerFeatures($layer)
                ->get()));
	}

	public function getLayerFromName($name){
	

		$layer=(new \spatial\LayerLoader())->fromName($name);
		if(!$layer){
			//throw new \Exception('Failed to load layer with name: `'.$name.'`');
			$layer=(new \spatial\LayerLoader())->fromName('Planning Layer');
		}

		return json_decode(json_encode($layer->getMetadata()));

		
		
	}


	public function getLayerMetadata($mapId){

		return json_decode(json_encode(array_map(function ($layerId) {
			return (new \spatial\LayerLoader())->fromId($layerId)->getMetadata();
		}, (new \spatial\MapLoader())->fromId($json->mapId)->getLayerIds())));

	}

	public function uploadImage($file, $name){

		$suported = array('jpg', 'png');
		$ext=explode('.', $name);
		$ext=strtolower(array_pop($name));

		if(!in_array($ext,$suported)){
			throw new \Exception("only supports ".json_encode($suported).": ".$ext);
		}

		$fileData = array(
				'number' => 1,
				'totalSize' => filesize($file),
				'files' => array(
					array(
						'ext' => $ext,
						'name' => $name,
						'type' => 'image/'.$ext,
						'tmp_name' => $file,
						'error' => 0,
					),
				),
			);

		$share=GetUserFiles()->getFileManager()->getCurrentUserShare();
		if($share->canStoreFiles($fileData)){
			$path=$share->storeFiles($fileData);
			return (new \Filesystem\FileMetadata())->getMetadata($path[0]);
		}
	}

	public function findFile($filter){

		if(key_exists('sha1', $filter)){
			$result=GetUserFiles()->getFileManager()->findFileWithSha1($filter['sha1']);
			$metadata=null;
			if(is_object($result)){
				$metadata=$result->metadata;
			}
						
			if(!empty($metadata)){
				return $metadata;
			}
		}
		if(key_exists('name', $filter)){
			$data=GetUserFiles()->getFileManager()->findFileWithBasename($filter['name']);
			if(!$data){
				error_log('Missing file with name: '.json_encode($filter['name']));
			}
			if($data&&!key_exists('metadata', $data)){
				error_log('Missing key metadata: '.json_encode($data));
			}
			return $data->metadata;
		}

	}


	public function getFeatureAttributes($id, $table, $fields){

		return array_diff_key((new \attributes\Record($table))->getFieldValues($id, 'marker', $fields), array('id'=>''));

	}



	public function createFeature($data){

		$feature=new \Marker();
		echo "\e[34mCreate New Feature\e[0m\n";
		return $this->save($feature, $data);

	}

	public function setFeatureData($id, $data){

		$feature=(new \spatial\FeatureLoader())->fromId($id);
		$this->save($feature, $data);

		return $this;

	}

	public function getDuplicates($id){
		$feature=(new \spatial\FeatureLoader())->fromId($id);
		$features=(new \spatial\FeatureLoader())->featuresWithName($feature->getName());

		return array_map(function($item){
				return (object) $item->getMetadata();
			},array_values(array_filter($features, function($item)use($feature){
				return $feature->getId()!==$item->getId();
		})));
	}


	public function findExistingByName($name){
		
		$features=(new \spatial\FeatureLoader())->featuresWithName($name);

		return array_map(function($item){
			return (object) $item->getMetadata();
		}, $features);
	}


	public function removeFeature($id){
		$feature=(new \spatial\FeatureLoader())->fromId($id);
		(new \spatial\FeatureLoader())->delete($feature);
	}


	public function getIconForLayer($layer){
		GetPlugin('Maps');

		$iconset=GetWidget(2)->getLayerMap();
		$value=array_search($layer, $iconset);

		if(strpos($value, 'administrator/components')===0){
			$value=str_replace('administrator/components', 'components', $value);
		}


		return $value;

		// if(strpos($value, 'http')===0){
		// 	return $value;
		// }
		// return RelativeUrlFrom(UrlFrom($value));
		// exit();

		// $icons= (new \spatial\Layers())->getDistinctIcons($layer);
		// return $icons[0];
	}

	protected function save($feature, $data){

		if(key_exists('name', $data)){
			$feature->setName($data['name']);
		}
		if(key_exists('description', $data)){
			$feature->setDescription($data['description']);
		}

		if(key_exists('coordinates', $data)&&!empty($data['coordinates'])){
			$c=explode(',', $data['coordinates']);
			if(count($c)<2){
				throw new \Exception("Invalid Coordinates: ".$data['coordinates']);
			}
			$feature->setCoordinates(floatval(trim($c[0])), floatval(trim($c[1])));
		}

		if($feature->getLayerId()==-1){
			$feature->setLayerId(6);
		}

		if(key_exists('layerId', $data)){
			$feature->setLayerId($data['layerId']);
		}


		if(key_exists('icon', $data)){
			$feature->setIcon($data['icon']);
		}

		echo "\e[34m".json_encode($feature->getMetadata())."\e[0m\n";
		echo "\e[34m".json_encode($data)."\e[0m\n";


		$id=-1;
		$id=(new \spatial\FeatureLoader())->saveWithMessage($feature, array("updateItemMessage"=>"Update item data from knack"));
		
		if(key_exists('attributes', $data)){
			foreach($data['attributes'] as $table=>$values){
				(new \attributes\Record($table))->setValues($id, 'marker', $values);
			}
		}

		echo "save feature id: ".$id."\n";

		return $id;

		//exit();
	}

	


}