<?php

class SitesTest extends PHPUnit_Framework_TestCase
{

    public function testSiteCount()
    {


    	include_once dirname(__DIR__).'/lib/GeoliveHelper.php';

    	include_once dirname(__DIR__).'/lib/AjaxRequest.php';


    	$access=array('public', 'special', 'site-planning');

    	$c=GeoliveHelper::CountSitesInAreas(array("Douglas Gardner"), $access);
    	$this->assertEquals(22, $c);
    	

    	$count=0;
    	GeoliveHelper::QueriedSiteListInAreas(array("Douglas Gardner"),function($i)use(&$count){
    		$count++;


    	}, $access);

    	$this->assertEquals(22, $count, GeoliveHelper::Database()->lastQuery());

	}
}