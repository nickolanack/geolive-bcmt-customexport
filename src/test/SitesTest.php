<?php

class SitesTest extends PHPUnit_Framework_TestCase
{

    public function testSiteCount()
    {


    	include_once dirname(__DIR__).'/lib/GeoliveHelper.php';

    	include_once dirname(__DIR__).'/lib/AjaxRequest.php';


    	$args=json_decode('{"paddlingAreas":["Douglas Gardner"],"layers":"*","region":"North Coast"}');

    	$c=GeoliveHelper::CountSitesInAreas(array("Douglas Gardner"));

    	$this->markTestIncomplete('Count: '.$c);

	}
}