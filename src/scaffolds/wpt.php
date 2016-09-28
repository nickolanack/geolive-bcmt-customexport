<?php
$config = array_merge(
    array(
        'coordinates' => array(
            0,
            0
        ),
        'name' => '',
        'description' => ''
    ), $params);
?>
<wpt lat="<?php echo $config['coordinates'][0];?>"
	lon="<?php echo $config['coordinates'][1];?>"> <ele>5.00</ele> <name><?php echo $config['name'];?></name>
<desc><?php echo $config['description'];?></desc> </wpt>