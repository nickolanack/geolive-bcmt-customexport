<?php
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

class GeoliveHelper
{
    private static $isTerm = false;
    private static $isDirect = false;
    private static $isLoaded = false;
    private static $isUrl = false;

    public static function LoadCoreLibs()
    {

        if (!self::$isLoaded) {

            $root = __DIR__;
            while ($root !== '/' && !file_exists($root . DS . 'configuration.php')) {
                //recurse back to site root.
                $root = dirname($root);
            }

            //check for geolive core include.
            $core = $root . DS . 'administrator' . DS . 'components' . DS . 'com_geolive' . DS .
                'core.php';

            if (!file_exists($core)) {
                throw new Exception('Unable to load Geolive Framework');
            }

            include_once $core;

            Core::Parameters()->disableCaching();
            Core::Parameters()->disableCompression();

            Core::LoadPlugin('Attributes');
            Core::LoadPlugin('Maps');

            self::$isLoaded = true;
        }

        $file = dirname(__DIR__) . DS . 'siteSearch.php';
        $map = dirname(__DIR__) . DS . 'paddlingAreas.php';

        if (key_exists('TERM', $_SERVER)) {
            self::$isTerm = true;
            if (isset($argv) && (realpath($argv[0]) === $file || realpath($argv[0]) === $map)) {
                self::$isDirect = true;
            }
        } else {
            self::$isUrl = true;
            if (Core::HTML()->getScriptName() == basename($file) || Core::HTML()->getScriptName() == basename($map)) {
                self::$isDirect = true;
            }
        }

        // Core::HTML()->setThrowsResouceInclusionError('Document header has already been printed');
    }

    public static function ScriptWasAccessedDirectlyFromUrl()
    {

        return self::$isDirect && self::$isUrl;
    }

    public static function ScriptWasIncludedFromJoomla()
    {

        return (!self::$isDirect) && self::$isUrl;
    }

    public static function ScriptWasIncludedFromCommandline()
    {

        return (!self::$isDirect) && self::$isTerm;
    }

    public static function ScriptWasAccessedDirectlyFromCommandLine()
    {

        return self::$isDirect && self::$isTerm;
    }

    public static function LoadGeoliveFromCommandLine()
    {

        $cmd = getopt('',
            array(
                'session:',
                'protocol:',
                'domain:',
                'scriptpath:',
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

    public static function VisibleLayers($accessGroups=null)
    {

        if(is_null($accessGroups)){
            $accessGroups=Core::Client()->getAccessGroups();
        }


        if (is_null(self::$visibleLayers)) {

            $readAccessFilter = array(
                'readAccess' => array(
                    'comparator' => 'IN',
                    'value' => '(\'' . implode('\', \'', $accessGroups) . '\')',
                    'qoutes' => false,
                ),
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
    public static function MapitemTable()
    {

        return Core::LoadPlugin('Maps')->getDatabase()->table(MapsDatabase::$MAPITEM);
    }

    public static function AttributeTable()
    {

        return Core::LoadPlugin('Attributes')->getDatabase()->decodeTableName(AttributesTable::GetMetadata('siteData'));
    }
    private static $tableMetadata = null;

    public static function AttributeTableMetadata()
    {

        if (is_null(self::$tableMetadata)) {
            self::$tableMetadata = AttributesTable::GetMetadata('siteData');
        }
        return self::$tableMetadata;
    }

    /**
     *
     * @return CoreDatabase is actually MapsDatabase object, but only CoreDatabase functions are neccesary
     */
    public static function Database()
    {

        return Core::LoadPlugin('Maps')->getDatabase();
    }

    public static function DefinedRegionsList()
    {

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
    public static function DistinctPaddlineAreas($region)
    {

        // TODO: I'd like to set up paddlingArea to have a defined list table (or tree rather with 2 levels;
        // roots=regions, children=paddlingAreas)
        // That would remove the need for the following Attribute Filter, and be a simple one liner like getting
        // Regions. ie:
        if (is_null(self::$queryWithRegionReplacement)) {
            $query = 'Select DISTINCT a.paddlingArea as area FROM ( SELECT * FROM ' . self::MapitemTable() . ' ) as m, ' . AttributesFilter::JoinAttributeFilterObject(
                json_decode(
                    '{
                    "join":"join","table":"siteData","set":"*","filters":[
                        {"field":"section","comparator":"equalTo","value":"' . '[[REGION]]' . '", "table":"siteData"}
                    ],"show":"paddlingArea"
                }'), 'm.id', 'm.type') . ' AND m.lid IN (' . implode(
                ', ',
                array_map(
                    function ($layer) {
                        if (!method_exists($layer, 'getId')) {
                            throw new Exception(print_r($layer, true));
                        }
                        return $layer->getId();
                    }, self::VisibleLayers())) . ') ORDER BY a.paddlingArea';

            self::$queryWithRegionReplacement = $query;
        }

        $q = str_replace('[[REGION]]', $region, self::$queryWithRegionReplacement);

        $paArray = array();

        $areas = array();
        self::Database()->iterate($q,
            function ($result) use (&$areas) {
                $areas[] = ucwords(trim($result->area));
            });
        return array_unique($areas);
    }

    /**
     * I was going to intersect all results with region as well, but I think that is unneccessary.
     * just union
     * all sites with paddling area.
     *
     * @param unknown $areas
     * @param unknown $iteratorCallback
     * @return string
     */
    public static function FilteredSiteListInAreas($areas, $iteratorCallback)
    {

        $filter = json_decode(
            '{
                    "join":"join","table":"siteData","set":"*","filters":[' . implode(', ',
                array_map(
                    function ($area) {
                        return '{"field":"paddlingArea","comparator":"equalTo","value":"' . $area .
                            '", "table":"siteData"}';
                    }, $areas)) . '


                    ]
                }');

        // print_r($filter);

        $query = 'Select m.id as id, m.name as name FROM ( SELECT * FROM ' . GeoliveHelper::MapitemTable() .
        ' WHERE readAccess IN (\'' . implode('\', \'', Core::Client()->getAccessGroups()) . '\')) as m, ' . AttributesFilter::JoinAttributeFilterObject(
            $filter, 'm.id', 'm.type') . ' AND m.lid IN (' . implode(', ',
            array_map(function ($layer) {
                return $layer->getId();
            }, self::VisibleLayers())) . ') ORDER BY a.paddlingArea';

        self::Database()->iterate($query, $iteratorCallback);
    }

    public static function CountSitesInAreas($areas, $accessGroups=null)
    {

        if(is_null($accessGroups)){
            $accessGroups=Core::Client()->getAccessGroups();
        }

        $filter = json_decode(
            '{
                    "join":"join","table":"siteData","set":"*","filters":[' . implode(', ',
                array_map(
                    function ($area) {
                        return '{"field":"paddlingArea","comparator":"equalTo","value":"' . $area .
                            '", "table":"siteData"}';
                    }, $areas)) . '


                    ]
                }');

        // print_r($filter);

        $query = 'Select count(*) as count FROM ( SELECT * FROM ' . GeoliveHelper::MapitemTable() .
        ' WHERE readAccess IN (\'' . implode('\', \'', $accessGroups) . '\')) as m, ' . AttributesFilter::JoinAttributeFilterObject(
            $filter, 'm.id', 'm.type') . ' AND m.lid IN (' . implode(', ',
            array_map(function ($layer) {
                return $layer->getId();
            }, self::VisibleLayers($accessGroups))) . ')';

        return self::Database()->query($query)[0]->count;
    }

    public static function QueriedSiteListInAreas($areas, $iteratorCallback, $accessGroups=null)
    {

        if(is_null($accessGroups)){
            $accessGroups=Core::Client()->getAccessGroups();
        }

        $from = "FROM " . GeoliveHelper::AttributeTable() . " a inner join " . GeoliveHelper::MapitemTable() .
        " m on a.mapitem = m.id WHERE m.lid IN (" . implode(', ',
            array_map(
                function ($layer) {
                    return $layer->getId();
                }, self::VisibleLayers($accessGroups))) . ")";

        $paWhere = 'AND (' . implode(' OR ',
            array_map(
                function ($pa) {
                    return 'lower(trim(a.paddlingArea)) = lower(trim(\'' . GeoliveHelper::Database()->escape($pa) .
                        '\'))';
                }, $areas)) . ')';
        $query = "SELECT * $from $paWhere order by m.name";

        self::Database()->iterate($query, $iteratorCallback);
    }

    public static function QueriedSiteListInAreasInIdList($areas, $ids, $iteratorCallback)
    {

        $from = "FROM " . GeoliveHelper::AttributeTable() . " a inner join " . GeoliveHelper::MapitemTable() .
        " m on a.mapitem = m.id WHERE m.lid IN (" . implode(', ',
            array_map(
                function ($layer) {
                    return $layer->getId();
                }, self::VisibleLayers())) . ") AND m.id IN(" . implode(', ',
            array_map(
                function ($id) {
                    return (int) $id;
                }, $ids)) . ")";

        $paWhere = 'AND (' . implode(' OR ',
            array_map(
                function ($pa) {
                    return 'lower(trim(a.paddlingArea))  = lower(trim(\''.
                    GeoliveHelper::Database()->escape($pa) . '\'))';
                }, $areas)) . ')';
        $query = "SELECT * $from $paWhere order by m.name";

        self::Database()->iterate($query, $iteratorCallback);
    }
}

GeoliveHelper::LoadCoreLibs();
