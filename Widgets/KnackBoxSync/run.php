<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


		$dir = __DIR__;
        while ((!file_exists($dir . DIRECTORY_SEPARATOR . 'core.php') && (!empty($dir)))) {
            $dir = dirname($dir);
        }

        if (file_exists($dir . DIRECTORY_SEPARATOR . 'core.php')) {
            include_once $dir . DIRECTORY_SEPARATOR . 'core.php';
        } else {
            throw new Exception('failed to find core.php');
        }


		include_once __DIR__ . '/vendor/autoload.php';

		HtmlDocument()
            ->setDocumentRoot('/srv/www/vhosts/production/bcmarinetrails.s54.ok.ubc.ca/http')
            ->setProtocol('https')
            ->setDomain('www.bcmarinetrails.org')
            ->setScriptPath('index.php');
           

      
        //Authorizer()->setDataType('marker', new AuthDummy());
        ob_start();
    
        GetPlugin('Attributes');
        include_once __DIR__ . '/AuthDummy.php';
        global $AttributesAuthenticator;
        $AttributesAuthenticator = function ($type) {
            return new AuthDummy();
        };
		

       $longTaskProgress= (new \core\LongTaskProgress())
                ->setNonReentrant('import-knack-items');

        $sync = (new \bcmt\Sync())
        ->addEventHandler(function($event, $data){
                
            GetPlugin('Email')->getMailer()->mail(
                'Knack/Box/Geolive Sync Notification: '.$event, 
                '<pre>'.json_encode($data,JSON_PRETTY_PRINT).'</pre>'
            )
            ->to(array('nickblackwell82@gmail.com'))->send();

        })
        ->triggerEvent('syncStart',array(
            "src"=>"cli"
        ))
        ->setKnackClient(

            (new \knack\Client(
                GetWidget(44)->getParameter('knackAuth')
            ))
                ->cacheRequests()
                ->limitApiCalls(4500)
                ->useNamedTableDefinitionForObject('mapitems', 16)
                //->shuffleResults()

        )
        ->setBoxClient(

            (new \box\Client(
                GetWidget(44)->getParameter('boxAuth')
            ))
            ->useCachePath(GetPath('{front}/../') . '/box-items')
            ->cacheItemsInPath('/geolive-site-images') //optimization

        )

        ->setMapClient((new \bcmt\LocalClient()))
        ->setSiteUrl('https://www.bcmarinetrails.org')
        ->cacheProgressTo(GetPath('{front}/../') . '/sync-items')
        
        ->syncBoxRootFolder('/geolive-site-images')
        ->setBoxCollaborators(7365369423);

        Broadcast('knack-sync', 'state', array(
                "running"=>true
            ));


        GetClient()->loginAs(62);

        $sync->resetDailyCache(0);

        ob_end_clean();

        //$sync->withKnackIdFilter(json_decode(file_get_contents(__DIR__.'/boxfolders.json')));
       // $sync->syncKnackUrls();
       //$sync->syncBoxChanges();
        //$sync->syncMapitems();
        
        //$sync->removeFeatures();

        
        $longTaskProgress->printOutput();
        
        
        $longTaskProgress->executeActivity('Syncronizing Data From Knack', array(
            function () use ($longTaskProgress, $sync){

               
                $sync->importKnackRecords($longTaskProgress);

            },
            function () use ($longTaskProgress, $sync){

                
                $sync->initBoxFolders($longTaskProgress);

            },
            function () use ($longTaskProgress, $sync) {

                $sync->syncKnackUrls($longTaskProgress);

            },
            
            function () use ($longTaskProgress, $sync) {

                $sync->syncMapitems($longTaskProgress);

            },
            function () use ($longTaskProgress, $sync) {

                

                $sync->removeFeatures($longTaskProgress);

            },
            function () use ($longTaskProgress, $sync) {

                /////////$sync->syncBoxChanges($longTaskProgress);
                $sync->syncAllBoxFolders($longTaskProgress);
                //$sync->syncBoxFolderWithMarker(14165, $longTaskProgress);
            }

        ));
        
        $sync->triggerEvent('syncComplete',array());
        
         Broadcast('knack-sync', 'state', array(
                "running"=>false
            ));

