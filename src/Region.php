<?php
class Region 
{
	// everything is public so we can convert it to JSON
	public $rgName = null;
	public $image = null;
	public $areas = array();

	function __construct($rgName) {
		$this->rgName = $rgName;
		/* assign an image by region rgName:
			Central Cost - http://dev.bcmarinetrails.geolive.ca/images/stories/central-coast-paddling-areas.jpg
			Discovery & Mid Coast - http://dev.bcmarinetrails.geolive.ca/images/stories/discovery-desolation-paddling-areas.jpg
			Haida Gwaii - http://dev.bcmarinetrails.geolive.ca/images/stories/haida-gwaii-paddling-areas.jpg
			North Coast - http://dev.bcmarinetrails.geolive.ca/images/stories/north-coast-paddling-areas.jpg
			South Mainland - http://dev.bcmarinetrails.geolive.ca/images/stories/south-mainland-paddling-areas.jpg
			Vancouver Island - http://dev.bcmarinetrails.geolive.ca/images/stories/vancouver-island-paddling-areas.jpg
		*/
		if ($rgName == 'Central Coast') {
			$this->image = 'central-coast-paddling-areas.jpg';
		} elseif($rgName == 'Discoveries & Mid Coast') {
			$this->image = 'discovery-desolation-paddling-areas.jpg';
        } elseif($rgName == 'Haida Gwaii') {
            $this->image = 'haida-gwaii-paddling-areas.jpg';
        } elseif($rgName == 'North Coast') {
            $this->image = 'north-coast-paddling-areas.jpg';
        } elseif($rgName == 'South Coast Mainland') {
            $this->image = 'south-mainland-paddling-areas.jpg';
        } elseif($rgName == 'Vancouver Island') {
            $this->image = 'vancouver-island-paddling-areas.jpg';
        } 
	}
}
?>
