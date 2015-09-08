<?php

class AjaxRequest {

    private static function ItemMetadata($itemid) {

        ob_start();
        /* @var $item Marker */
        if ($itemid instanceof Marker) {
            $item = $itemid;
        } else {
            $item = MapController::LoadMapItem($itemid);
        }
        Scaffold('article.mapitem', 
            array(
                'item' => $item,
                'imageThumb' => array(
                    250,
                    210
                ),
                'maxImages' => 1,
                'schema' => array(
                    'link' => 'itemprop="map"'
                ),
                'showStaticMap' => false
            ), Core::Get('Maps')->getScaffoldsPath());
        
        $article = ob_get_contents();
        ob_end_clean();
        
        // return array(
        // 'html' => $article
        // );
        
        $layer = null;
        foreach (GeoliveHelper::VisibleLayers() as $l) {
            if ($l->getId() == $item->getLayerId()) {
                $layer = $l;
            }
        }
        
        $data = AttributesRecord::Get($item->getId(), 'marker', GeoliveHelper::AttributeTableMetadata());
        
        return array(
            'html' => $article,
            'details' => array(
                'coordinates' => $item->getCoordinates(),
                'layer' => $layer->getName()
            ),
            'attributes' => $data
        );
    }

    public static function ListSites() {

        $args = json_decode(UrlVar('json', '{}'));
        
        $sites = array();
        if (is_array($args->paddlingAreas)) {
            
            GeoliveHelper::FilteredSiteListInAreas($args->paddlingAreas, 
                function ($site) use(&$sites) {
                    if (count($sites) < 25) {
                        
                        $sites[] = array_merge(get_object_vars($site), self::ItemMetadata($site->id));
                    } else {
                        $sites[] = get_object_vars($site);
                    }
                });
        }
        
        echo json_encode(
            array(
                'sites' => $sites,
                // 'args' => $args,
                // 'query' => Core::GetDatasource()->getQuery(),
                'success' => true
            ), JSON_PRETTY_PRINT);
    }

    public static function CountSites() {

        $args = json_decode(UrlVar('json', '{}'));
        
        $sites = array();
        if (is_array($args->paddlingAreas)) {
            
            $count = GeoliveHelper::CountSitesInAreas($args->paddlingAreas);
        }
        
        echo json_encode(
            array(
                'count' => $count,
                // 'args' => $args,
                // 'query' => Core::GetDatasource()->getQuery(),
                'success' => true
            ), JSON_PRETTY_PRINT);
    }

    public static function ArticlesForSites() {

        $args = json_decode(UrlVar('json', '{}'));
        
        $sites = array();
        if (is_array($args->sites)) {
            
            foreach ($args->sites as $id) {
                
                $site = MapController::LoadMapItem((int) $id);
                if (Auth('read', $site, 'mapitem')) {
                    $sites[] = array_merge(
                        array(
                            'id' => $site->getId(),
                            'name' => $site->getName()
                        ), self::ItemMetadata($site));
                } else {
                    echo json_encode(
                        array(
                            'success' => false,
                            'message' => 'invalid id:' . $id . ' in list, or no access'
                        ), JSON_PRETTY_PRINT);
                    return;
                }
            }
        }
        
        echo json_encode(
            array(
                'sites' => $sites,
                // 'args' => $args,
                // 'query' => Core::GetDatasource()->getQuery(),
                'success' => true
            ), JSON_PRETTY_PRINT);
    }
}