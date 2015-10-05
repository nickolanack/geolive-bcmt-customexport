<?php

class KmlWriter {
    private $dom;
    private $docNode;
    private $styles = array();

    function __construct() {

        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $node = $this->dom->createElementNS('http://www.opengis.net/kml/2.2', 'kml');
        $parNode = $this->dom->appendChild($node);
        $dnode = $this->dom->createElement('Document');
        $this->docNode = $parNode->appendChild($dnode);
    }

    function getStyles() {

        return $this->styles;
    }

    function writeKml($sitesArray) {

//         print_r('hello world ' . count($sitesArray));
//         echo '<pre>';
        foreach ($sitesArray as $site) {
            $coordArray = Util::parseCoords($site['coordinates']);
            $coordStr = $coordArray['lng'] . "," . $coordArray['lat'] . ",5.00";

            $icon = urldecode($site['style']);
            $iconName = substr($icon, strrpos($icon, '/') + 1);
            $styleName = substr($iconName, 0, strpos($iconName, '.'));

            if (!in_array($styleName, $this->styles)) {

//                 print_r(
//                     array(
//                         $icon,
//                         $styleName,
//                         $iconName
//                     ));
//                 echo "\n";

                if (!file_exists(dirname(__DIR__) . DS . $iconName)) {
                    file_get_contents($site['style']);
                    file_put_contents(dirname(__DIR__) . DS . $iconName, file_get_contents($site['style']));
                }
                $this->styles[$styleName] = $iconName;

                $style = $this->dom->createElement('Style');
                $style->setAttribute('id', $styleName);
                $iconstyle = $this->dom->createElement('IconStyle');

                $icon = $this->dom->createElement('Icon');
                $href = $this->dom->createElement('href', $iconName);

                $icon->appendChild($href);
                $iconstyle->appendChild($icon);
                $style->appendChild($iconstyle);
                $this->docNode->appendChild($style);
            }

            $node = $this->dom->createElement('Placemark');
            $placeNode = $this->docNode->appendChild($node);
            // $placeNode->setAttribute('id', 'placemark' . $row['id']);
            $nameNode = $this->dom->createElement('name', $site['name']);
            $placeNode->appendChild($nameNode);

            $descNode = $this->dom->createElement('description');
            $descNode->appendChild(
                $this->dom->createCDATASection(
                    '<b>Site Function: </b>' . $site['siteFunction'] . '<br/><br/><b>Landing Comments: </b>' .
                         $site['landingComments'] . '<br/><br/><b>Campsite Comments: </b>' . $site['campComments'] .
                         '<br/><br/><b>Number of Tent Sites: </b>' . $site['tentSites']));
            $placeNode->appendChild($descNode);

            $placeNode->appendChild($this->dom->createElement('styleUrl', '#' . $styleName));

            $pointNode = $this->dom->createElement('Point');
            $placeNode->appendChild($pointNode);

            $altModeNode = $this->dom->createElement('altitudeMode', 'absolute'); // Sets the altitude of the coordinate
                                                                                  // relative to sea level
            $pointNode->appendChild($altModeNode);

            $coorNode = $this->dom->createElement('coordinates', $coordStr);
            $pointNode->appendChild($coorNode);
        }
//         echo '</pre>';
//         die(count($sitesArray));
        return $this->dom->saveXML();
    }
}

?>
