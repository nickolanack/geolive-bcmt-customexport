<?php

namespace bcmt;




class Client{

	protected $client;

	public function __construct($url, $user, $pass){

		$this->client = new \coreclient\Client($url, $user, $pass);

	}

	public function getApiCallCount(){
		return $this->client->getApiCallCount();
	}

	public function getFeature($id){
		$feature = json_decode($resp=$this->client->ajax('get_map_item', array(
			"id"=>$id
		), "Maps"));


		if(!is_object($feature)){
			throw new \Exception("Not an object response: ".$resp);	
		}

		return $feature->result;
	}

	
	public function getFeatures($layer){
		$features = json_decode($resp=$this->client->ajax('layer_display', array(
			"layerId" => $layer,
			"format" => "json",
		), "Maps"));


		if(!is_object($features)){
			throw new \Exception("Not an object response: ".$resp);	
		}

		return $features->items;
	}



	public function getLayerMetadata($mapId){

		$layersData=array();

		foreach(json_decode($resp=$this->client->ajax('map_layers_metadata', array(
			"mapId" => $mapId
		)))->layers as $layerMeta){
			$layersData[$layerMeta->id]=$layerMeta;
		}

		return $layersData;

	}


	public function getFeatureAttributes($id, $table, $fields){

		$attributes = json_decode($resp=$this->client->ajax('get_attribute_value_list', array(
			"itemId"=>$id, "itemType"=>"marker", "filters"=>array("table"=>$table,"fields"=>$fields)
		), "Attributes"));

		if(!is_object($attributes)){
			throw new \Exception("Not an object response: ".$resp);			
		}
		if($attributes->values){
			return array_diff_key(get_object_vars($attributes->values[0]->entries[0]), array('id'=>''));
		}

		return array();

	}


}