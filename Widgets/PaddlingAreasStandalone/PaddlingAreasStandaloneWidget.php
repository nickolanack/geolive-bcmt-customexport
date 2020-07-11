<?php

/**
 * @package    Geolive
 * @subpackage Widgets
 * @license    MIT
 * @author	Nicholas Blackwell
 * @version	1.0
 *
 * The user type widget describes the user type on the map.
 */

class PaddlingAreasStandaloneWidget extends Widget implements core\AjaxControllerProvider, core\ReusableView{

    use core\PluginMemberTrait;
    use core\AjaxControllerProviderTrait;
    use core\ReusableViewTrait;
    
    protected $javascript = false;

    protected $name = "Paddling Areas Tool";

    public function getDescription() {

        return "Paddling Areas Standalone Tool";
    }

    public function includeScripts($targetInstance = null) {



        Behavior('mootools');
        Behavior('ajax');


		IncludeCSS(dirname(dirname($this->getPath())).'/src/css/siteSearch.css');
		IncludeJS(dirname(dirname($this->getPath())).'/src/js/siteSearch.js'); 
    

    }

   
}