<?php

namespace bcmt;

class ExifData{

	protected $exif;
	
	public function __construct($file){

		$this->exif=exif_read_data($file);
		print_r($this->exif);

	}

	public function getTags(){

		$tags=array();
		if(key_exists('Artist', $this->exif)&&!empty($this->exif['Artist'])){
			$tags[]=$this->exif['Artist'];
		}

		return $tags;

	}

}