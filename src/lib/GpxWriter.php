<?php

class GpxWriter {
    private $dom;
    private $docNode;

    function __construct() {

        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $node = $this->dom->createElementNS('http://www.topografix.com/GPX/1/1', 'gpx');
        $this->docNode = $this->dom->appendChild($node);
        $attr = $this->dom->createAttribute('version');
        $attr->value = "1.1";
        $node->appendChild($attr);
        $attr = $this->dom->createAttribute('creator');
        $attr->value = "Marine Trails";
        $node->appendChild($attr);
        $attr = $this->dom->createAttribute('xmlns:xsi');
        $attr->value = "http://www.w3.org/2001/XMLSchema-instance";
        $node->appendChild($attr);
        
        $node = $this->dom->createElement('metadata');
        $metaNode = $this->docNode->appendChild($node);
        
        $node = $this->dom->createElement('link');
        $attr = $this->dom->createAttribute('href');
        $attr->value = 'http://www.bcmarinetrails.org';
        $node->appendChild($attr);
        $linkNode = $metaNode->appendChild($node);
        $node = $this->dom->createElement('text', 'BC Marine Trails');
        $textNode = $linkNode->appendChild($node);
        $timeNow = gmdate("Y-m-d\TH:i:s\Z");
        $node = $this->dom->createElement('time', $timeNow);
        $timeNode = $metaNode->appendChild($node);
    }

    function writeGpx($sitesArray) {

        foreach ($sitesArray as $site) {
            $coordArray = Util::parseCoords($site['coordinates']);
            
            $node = $this->dom->createElement('wpt');
            $attr = $this->dom->createAttribute('lat');
            $attr->value = $coordArray['lat'];
            $node->appendChild($attr);
            $attr = $this->dom->createAttribute('lon');
            $attr->value = $coordArray['lng'];
            $node->appendChild($attr);
            $waypointNode = $this->docNode->appendChild($node);
            
            $elevationNode = $this->dom->createElement('ele', '5.00'); // elevation is always 5 meters above sea-level
            $wptNameNode = $waypointNode->appendChild($elevationNode);
            
            $nameNode = $this->dom->createElement('name', $site['name']);
            $wptNameNode = $waypointNode->appendChild($nameNode);
            
            /*
             * description should be:
             * Site function e.g. Alternate Campsite
             * Landing Comments e.g. blah blah
             * Camp Comments e.g. continue blah blah - different receivers will truncate at different points and it is
             * best just to keep adding text as in the KML file.
             */
           
             $tentSites = trim(strtolower((empty($site['tentSites'])?"?":$site['tentSites']).""));
             $tentSites = str_replace('unknown', "?", $tentSites);


            $descText=$site['siteFunction'] . "\n\n" . "Landing Comments: ".$site['landingComments'] . "\n\n" . "Camp Comments: ".$site['campComments']."\n\n".'Tent Sites: '.  $tentSites."\n\n".'Other Comments: '.  $site['otherComments'];

            $descText=$this->filterLinkTags($descText);

            $descNode = $this->dom->createElement('desc', 
                htmlspecialchars($descText));
            $wptDescNode = $waypointNode->appendChild($descNode);
            
            // $typeNode = $this->dom->createElement('type', $site['siteFunction']);
            // $wptTypeNode = $waypointNode->appendChild($typeNode);
        }
        
        return $this->dom->saveXML();
    }

    protected function filterLinkTags($htmlBlock)
    {
        preg_match_all('/<a(.*?)<\/a>/i', $htmlBlock, $results);

        if(count($results[0])<1){
            return $htmlBlock;
        }
      

        preg_match_all('/href[^\'"]+[\'"]([^\'"]+)[\'"]/', implode('', $results[0]), $links);

        if(empty($links[1])){
            return array();
        }
        foreach($results[0] as $i=>$linkTag){


            $htmlBlock=str_replace($linkTag, implode(' - ',  array_filter(array(strip_tags($linkTag), $links[1][$i]), function($t){
                return ($t&&trim($t)!=="");
            })), $htmlBlock);
        }

        return $htmlBlock;
    }
}

?>
