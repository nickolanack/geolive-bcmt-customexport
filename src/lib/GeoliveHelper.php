<?php
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

class GeoliveHelper {
    private static $isTerm = false;
    private static $isDirect = false;
    private static $isLoaded = false;
    private static $isUrl = false;

    public static function LoadCoreLibs() {

        if (!self::$isLoaded) {
            
            include_once dirname(dirname(__DIR__)) . DS . 'administrator' . DS . 'components' . DS . 'com_geolive' . DS .
                 'core.php';
            Core::LoadPlugin('Attributes');
            Core::LoadPlugin('Maps');
            
            self::$isLoaded = true;
        }
        
        if (key_exists('TERM', $_SERVER)) {
            self::$isTerm = true;
            if (isset($argv) && realpath($argv[0]) === __FILE__) {
                self::$isDirect = true;
            }
        } else {
            self::$isUrl = true;
            if (Core::HTML()->getScriptName() == basename(__FILE__)) {
                self::$isDirect = true;
            }
        }
    }

    public static function ScriptWasAccessedDirectlyFromUrl() {

        return self::$isDirect && self::$isUrl;
    }

    public static function ScriptWasIncludedFromJoomla() {

        return (!self::$isDirect) && self::$isUrl;
    }

    public static function ScriptWasIncludedFromCommandline() {

        return (!self::$isDirect) && self::$isTerm;
    }

    public static function ScriptWasAccessedDirectlyFromCommandLine() {

        return self::$isDirect && self::$isTerm;
    }

    public static function LoadGeoliveFromCommandLine() {

        $cmd = getopt('', 
            array(
                'session:',
                'protocol:',
                'domain:',
                'scriptpath:'
            ));
        
        if (!key_exists('session', $cmd)) {
            exit('Core: terminal commands require --session');
        }
        
        // these are all optional, but if not set, will likely cause fatal errors.
        
        if (key_exists('protocol', $cmd)) {
            Core::HTML()->setProtocol($cmd['protocol']);
        }
        if (key_exists('domain', $cmd)) {
            Core::HTML()->setDomain($cmd['domain']);
        }
        if (key_exists('scriptpath', $cmd)) {
            Core::HTML()->setScriptPath($cmd['scriptpath']);
        }
    }
    private static $visibleLayers;

    public static function VisibleLayers() {

        if (is_null(self::$visibleLayers)) {
            
            $readAccessFilter = array(
                'readAccess' => array(
                    'comparator' => 'IN',
                    'value' => '(\'' . implode('\', \'', Core::Client()->getAccessGroups()) . '\')',
                    'qoutes' => false
                )
            );
            
            $layers = MapController::GetAllLayers($readAccessFilter);
            self::$visibleLayers = $layers;
        }
        return self::$visibleLayers;
    }

    /**
     *
     * @return string table name with prefix
     */
    public static function MapitemTable() {

        return Core::LoadPlugin('Maps')->getDatabase()->table(MapsDatabase::$MAPITEM);
    }

    public static function AttributeTable() {

        return Core::LoadPlugin('Attributes')->getDatabase()->decodeTableName(AttributesTable::GetMetadata('siteData'));
    }

    /**
     *
     * @return CoreDatabase is actually MapsDatabase object, but only CoreDatabase functions are neccesary
     */
    public static function Database() {

        return Core::LoadPlugin('Maps')->getDatabase();
    }

    public static function DefinedRegionsList() {

        $tableMetadata = AttributesTable::GetMetadata('siteData');
        $rgArray = array_map(function ($region) {
            return ucwords($region->section);
        }, AttributesField::GetDefinedList(AttributesField::GetMetadata('section', $tableMetadata)));
        return $rgArray;
    }
    private static $queryWithRegionReplacement = null;

    /**
     *
     * @param string $region            
     * @return array<string> list of region values.
     */
    public static function DistinctPaddlineAreas($region) {
        
        // TODO: I'd like to set up paddlingArea to have a defined list table (or tree rather with 2 levels;
        // roots=regions, children=paddlingAreas)
        // That would remove the need for the following Attribute Filter, and be a simple one liner like getting
        // Regions. ie:
        if (is_null(self::$queryWithRegionReplacement)) {
            $query = 'Select DISTINCT a.paddlingArea as area FROM ( SELECT * FROM ' . self::MapitemTable() . ' ) as m, ' . AttributesFilter::JoinAttributeFilterObject(
                json_decode(
                    '{
                    "join":"join","table":"siteData","set":"*","filters":[
                        {"field":"section","comparator":"equalTo","value":"' .
                         '[[REGION]]' . '", "table":"siteData"}
                    ],"show":"paddlingArea"
                }'), 'm.id', 'm.type') . ' AND m.lid IN (' . implode(
                ', ', 
                array_map(function ($layer) {
                    return $layer->getId();
                }, self::VisibleLayers())) . ') ORDER BY a.paddlingArea';
            
            self::$queryWithRegionReplacement = $query;
        }
        
        $q = str_replace('[[REGION]]', $region, self::$queryWithRegionReplacement);
        
        $paArray = array();
        
        $areas = array();
        self::Database()->iterate($q, 
            function ($result) use(&$areas) {
                $areas[] = ucwords(trim($result->area));
            });
        return array_unique($areas);
    }

    /**
     * I was going to intersect all results with region as well, but I think all that is neccessary is to union
     * all sites with paddling area.
     *
     * @param unknown $areas            
     * @param unknown $iteratorCallback            
     * @return string
     */
    public static function FilteredSiteListInAreas($areas, $iteratorCallback) {

        $query = 'Select DISTINCT a.paddlingArea as area FROM ( SELECT * FROM ' . $table . ' ) as m, ' . AttributesFilter::JoinAttributeFilterObject(
            json_decode(
                '{
                    "join":"join","table":"siteData","set":"*","filters":[' . array_map(
                    function ($area) {
                        return '{"field":"paddlingArea","comparator":"equalTo","value":"' . $area .
                             '", "table":"siteData"}';
                    }, $areas) . '


                    ],"show":"*"
                }'), 'm.id', 'm.type') . ' AND m.lid IN (' . implode(
            ', ', 
            array_map(function ($layer) {
                return $layer->getId();
            }, self::VisibleLayers())) . ') ORDER BY a.paddlingArea';
        
        self::Database()->iterate($query, $iteratorCallback);
    }

    public static function QueriedSiteListInAreas($areas, $iteratorCallback) {

        $from = "FROM " . GeoliveHelper::AttributeTable() . " a inner join " . GeoliveHelper::MapitemTable() .
             " m on a.mapitem = m.id WHERE m.lid IN (1, 2, 3, 7)";
        
        $paWhere = 'AND ' . implode(' AND ', 
            array_map(
                function ($pa) {
                    return 'lower(trim(a.paddlingArea)) LIKE \'%' . GeoliveHelper::Database()->escape($pa) . '%\'';
                }, $areas));
        $query = "SELECT * $from $paWhere order by m.name";
        
        self::Database()->iterate($query, $iteratorCallback);
    }
}