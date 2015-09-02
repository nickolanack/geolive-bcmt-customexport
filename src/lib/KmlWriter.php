<?php

class KmlWriter {
    private $dom;
    private $docNode;

    function __construct() {

        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $node = $this->dom->createElementNS('http://www.opengis.net/kml/2.2', 'kml');
        $parNode = $this->dom->appendChild($node);
        $dnode = $this->dom->createElement('Document');
        $this->docNode = $parNode->appendChild($dnode);
    }

    function writeKml($sitesArray) {

        foreach ($sitesArray as $site) {
            $coordArray = Util::parseCoords($site['coordinates']);
            $coordStr = $coordArray['lng'] . "," . $coordArray['lat'] . ",5.00";
            
            $node = $this->dom->createElement('Placemark');
            $placeNode = $this->docNode->appendChild($node);
            // $placeNode->setAttribute('id', 'placemark' . $row['id']);
            $nameNode = $this->dom->createElement('name', $site['name']);
            $placeNode->appendChild($nameNode);
            $descNode = $this->dom->createElement('description', 
                '<![CDATA[<b>Site Function: </b>' . $site['siteFunction'] . '<br/><br/><b>Landing Comments: </b>' .
                     $site['landingComments'] . '<br/><br/><b>Campsite Comments: </b>' . $site['campComments'] .
                     '<br/><br/><b>Number of Tent Sites: </b>' . $site['tentSites']);
            $placeNode->appendChild($descNode);
            $pointNode = $this->dom->createElement('Point');
            $placeNode->appendChild($pointNode);
            
            $altModeNode = $this->dom->createElement('altitudeMode', 'absolute'); // Sets the altitude of the coordinate
                                                                                  // relative to sea level
            $pointNode->appendChild($altModeNode);
            
            $coorNode = $this->dom->createElement('coordinates', $coordStr);
            $pointNode->appendChild($coorNode);
        }
        
        return $this->dom->saveXML();
    }
}

?>