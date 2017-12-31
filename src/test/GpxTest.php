<?php

class GPXTest extends PHPUnit_Framework_TestCase
{

    public function testParseLinks()
    {

        echo json_encode($this->filterLinkTags('

           


        '));
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

        // print_r($results);
        // print_r($links);



        foreach($results[0] as $i=>$linkTag){


            $htmlBlock=str_replace($linkTag, implode(' - ',  array_filter(array(strip_tags($linkTag), $links[1][$i]), function($t){
                return ($t&&trim($t)!=="");
            })), $htmlBlock);
        }

        //echo $htmlBlock;
        return $htmlBlock;
    }
}
