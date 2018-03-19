<?php


class PaddlingAreasStandaloneAjaxController extends core\AjaxController implements core\WidgetMember{
	

	use core\WidgetMemberTrait;


	private function getToolPath(){

		$path=dirname(dirname($this->getPath())).'/src/siteSearch.php';
		if(file_exists($path)){
			return $path;
		}

		$pathLive=GetPath('{front}/ext/siteSearch.php');

		if(file_exists($pathLive)){
			return $pathLive;
		}
		

		throw new Exception(dirname(dirname($this->getPath())).'/src/siteSearch.php');

	}

	protected function countSites($task, $json){
		include $this->getToolPath();
	}

	protected function listSites($task, $json){
		include $this->getToolPath();
	}

	protected function siteArticles($task, $json){
		include $this->getToolPath();
	}
	protected function export($task, $json){
		include $this->getToolPath();
	}


}