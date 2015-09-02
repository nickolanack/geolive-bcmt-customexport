

function PaddlingRegionSearchBehavior(regions, config){
	

	

	var selobj=document.getElementById(config.rgSelect);
	var rgImg=document.getElementById(config.regionImage);
	var paChs=document.getElementById(config.areaChoices);
	var paInstr=document.getElementById(config.paInstr);
	var paSubmit=document.getElementById(config.paSubmit);
	var exportOutput=document.getElementById('exportOutput');
	var form=document.getElementById('exportForm');

	selobj.addEventListener("change",function(){
		listPaddlingAreas(selobj.value);
	});
	
	
	Array.prototype.slice.call( paSubmit.childNodes, 0).forEach(function(button){
		button.addEventListener('click',function(){
			var out=button.getAttribute('data-out');
			if(out==='preview'){
				
				
				
			}else{
				exportOutput.value=out
				form.submit();
			}
			
		})
	
	});
	

	function listPaddlingAreas(forRegion)
	{
		
		
		
		var selectedRegion = forRegion;
		var areaChoicesHtml = "";
		for(var i in regions) {
			if (regions[i].rgName == selectedRegion)
			{
				var areas = regions[i].areas;
				var mapPrefix = '../images/stories/';
				rgImg.src=mapPrefix + regions[i].image;
				rgImg.alt=regions[i].rgName + " picture";
				for(var j in areas) {
					if (typeof areas[j].paName !== "undefined") {
						areaChoicesHtml += '<label><input class="ckbxarea" type=checkbox name="paddlingAreas[]" value="' + areas[j].paName + '" />' + areas[j].paName + '</label>';
					}
				}
			}
		}
		paChs.innerHTML=areaChoicesHtml;
		
		
		if(selectedRegion == 'choose a region') {
			paInstr.style.visibility="hidden";
			paSubmit.style.visibility="hidden";
		} else {
			paInstr.style.visibility="visible";
			paSubmit.style.visibility="visible";
		}

		addSelectedAreasValidator(
				Array.prototype.slice.call( document.getElementsByClassName("ckbxarea") , 0),
				Array.prototype.slice.call( paSubmit.childNodes, 0)
			);


	}
	
	
	function addSelectedAreasValidator(checkboxes, buttons){
		
		var checked=0;
		
		buttons.forEach(function(submit){
			submit.setAttribute('disabled',true);
			submit.className='btn';
		});
		

		checkboxes.forEach(function(chbx){
			chbx.addEventListener("click", function(){
				if(chbx.checked){
					checked++;
				}else{
					checked--;
				}
				
				if(checked>0){
					buttons.forEach(function(submit){
						submit.removeAttribute('disabled');
						submit.className='btn btn-primary';
					});
				}else{
					buttons.forEach(function(submit){
						submit.setAttribute('disabled',true);
						submit.className='btn';
					}); 
				}
				
			});
		});
		
		
	}
	
	
	
	
	
	
	/*
	
	
	
	// the following function is never used.
	// jQuery selectors in this function only work if you pass in the DOM reference.  No method of appending to the select element worked.
	function loadPaddlingAreas(rgSel,paSel,paOpts,rgImg)
	{
		var $jq = jQuery.noConflict(); // this is to get around 'is not a function' errors that get thrown due to conflict with MooTools.
		var selectedRegion = rgSel.value;
		var selectedArea = paSel.value
		$jq(paSel).empty();
		var opt = document.createElement("option");
		opt.text = 'choose a paddling area';
		paOpts.add(opt);
		for(var i in regions) {
			if (regions[i].rgName == selectedRegion)
			{
				var areas = regions[i].areas;
				var mapPrefix = '../images/stories/';
				$jq(rgImg).attr('src',mapPrefix + regions[i].image);
				$jq(rgImg).attr('alt',regions[i].rgName + " picture");
				for(var j in areas) {
					if (typeof areas[j].paName !== "undefined") {
						var opt = document.createElement("option");
						opt.text = areas[j].paName;
						paOpts.add(opt);
					}
				}
			}
		}
	}

 */
	
}
