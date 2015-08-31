<?php

class Util {

public static function parseCoords($coords) {
	$ret = array();
	preg_match("/\{\"coordinates\":\[+([-\.\d]+),([-\.\d]+)(,[-\.\d]+)?\]+\}/",$coords,$matches);
	$lat = $matches[1];
	$ret['lat'] = round($lat,5);
	$lng = $matches[2];
	$ret['lng'] = round($lng,5);
	return $ret;
}

public static function getOutputDir() {
	//return '/srv/www/vhosts/development/dev.bcmarinetrails.s54.ok.ubc.ca/http/';  // for development
    return '/srv/www/vhosts/production/bcmarinetrails.s54.ok.ubc.ca/http/';  // for production
}

public static function logger($string) {
    $file = "/srv/www/vhosts/development/dev.bcmarinetrails.s54.ok.ubc.ca/http/log/sitesearch.log";
    #$file = "./log/sitesearch.log";

    // this is just for testing installation
    /*
        if (!touch($file)) {
            echo "FILE ERROR: cannot touch $file with user " . get_current_user() . "\n"; // helps figure out why you can't touch the file
            return;
        } else {
            echo "FILE: touched $file with user " . get_current_user() . "\n"; // helps figure out why you can't touch the file
        }
    */

    if (!$fh = fopen($file, 'a')) {
        return;
    }

    flock($fh, LOCK_EX);
    $delim = "\t";
    date_default_timezone_set($conf['timeZone']);
    if (!fwrite($fh, 'StaticUtil[' . date('Y-M-d H:i:s') . ']' . $delim . $string . "\r\n")) {
        return;
    }
    flock($fh, LOCK_UN);
    fclose($fh);
}

}

?>
