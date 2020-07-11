<?php

namespace bcmt;

class KnackSync {

	private static $nameMap=array();

	public function checkDuplicates($record, $i) {

		if(!key_exists($record->title, self::$nameMap)){
			self::$nameMap[$record->title]=array();
		}

		self::$nameMap[$record->title][]=$record;

		if(count(self::$nameMap[$record->title])>1){


			echo "Found duplicate:\n";

			if(count(self::$nameMap[$record->title])==2){
				//$this->print(self::$nameMap[$record->title][0]);
			}

			//$this->print(self::$nameMap[$record->title][count(self::$nameMap[$record->title])-1]);

		}




	}


	public function printDuplicates(){

		foreach (self::$nameMap as $title=>$items) {
			if(count($items)<2){
				continue;
			}
			echo "Found duplicate: ".$title."\n";
			foreach( $items as $item) {
				$this->print($item);
			}
		}
	}

	protected function print($record){

			echo $record->knackid.': '$record->id.': '.$record->title.": ".$record->coordinates."\n";

	}
}


		