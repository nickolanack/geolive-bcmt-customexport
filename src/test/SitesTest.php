<?php

class SitesTest extends PHPUnit_Framework_TestCase
{

    public function testSiteCount()
    {


    	include_once dirname(__DIR__).'/lib/GeoliveHelper.php';

    	include_once dirname(__DIR__).'/lib/AjaxRequest.php';


    	

    	$c=GeoliveHelper::CountSitesInAreas(array("Douglas Gardner"), array('public', 'special', 'site-planning'));
    	$this->assertEquals(22, $c);
    	

	}
}