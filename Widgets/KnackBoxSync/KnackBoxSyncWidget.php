<?php

/**
 * @package    Geolive
 * @subpackage Widgets
 * @license    MIT
 * @author	Nicholas Blackwell
 * @version	1.0
 */

include_once GetPath('{plugins}/Maps/Widgets/CustomTileControlScript/CustomTileControlScriptWidget.php');

class KnackBoxSyncWidget extends CustomTileControlScriptWidget implements core\AjaxControllerProvider {

    use core\PluginMemberTrait;
    use core\AjaxControllerProviderTrait;
    use core\ReusableViewTrait;
    
    protected $javascript = false;

    protected $name = "Knack Box Sync";

    public function getDescription() {

        return "Knack Box Sync tools";
    }

    public function includeScripts($targetInstance = null){

    	parent::includeScripts($targetInstance);

    	IncludeJS('{scripts}/Controls/UIProgressBar.js');
		IncludeJS('{scripts}/Controls/UITaskProgressSubscription.js');
		IncludeCSS('{core}/css/progress.css');

        IncludeCSSBlock('

            .subscription-progress.main-progress:before {
                content: "Overall Progress";
                z-index: 1;
                top: 0;
                background-color: transparent;
                box-shadow: none;
            }

            .subscription-progress:before {
                content: attr(data-activity-name);
                z-index: 1;
                top: 0;
                background-color: transparent;
                box-shadow: none;
            }

            .knack-sync .tileButtonContainer.active {
                animation: pulse 1s infinite;
            }





            @keyframes pulse {
                0% {
                    background-color: #b7fdb7;
                }
                10% {
                    background-color: #57f157;
                }
                100% {
                    background-color: #b7fdb7;
                }
            }

        ');

    }

    public function getUserFunction(){

    	return JSBlock(function(){
    		?><script type="text/javascript">


                var widget=<?php echo $this->getId(); ?>;
    			
                AjaxControlQuery.Subscribe({
                    "channel" : "knack-sync",
                    "event" : "state"
                }, function(state){
                    if(state.running){
                        tile.activate();
                        return;
                    }

                    tile.deactivate();
                })


    			tile.addEvent('click',function(){


    				(new AjaxControlQuery(CoreAjaxUrlRoot, 'sync', {
		                "widget": widget
		                })).addEvent("success", function(result){

    					var el=new Element('div',{styles:{'margin':"30px"}}); 
    					var pb=PushBoxWindow.open(el, {push:true, handler:'append', size: {x: 650, y: 200}, elasticY:200});
    					(new UITaskProgressSubscription(el, Object.append({
                            "showOutput":false,
                            "hideCompleted":false
                        },result))).addEvent('addActivity', function(){
                           setTimeout(function(){ pb.fit(); }, 50);
                        }).onComplete(function(){
                            setTimeout(function(){
        						if(document.body.contains(el)){
        							pb.close();
        						}
                            },5000);

    					});
                        el.appendChild(new Element('p',{
                            "html":"You can close this window, importing will continue to run in the background",
                            "styles":{
                                    "color": "cornflowerblue",
                                    "text-align": "center",
                                    "font-style": "italic"
                            }
                        }));

    				}).execute();

    				
    			});

    		</script><?php
    	}).parent::getUserFunction();

    }



    public function syncWithKnack($eventArgs){


       
        echo "Init\n";
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set("error_log", __DIR__."/php-error.log");

        set_time_limit(-1);

       
        $sync=$this->getSync();

        
    	$longTaskProgress = new \core\LongTaskProgress($eventArgs);
        $longTaskProgress->setOutputFile(__DIR__."/php-error.log");
        
        if($longTaskProgress->isRunning()){
            echo "Already running\n";
            return;
        }


        if(!$this->getParameter('enableSync', true)){
            $longTaskProgress->executeActivity('~DISABLED~ Syncronizing Data From Knack', array(
                function () use ($longTaskProgress, $sync){

                  sleep(5);

                })
            );


            return;

        }


        Broadcast('knack-sync', 'state', array(
                "running"=>true,
                'progressChannel'=>$longTaskProgress->getSubscription()
            ));


        Broadcast('knack-sync', 'test', array(
                "running"=>true,
                'progressChannel'=>$longTaskProgress->getSubscription()
            ));


        Broadcast('knack-sync', 'test', array(
                "running"=>true,
                'progressChannel'=>$longTaskProgress->getSubscription()
            ));
        
        echo "Starting\n";

        try{

            $sync->triggerEvent('syncStart',array(
                'time'=>time(),
                'date'=>date('Y-m-d H:i:s'),
                'user'=>GetClient()->getUserMetadata()
            ));

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

                    $sync->syncBoxChanges($longTaskProgress);

                    
                }

    		));

            $sync->triggerEvent('syncComplete',array());

        }catch(\Exception $e){
            error_log($e->getMessage());
            //error_log(print_r($e,true));
            foreach (array_slice($e->getTrace(), 0 , 20) as $value) {

                error_log(print_r($value, true)."\n\n\n");
          
            }
        }

         Broadcast('knack-sync', 'state', array(
                "running"=>false
            ));

    }

    protected function getSync(){

         include_once __DIR__.'/vendor/autoload.php';

        $sync = (new \bcmt\Sync())
        ->addEventHandler(function($event, $data){

            GetPlugin('Email')->getMailer()->mail(
                'Knack/Box/Geolive Sync Notification: '.$event, 
                '<pre>'.json_encode($data,JSON_PRETTY_PRINT).'</pre>'
            )
            ->to(array('nickblackwell82@gmail.com'))->send();

        })
        ->setKnackClient(

            (new \knack\Client(
                $this->getParameter('knackAuth')
            ))
            ->cacheRequests()
            ->limitApiCalls(500)
            ->useNamedTableDefinitionForObject('mapitems', 16)
            ->shuffleResults()

        )
        ->setBoxClient(

            (new \box\Client(
                $this->getParameter('boxAuth')
            ))
            ->useCachePath(GetPath('{front}/../') . '/box-items')
            ->cacheItemsInPath('/geolive-site-images') //optimization

        )

        ->setMapClient((new \bcmt\LocalClient()))
        ->setSiteUrl('https://www.bcmarinetrails.org')
        ->cacheProgressTo(GetPath('{front}/../') . '/sync-items')
        
        ->syncBoxRootFolder('/geolive-site-images')
        ->setBoxCollaborators(7365369423)
        ->resetDailyCache(0);
        

        return $sync;


    }


    public function __destruct()
    {
       error_log('exit');
    }
   
}