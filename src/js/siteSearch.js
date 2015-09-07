

function PaddlingRegionSearchBehavior(regions, layers, JsonQuery){
	


	var selobj=document.getElementById('rgSelect');
	var rgImg=document.getElementById('regionImage');
	var paChs=document.getElementById('areaChoices');
	var paInstr=document.getElementById('paInstr');
	var paSubmit=document.getElementById('paSubmit');
	var exportOutput=document.getElementById('exportOutput');
	var form=document.getElementById('exportForm');
	
	var sitePreviewArea=document.getElementById('site_preview');

	selobj.addEventListener("change",displayPaddlingAreas);
	
	
	getSubmitButtons().forEach(function(button){
		button.addEventListener('click',function(){
			var out=button.getAttribute('data-out');
			if(out==='preview'){
				
				displaySelectedSites();
				
				
			}else{
				exportOutput.value=out
				form.submit();
			}
			
		});
	
	});
	
	
	function getSubmitButtons(){
		return Array.prototype.slice.call(paSubmit.childNodes, 0);
	}
	function getCheckboxes(){
		return Array.prototype.slice.call(document.getElementsByClassName("ckbxarea"), 0);
	}
	
	function displaySelectedSites(){
		
		var json={paddlingAreas:[], layers:[], region:selobj.value};
		
		getCheckboxes().forEach(function(cbx){
			
			if(cbx.checked){
				json.paddlingAreas.push(cbx.value);
			}
			
		});
		
		if(json.layers.length==0){
			json.layers="*";
		}
		if(json.paddlingAreas.length==0){
			json.paddlingAreas="*";
		}
		
		(new JsonQuery('list_sites', json)).addEvent('success',function(result){
			sitePreviewArea.innerHTML='';
			if(result.success){
				
				var sitesWithoutHtml=[];
				result.sites.forEach(function(site){
					
					if(site.html){
						var siteArticle=new Element('div');
						siteArticle.innerHTML=site.html;
						sitePreviewArea.appendChild(siteArticle);
					}else{
						sitesWithoutHtml.push(site.id);
					}
					
					
					if(count(sitesWithoutHtml)){
						
					}
					
				});
			}
			
		}).execute();
		
		
		
	}

	function displayPaddlingAreas()
	{
		

		var selectedRegion = selobj.value;
		var areaChoicesHtml = "";
		var mapPrefix = '../images/stories/';
		for(var i in regions) {
			if (regions[i].rgName == selectedRegion)
			{
				var areas = regions[i].areas;
				
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
			rgImg.src=mapPrefix + 'sixregions.jpg';
		} else {
			paInstr.style.visibility="visible";
			paSubmit.style.visibility="visible";
		}

		addSelectedAreasSubmitValidator();


	}
	
	
	/**
	 * 
	 */
	function addSelectedAreasSubmitValidator(){
		
		var checkboxes=getCheckboxes();
		var buttons=getSubmitButtons();
		
		var checked=0;
		
		buttons.forEach(function(btn){
			btn.setAttribute('disabled',true);
			btn.className='btn';
		});
		

		checkboxes.forEach(function(cbx){
			cbx.addEventListener("click", function(){
				if(cbx.checked){
					checked++;
				}else{
					checked--;
				}
				
				if(checked>0){
					enableSubmitButtons();
				}else{
					disableSubmitButtons();
				}
				
											
				
			});
		});
		
		
	}
	
	function enableSubmitButtons(){
		
		getSubmitButtons().forEach(function(btn){
			
			btn.removeAttribute('disabled');
			if(btn.getAttribute('data-out')==='preview'){			
				btn.className='btn btn-primary';	
			}else{	
				btn.className='btn btn-success';
			}

		});

	}
	
	function disableSubmitButtons(){
		getSubmitButtons().forEach(function(btn){
			btn.setAttribute('disabled',true);
			btn.className='btn';
		}); 

	}
	
	
}
