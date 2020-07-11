<?php


class KnackBoxSyncAjaxController extends core\AjaxController implements core\WidgetMember{
	

	use core\WidgetMemberTrait;


	protected function sync($task, $json){

		(new \core\LongTaskProgress())->emit('onTriggerUpdateDevicesList', array('team' => 1));


		

		return array(
			'task'=>$task,
			'json'=>$json,
			'subscription' => (new \core\LongTaskProgress())
				->setNonReentrant('import-knack-items')
				->emit('onTriggerSyncKnack', array('widget'=>$this->getWidget()->getId()))
				->getSubscription()
		);
	}




}