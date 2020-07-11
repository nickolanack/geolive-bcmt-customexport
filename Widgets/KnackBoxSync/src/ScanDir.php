<?php

namespace filesystem;

class ScanDir{

	protected $dir=null;
	protected $recursive=false;
	protected $ext=false;


	public function __construct($dir){
		$this->dir=$dir;
	}

	protected  function listFiles($dir){


		
		$list=array();
		foreach(scandir($dir) as $path){
			if($path==='.'||$path==='..'){
				continue;
			}

			if(is_dir($dir.'/'.$path)){
				$list=array_merge($list, $this->listFiles($dir.'/'.$path));
				continue;
			}


			if(!is_file($dir.'/'.$path)){
				continue;
			}

			if($this->ext&&strpos($path, $this->ext)!==strlen($path)-strlen($this->ext)){
				continue;
			}
				

			$list[]=$dir.'/'.$path;

		}

		return $list;


	}



	public function recursively(){
		$this->recursive=true;
		return $this;
	}

	public function withExt($ext){
		$this->ext=$ext;
		return $this;
	}

	public function list(){

		return $this->listFiles($this->dir);

	}



}




	


