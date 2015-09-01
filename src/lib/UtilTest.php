<?php class UtilTest extends PHPUnit_Framework_TestCase{




	public function testParseCoords(){
	

		include_once 'Util.php';

		// deprecated coordinate storage format
		$c0='{"coordinates":[[49.965,-125.20805]]}';
		
		// new coordinate storage format, a single array, with lat, lng and elevation,
                // all coordinates will be updated to this format
		$c1='{"coordinates":[50.372,-124.6705,0]}';

		$this->assertEquals(array(
			'lat' => 49.965,
			'lng' => -125.20805
	
		), Util::ParseCoords($c0));
		$this->assertEquals(array(
			'lat' => 50.372,
                        'lng' => -124.6705
		
		), Util::ParseCoords($c1));
	

	}


}
