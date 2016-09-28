<?php
echo '<?xml version="1.0" encoding="UTF-8"?>';
?><gpx xmlns="http://www.topografix.com/GPX/1/1" version="1.1"
	creator="Marine Trails"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"> <metadata>
<link href="http://www.bcmarinetrails.org">
<text>BC Marine Trails</text>
</link>
<time><?php echo  gmdate("Y-m-d\TH:i:s\Z");?></time></metadata><?php

$callback = $params['callback'];
$callback();

?></gpx>