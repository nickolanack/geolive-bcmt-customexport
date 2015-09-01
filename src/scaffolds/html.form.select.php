<?php
$config = array_merge(array(
    'regionObjArray'
), $params);

?>
<script type="text/javascript" src="../js/siteSearch.js">
</script>
<script type="text/javascript">
window.addEventListener("load", function(){
	PaddlingRegionSearchBehavior(
 <?php echo json_encode($config['regionObjArray'], JSON_PRETTY_PRINT); ?>, {

		rgSelect:"rgSelect",
		regionImage:"regionImage",
	    areaChoices:"areaChoices",
	    paInstr:"paInstr",
	    paSubmit:"paSubmit"

    });
  });

</script>


<a name="bcmtFormAnchor"></a>
<h3>Search for site by Region and Paddling Area</h3>
<form name="bcmtForm" method="POST"
	action="sites-by-region-and-paddling-area#bcmtFormAnchor">
	<img id="regionImage" src="../images/stories/sixregions.jpg"
		alt="Six Regions" width="300px" style="float: left">
	<table style="margin-left: 330px">
		<tr>
			<td>Region:</td>
			<td><select id="rgSelect" name="rgSelect" class="btn btn-success"
				style="height: 30px;">
					<option>choose a region</option>
			<?php

echo implode(
    array_map(
        function ($region) {
            $name = $region->rgName;
            return '<option value="' . $name . '">' . $name . '</option>';
        }, $config['regionObjArray']));

?>
			</select></td>
		</tr>
		<tr>
			<td id="paInstr" style="visibility: hidden" colspan=2>Choose the
				paddling areas you wish to view in either Google Earth or your GPS:</td>
		</tr>
		<tr>
			<td>&nbsp;&nbsp;&nbsp;</td>
			<td id="areaChoices" style="vertical-align: top"></td>
		</tr>
		<tr>
			<td colspan="2">&nbsp;&nbsp;&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;&nbsp;&nbsp;</td>
			<td id="paSubmit" style="visibility: hidden"><input type="submit"
				value="Generate files" class="btn btn-primary"></td>
		</tr>
	</table>
	<br />
</form>