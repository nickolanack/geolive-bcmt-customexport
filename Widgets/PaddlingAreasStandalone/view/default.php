<?php

//include dirname(dirname($this->getPath())).'/src/paddlingAreas.php';
if(UrlVar('show', '')==='map'){
	include dirname(dirname($this->getPath())).'/src/paddlingAreas.php';
	return;
}

if($targetInstance){


	//https://www.bcmarinetrails.org/nervouS-falcon/php-core-app/core.php?controller=plugins&view=plugin&format=raw&plugin=UserInterface&pluginView=widget&widget=23


	?>
	<script type="text/javascript">


	<?php echo $this->renderTemplate($targetInstance); ?>
	

	</script>

<?php


}else{
	include dirname(dirname($this->getPath())).'/src/siteSearch.php';
}

