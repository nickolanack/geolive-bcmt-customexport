<?php

namespace bcmt;

class FieldFormatter{

	protected $featureSync=null;


	public function __construct($featureSync){

		$this->featureSync=$featureSync;

	}


	protected function getLayerIdFromName($name){

		return $this->featureSync->getLayerIdFromName($name);
	}



	public function formatFeatureField($name, $value) {
		if ($name == 'coordinates') {
			return implode(', ', array_slice($value, 0, 2));
		}
		if ($name == 'description') {
			//return trim(preg_replace("/<img[^>]+\>/i", "", $value));
		}

		



		if (is_string($value)) {
			return trim($value);
		}
		return $value;
	}


	public function formatAttributeField($name, $value) {

		if (is_string($value)) {
			return trim($value);
		}

		if($name == 'siteConditionNotes'&&is_null($value)){
			return "";
		}

		if($name == 'siteUsage'&&is_null($value)){
			return "";
		}

		return $value;
	}


	public function formatKnackField($name, $value, $record=null) {

		if($name=='coordinates'&&is_string($value)){
			//$value=str_replace(' ', '', $value);
		}

		if ($name == 'layerId') {

			//if(is_string($value)&&empty($value)){
				$layerName=$record->{'layer title'};
				$value=$this->getLayerIdFromName($layerName);
			//}

			$value = (int) $value;
			if ($value <= 0) {
				$value = 6;
			}
		}

		if (empty($value)) {
			return "";
		}

		if (is_string($value)) {
			return trim($value);
		}

		if (is_numeric($value)) {
			return $value;
		}

		if (is_array($value)) {
			return implode(', ', array_map(function ($v) use ($name) {
				return $this->formatKnackField($name, $v);
			}, $value));
		}

		if (is_object($value)) {

			if (key_exists('url', $value)) {
				return $value->url;
			}

		}

		return json_encode($value);
	}


	public function sanityCheck($fieldName, $oldV, $newV){


		$sanityCheck = array(
			'section' => function ($old, $new) {
				if (empty($new) || empty(trim($new))) {
					throw new \Exception('Sanity check: section empty: ' . $old . ' => ' . $new);
				}
			},
			'paddlingArea' => function ($old, $new) {
				if (empty($new) || empty(trim($new))) {
					throw new \Exception('Sanity check: paddlingArea empty: ' . $old . ' => ' . $new);
				}
			},
			'siteUsage' => function ($old, $new) {

				if (empty($new) || empty(trim($new))) {

					if (empty($old) || empty(trim($old))) {
						return;
					}

					throw new \Exception('Sanity check: siteUsage empty: ' . $old . ' => ' . $new);
				}
			},
			'siteFunction' => function ($old, $new) {
				if (empty($new) || empty(trim($new))) {

					if (empty($old) || empty(trim($old))) {
						return;
					}

					throw new \Exception('Sanity check: siteFunction empty: ' . $old . ' => ' . $new);
				}
			},
			'tentSites' => function ($old, $new) {

				if ($old === 0 && empty($new)) {
					return;
				}

				if (empty($new) || empty(trim($new))) {

					if (empty($old) || empty(trim($old))) {
						return;
					}

					throw new \Exception('Sanity check: tentSites empty: ' . $old . ' => ' . $new);
				}

				for ($i = 1; $i <= 12; $i++) {
					$month = date('M', strtotime('0000-' . $i . '-01'));
					if (stripos($new, $month) !== false) {
						throw new \Exception('Sanity check: tentSites date conversion: ' . $old . ' => ' . $new);
					}
				}
			},
			'landingComments' => function ($old, $new) {
				if (empty($new) || empty(trim($new))) {
					if (empty($old) || empty(trim($old))) {
						return;
					}
					throw new \Exception('Sanity check: landingComments empty: ' . $old . ' => ' . $new);
				}
			},
			'campComments' => function ($old, $new) {
				if (empty($new) || empty(trim($new))) {
					if (empty($old) || empty(trim($old))) {
						return;
					}
					throw new \Exception('Sanity check: campComments empty: ' . $old . ' => ' . $new);
				}
			},
			'otherComments' => function ($old, $new) {
				if (empty($new) || empty(trim($new))) {
					if (empty($old) || empty(trim($old))) {
						return;
					}
					throw new \Exception('Sanity check: otherComments empty: ' . $old . ' => ' . $new);
				}
			},
			'siteConditionNotes' => function ($old, $new) {
				if (empty($new) || empty(trim($new))) {
					if (empty($old) || empty(trim($old))) {
						return;
					}
					throw new \Exception('Sanity check: siteConditionNotes empty: ' . $old . ' => ' . $new);
				}
			},
			'coordinates' => function ($old, $new) {

				$o = explode(', ', $old);
				$n = explode(', ', $new);

				if(count($n)!==2){
					throw new \Exception('Sanity check: invalid coordinates: ' . $old . ' => ' . $new);
				}

				if ($old[0] == 0 && $old[1] == 0) {
					return;
				}

				if (rtrim($n[0] . "", '0') === rtrim($o[0] . "", '0') && rtrim($n[1] . "", '0') === rtrim($o[1] . "", '0')) {
					return;
				}

				if (abs(floatval($n[0]) - floatval($o[0])) > 0.000001) {
					//throw new \Exception('Sanity check: coordinates lat change: ' . $old . ' => ' . $new);
				}
				if (abs(floatval($n[1]) - floatval($o[1])) > 0.000001) {
					//throw new \Exception('Sanity check: coordinates lng change: ' . $old . ' => ' . $new);
				}

			},
		);


		if (key_exists($fieldName, $sanityCheck)) {
			$sanityCheckFn = $sanityCheck[$fieldName];
			$sanityCheckFn($oldV, $newV);
			
		}
	}
}