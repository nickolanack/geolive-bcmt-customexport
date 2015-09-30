/**
 * 
 */

function PaddlingRegionMapSearchBehavior(regionsData, map, kmlUrl){

	/*
	 * current todos:
	 * move functions and variables below into class PaddlingAreasSelection
	 */
	
	
	var polysByArea={};
	var areasByRegion={};
	var regionsByArea={};
	
	var popoversByArea={};

	(regionsData).forEach(function(regionObj){

		if(!areasByRegion[regionObj.rgName]){
			areasByRegion[regionObj.rgName]=[];
		}

		regionObj.areas.forEach(function(areasObj){

			areasByRegion[regionObj.rgName].push(areasObj.paName);


			if(!regionsByArea[areasObj.paName]){
				regionsByArea[areasObj.paName]=regionObj.rgName
			}

		});
	});
	
	
	
	var polysWithRegion=function(region){
		var areas=areasByRegion[region];
		if(!areas){
			console.log("missing region: "+region);
			return [];
		}
		return areas.map(function(area){
			var poly=polysByArea[area];
			if(!poly)console.log("missing area: "+area);
			return poly;
		}).filter(function(p){return !!p;});

	}


	var polysNotWithRegion=function(region){



		var areas=areasByRegion[region];
		if(!areas){
			console.log("missing region: "+region);
			return [];
		}
		var polys=[];
		Object.keys(polysByArea).forEach(function(a){
			if(areas.indexOf(a)==-1){
				polys.push(polysByArea[a]);
			}

		});
		return polys;

	}
	
	var popoverByArea=function(area){
		if((typeof popoversByArea[area])=='undefined'){
			popoversByArea[area]=new UIMapPopover(polysByArea[area],{
				anchor:UIPopover.AnchorTo(['bottom'])
				}).setMap(map);
		}
		return popoversByArea[area];
	}

	var detail=new Element('div', {'class':'paddling-areas-detail',html:'<span class="no-region">select a region</span>'});
	var clear=new Element('button', {'class':'btn btn-danger',html:'reset'});
	var selectedRegion=false;
	
	
	

	var PaddlingAreasSelection=new Class({
		Implements:Events,
		initialize:function(regionsData, map, kmlUrl){
			
			var me=this;
			
			(new XMLControlQuery(kmlUrl, "",
					{})).addEvent("success",function(xml){

						//console.log(xml);

					
						
						map.controls[google.maps.ControlPosition.TOP_LEFT].insertAt(0, detail);	

						map.controls[google.maps.ControlPosition.TOP_RIGHT].insertAt(0, clear);	



						clear.addEvent('click',function(){				
							me._reset();
						});
				


						(new SimpleParser({
							polygonTransform:function(polygonParams, xmlSnippet){
								//console.log(polygonParams);

								var polygon= new google.maps.Polygon((function(){
									var polygonOpts={
											paths:(function(){


												var paths=polygonParams.coordinates.map(function(coord){
													return {lat:parseFloat(coord[0]), lng:parseFloat(coord[1])};
												});
												return paths;
											})(),
											fillColor:'#000000',
											fillOpacity:0.5,
											strokeColor:'#000000',
											strokeWeight:1,
											strokeOpacity:0.7
									};
									console.log(polygonOpts);
									return polygonOpts;
								})());



								polygon.setMap(map);
								var area=polygonParams.name
								polysByArea[area]=polygon;

								google.maps.event.addListener(polygon, 'click',function(e){

									me._toggleSelectedArea(area);

								});


								google.maps.event.addListener(polygon, 'mouseover',function(e){
									me._overArea(area);
								});

								google.maps.event.addListener(polygon, 'mouseout',function(e){
									me._outArea(area);
								});

							}
						})).parsePolygons(xml);
						setTimeout(function(){
							
							me.zoomToRegion(null);
							
						}, 500);
						
						

					}).execute();

			
		},
		
		hasRegion:function(){
			return (typeof selectedRegion)=='string';
		},
		getAreas:function(){
			var me=this;
			return me._selectedAreas.slice(0);
			
		},
		
		setAreas:function(areas){
			var newAreas=areas.splice(0);
			var me=this;
			if(me.hasRegion()){
				
				me.getAreas().forEach(function(area){
					var i=newAreas.indexOf(area);
					if(i==-1){
						me._clearSelectedArea(area);
					}else{
						newAreas.splice(i,1); //to skip set selected!
					}
					
				});
				
				newAreas.forEach(function(area){
					me._setSelectedArea(area);
				});
				
			}else{
				
				throw new Error('Expected to have a region before setting areas')
				
			}
		},
		_isSelectedArea:function(area){
			var me=this;
			if(!me._selectedAreas){
				me._selectedAreas=[];
			}
			
			return me._selectedAreas.indexOf(area)>=0;	
		},
		
		_setSelectedArea:function(area){
			var me=this;
			if(!me._isSelectedArea(area)){
				me._selectedAreas.push(area);
			}
			
			var polygon=polysByArea[area];
			polygon.setOptions({fillColor:'#5AB55A'})
			
		},
		
		_clearSelectedArea:function(area){
			var me=this;
			if(me._isSelectedArea(area)){
				var i=me._selectedAreas.indexOf(area);
				me._selectedAreas.splice(i,1);
			}
			var polygon=polysByArea[area];
			polygon.setOptions({fillColor:'#000000'})
		},
		
		_toggleSelectedArea:function(area){
			
			var me=this;
			if(!me.hasRegion()){
				me._selectRegionWithArea(area);
				return;

			}

	
			if(me._isSelectedArea(area)){
				me._clearSelectedArea(area);
				me.fireEvent('clearArea', [area]);
				//becuase cursor is still over the area.
				//setTimeout(function(){
					//let it go dark briefly... 
					var polygon=polysByArea[area];
					polygon.setOptions({fillColor:'#55acee'});
				//},250)
					
				me._setAreaPoverTextSelectable(area);
			}else{
				me._setSelectedArea(area);
				me.fireEvent('selectArea', [area]);
				me._setAreaPoverTextRemoveable(area);
			}
			
		},
		_overArea:function(area){
			var me=this;
			
			if(!me.hasRegion()){
				me._overRegionWithArea(area);
				return;

			}
			var polygon=polysByArea[area];
			if(me._isSelectedArea(area)){
				me._setAreaPoverTextRemoveable(area).show();	
			}else{
				
				polygon.setOptions({fillColor:'#55acee'})
				me._setAreaPoverTextSelectable(area).show();			
			}
			detail.innerHTML=selectedRegion+' - '+area;
			
			
			
			

		},
		_setAreaPoverTextSelectable(area){
			var popover=popoverByArea(area);
			popover.setText(new Element('span', {html:'click to add <span class="pop-area">'+area+'</span> to the current selection', style:"width:200px;"}));
			return popover;
		},
		_setAreaPoverTextRemoveable(area){
			var popover=popoverByArea(area);
			popover.setText(new Element('span', {html:'click to remove <span class="pop-area remove">'+area+'</span> from the current selection', style:"width:200px;"}));
			return popover;
		},
		_outArea:function(area){
			var me=this;
			
			if(!me.hasRegion()){
				me._outRegionWithArea(area);
				return;

			}
			if(me._isSelectedArea(area)){
				
			}else{
				var polygon=polysByArea[area];
				polygon.setOptions({fillColor:'#000000'});	
			}
			detail.innerHTML=selectedRegion;
			

		},
		
		_overRegionWithArea:function(area){
			var me=this;
			var region= regionsByArea[area];
			if(region==me.mRegion&&me.mRegionTimeout){
				clearTimeout(me.mRegionTimeout);
			}
			polysWithRegion(region).forEach(function(polygon){
				polygon.setOptions({fillColor:'#55acee'})

			});

			var popover=popoverByArea(area);
			popover.setText(new Element('span', {html:'click to choose <span class="pop-area region">'+region+'</span> as the current region', style:"width:200px;"}));
			popover.show();
			detail.innerHTML=region;

		},
		
		_outRegionWithArea:function(area){
			var me=this;
			detail.innerHTML='<span class="no-region">select a region</span>';
			var region= regionsByArea[area];
			me.mRegion=region;
			me.mRegionTimeout=setTimeout(function(){

				polysWithRegion(region).forEach(function(polygon){
					polygon.setOptions({fillColor:'#000000'})
				});

			},150);
			
		},
		
		_selectRegionWithArea:function(area){
			var me=this;
			var region=regionsByArea[area];
			me.setRegion(region);
			me._overArea(area);
			me.fireEvent('selectRegion',[region]);


		},
		
		setRegion:function(region){
			var me=this;
			selectedRegion=region;
			detail.innerHTML=region;
			polysWithRegion(region).forEach(function(polygon){
				polygon.setVisible(true);
				polygon.setOptions({fillColor:'#000000', strokeOpacity:0.7});
			
			});

			polysNotWithRegion(region).forEach(function(polygon){
				polygon.setVisible(false);
			});

			me.zoomToRegion(region);

		},
		_reset:function(){
			var me=this;
			me.zoomToRegion(null);
			if(me.hasRegion()){
				me.setAreas([]);
				selectedRegion=null;
			}
			Object.keys(polysByArea).forEach(function(name){
				polygon=polysByArea[name];
				polygon.setOptions({fillColor:'#000000', strokeOpacity:0.7});
				polygon.setVisible(true);
			});
			me.fireEvent('reset');
			
		},
		getRegion:function(){
			var me=this;
			if(me.hasRegion()){
				return selectedRegion;
			}
			return false;
		},
		
		zoomToRegion:function(region){
			var polys=[];
			if(region){
				polys=polysWithRegion(region);
			}else{
				Object.keys(polysByArea).forEach(function(k){
					polys.push(polysByArea[k]);
				})
			}

			var bounds=new google.maps.LatLngBounds();
			polys.forEach(function(polygon){

				polygon.getPath().forEach(function(latlng){			 
					bounds.extend(latlng);
				});

			});

			var bndSpn=bounds.toSpan();
			var mapSpn=map.getBounds().toSpan()

			function log2(val) {
				return Math.log(val) / Math.LN2;
			}


			// calculate relative zoom difference between map bounds and polybounds 
			// it is easy becuase each zoom lever doubles or halves the latlng pan of a tile.
			// making math easy with log2 ie: if the map has 4 times the lat span, then ratio 
			// is 4 and the map could be zoomed once 2^x=4 where x is the number of zooms in 
			// this case x=2.

			//x
			var x=log2(mapSpn.lng()/bndSpn.lng())
			console.log('x: '+x);

			//y
			var y=log2(mapSpn.lat()/bndSpn.lat());
			console.log('y: '+y);

			map.setZoom(map.getZoom()+Math.min(Math.round(x), Math.round(y)));
			map.panTo(bounds.getCenter());

		}
		
		

	
	});
	
	

	var mapselector=new PaddlingAreasSelection(regionsData, map, kmlUrl);


 
	// this is to help parent window access the iframe as though it was
	// a javascript class
	
	window.Outlets={

			getRegion:function(){
				
				return mapselector.getRegion();
				
			},
			setRegion:function(region){
				
				mapselector.setRegion(region);
				
				
			},

			getSelectedAreas:function(){
				mapselector.getAreas();
			},
			
			setSelectedAreas:function(areas){
				mapselector.setAreas(areas);
			},


			onChange:function(fn){

				mapselector.addEvent('selectRegion', function(){ fn(mapselector); });
				mapselector.addEvent('clearRegion', function(){ fn(mapselector); });
				
				mapselector.addEvent('selectArea', function(){ fn(mapselector); });
				mapselector.addEvent('clearArea', function(){ fn(mapselector); });
				mapselector.addEvent('reset', function(){ fn(mapselector); });

			}


	};


};
