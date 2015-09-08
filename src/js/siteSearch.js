

function PaddlingRegionSearchBehavior(regions, layers, JsonQuery){



	var selobj=document.getElementById('rgSelect');
	var rgImg=document.getElementById('regionImage');
	var paChs=document.getElementById('areaChoices');
	var paInstr=document.getElementById('paInstr');
	var paSubmit=document.getElementById('paSubmit');
	var paSubmitFooter=document.getElementById('paSubmitFooter');
	var exportOutput=document.getElementById('exportOutput');
	var form=document.getElementById('exportForm');
	var siteCount=document.getElementById('siteCount');

	var sitePreviewArea=document.getElementById('site_preview');
	var sitePreviewHeader=document.getElementById('sitePreviewHeader');
	
	var siteSelectAll=document.getElementById('selectAllSites');
	var siteRemoveAll=document.getElementById('removeAllSites');
	
	var currentCountSites=0;
	var currentSelectedSites=0;
	var siteList=document.getElementById('siteList');
	
	selobj.addEventListener("change",displayPaddlingAreas);


	getSubmitButtons().forEach(function(button){
		button.addEventListener('click',function(){
			var out=button.getAttribute('data-out');
			if(out==='preview'){

				displaySelectedSites();


			}else{
				exportOutput.value=out
				if(currentSelectedSites<currentCountSites){
					siteList.value=JSON.stringify(getSiteCheckboxes().filter(function(cbx){
						return !!cbx.checked;
					}).map(function(cbx){
						return parseInt(cbx.getAttribute('data-id'));
					}));
				}
				form.submit();
			}

		});

	});


	function getSubmitButtons(){
		return Array.prototype.slice.call(paSubmit.childNodes, 0).filter(function(btn){
			return (btn.nodeName==='A');
		}).concat(Array.prototype.slice.call(paSubmitFooter.childNodes, 0).filter(function(btn){
			return (btn.nodeName==='A');
		}));
	}
	function getCheckboxes(){
		return Array.prototype.slice.call(document.getElementsByClassName("ckbxarea"), 0);
	}
	

	function getSiteCheckboxes(){
		return Array.prototype.slice.call(document.getElementsByClassName("site-ckbx"), 0);
	}

	function displaySelectedSites(){

		paSubmitFooter.style.visibility="visible";
		sitePreviewHeader.style.visibility="visible";
		
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
						displaySitePreviewElement(site);
					}else{
						sitesWithoutHtml.push(site.id);
					}

				});

				var staggerQuery=function(){
					var group=[];
					while(sitesWithoutHtml.length>0&&group.length<25){
						group.push(sitesWithoutHtml.shift());
					}

					if(group.length){

						(new JsonQuery('site_articles', {sites:group})).addEvent('success',function(result){

							result.sites.forEach(function(site){

								if(site.html){
									displaySitePreviewElement(site);
								}

							});
							staggerQuery();
						}).execute();
					}

				};
				staggerQuery();
			}

		}).execute();
	}
	
	function updateCountOfSites(){
		
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

		(new JsonQuery('count_sites', json)).addEvent('success',function(result){
			
			if(result.success){
				currentCountSites=result.count;
				currentSelectedSites=result.count;
				displayCountOfSites();
			}
		}).execute();
	}
	
	function displayCountOfSites(){
		
		if(currentSelectedSites<currentCountSites){
			
			siteCount.innerHTML=" "+currentSelectedSites+"/"+currentCountSites+' locations';
		}else{

			var s=(currentCountSites==1?'':'s');
			siteCount.innerHTML=" "+currentCountSites+' location'+s;
		}
		
		updateSiteButtons();
	}
	
	function updateSiteButtons(){
		
		if(currentCountSites==currentSelectedSites){
			siteSelectAll.setAttribute('disabled',true);
			siteSelectAll.className='btn';
		}else{
			siteSelectAll.removeAttribute('disabled');
			siteSelectAll.className='btn btn-info';
		}
		
		if(currentSelectedSites==0){
			siteRemoveAll.setAttribute('disabled',true);
			siteRemoveAll.className='btn';
			disableSubmitButtons();
		}else{
			enableSubmitButtons();
			siteRemoveAll.removeAttribute('disabled');
			siteRemoveAll.className='btn btn-info';
		}
		
	}
	
	siteSelectAll.addEvent('click',function(){
		getSiteCheckboxes().forEach(function(cbx){
			cbx.checked=true;
			cbx.parentNode.addClass('selected');
		});
		currentSelectedSites=currentCountSites;
		displayCountOfSites();
	})
	
	siteRemoveAll.addEvent('click',function(){
		getSiteCheckboxes().forEach(function(cbx){
			cbx.checked=false;
			cbx.parentNode.removeClass('selected');
		});
		
		currentSelectedSites=0;
		displayCountOfSites();
	})
	
	
	
	function displaySitePreviewElement(site){

		var siteArticle=new Element('div', {'id':'site-'+site.id, 'class':'selected'});
		siteArticle.innerHTML=site.html;
		
		var cbx=new Element('input',{type:'checkbox', checked:true, 'class':'site-ckbx', 'data-id':site.id})

		siteArticle.insertBefore(cbx,siteArticle.childNodes[0]);
		
		cbx.addEventListener('change',function(){

			if(cbx.checked){
				siteArticle.addClass('selected');
				currentSelectedSites++;
			}else{
				siteArticle.removeClass('selected');
				currentSelectedSites--
			}
			displayCountOfSites();

		});
		
		
		
		sitePreviewArea.appendChild(siteArticle);
		
		var attributesEl=new Element('ul');
		Object.keys(site.attributes).forEach(function(name){
			value=site.attributes[name];
			attributesEl.appendChild(new Element('li',{html:value, 'data-field':name, 'class':'atr-'+name}));
		});

		siteArticle.appendChild(attributesEl);
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
			paInstr.style.display="none";
			paSubmit.style.visibility="hidden";
			rgImg.src=mapPrefix + 'sixregions.jpg';
		} else {
			paInstr.style.display="block";
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

				updateCountOfSites();
				sitePreviewArea.innerHTML='';
				paSubmitFooter.style.visibility="hidden";
				sitePreviewHeader.style.visibility="hidden";

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

	
	
	
	
	
	var gridView=document.getElementById('gridView');
	var tableView=document.getElementById('tableView');
	
	gridView.addEvent('click',function(){
		
		gridView.addClass('btn-primary');
		tableView.removeClass('btn-primary');
		
		
		gridView.addClass('active');
		tableView.removeClass('active');
		
		gridView.firstChild.src=gridView.firstChild.src.split('?')[0]+'?tint=rgba(255,255,255)'
		tableView.firstChild.src=tableView.firstChild.src.split('?')[0]+'?tint=rgb(0, 68, 204)'
		
		sitePreviewArea.addClass('grid-view');
		sitePreviewArea.removeClass('table-view');
		
		
		
	});
	
	tableView.addEvent('click',function(){
		tableView.addClass('btn-primary');
		gridView.removeClass('btn-primary');
		
		
		tableView.addClass('active');
		gridView.removeClass('active');
		
		
		tableView.firstChild.src=tableView.firstChild.src.split('?')[0]+'?tint=rgba(255,255,255)';
		gridView.firstChild.src=gridView.firstChild.src.split('?')[0]+'?tint=rgb(0, 68, 204)';
		
		
		sitePreviewArea.addClass('table-view');
		sitePreviewArea.removeClass('grid-view')
		
	});
	

}
