if (!window.UILayerGroup) {
    var UILayerGroup = new Class({
        Implements:[Events],

        initialize: function(application, groupMap, options) {

            var me = this;
            me.options = Object.append({
                "showExpand":true,
                "zoomToExtents":false,
                "anchorTo":"left"

            }, options);

            me.application=application;

            me._layerGroupsMap = groupMap;
            
            me._layerGroupEls = {};
            me._layerGroupChildren={};
            me._layerGroupPopovers={};

        },
        addLegendLayer: function(layer, element) {

            var me = this;
            var group = me.getGroup(layer, element)

            if (group) {
                me.addToGroup(group, layer, element);
                return;
            }
        },
        getGroup: function(layer, element) {
            var me = this;
            var id = layer.getId();
            var keys = Object.keys(me._layerGroupsMap);
            var key = null;
            var map = null;

            for (var i = 0; i < keys.length; i++) {
                key = keys[i];
                map = me._layerGroupsMap[key];
                for (var j = 0; j < map.length; j++) {
                    if (id + "" == map[j] + "") {
                        return key;
                    }
                    //element.addClass("not-"+map[j]);
                }
                // element.addClass("not-"+map.join('-'));
            }
            // element.addClass("not-any-"+keys.map(function(k){return k.toLowerCase().split(' ').join('-'); }).join('-'));
            return false;
        },
        toggleNesting:function(group){

            var me=this;
            var category=me._layerGroupEls[group];


            if(category.hasClass('expanded')){
                category.removeClass('expanded');
                me._layerGroupChildren[group].forEach(function(el){
                    el.removeClass('expanded');
                });
                return;
            }


            category.addClass('expanded');
            me._layerGroupChildren[group].forEach(function(el){
                    el.addClass('expanded');
            });

        },
        updatePopover:function(group){
            var me=this;
            var popover=me._layerGroupPopovers[group];

            popover.setText('<div style="color:cornflowerblue;">Click to open</div><ul class="'+group.toLowerCase().split(' ').join('-')+'">'+me._layerGroupChildren[group].map(function(el){
                return el.outerHTML;
            }).join('')+'</ul>')

        },
        zoomToExtents:function(group){

            var me=this;

            var layers = me._layerGroupsMap[group].map(function(lid) {
                return me.application.getLayerManager().getLayer(lid);
            });

            var north = -Infinity;
            var south = Infinity;
            var east = -Infinity;
            var west = Infinity;

            layers.forEach(function(i) {
                i.runOnceOnLoad(function() {

                    var b = i.getBounds();

                    north = Math.max(north, b.north);
                    east = Math.max(east, b.east);
                    south = Math.min(south, b.south);
                    west = Math.min(west, b.west);

                    me.application.fitBounds({
                        "north": north,
                        "south": south,
                        "east": east,
                        "west": west
                    });
                });

            });
        },
        updateState:function(group){

            var me=this;
            var category=me._layerGroupEls[group];

            var layers = me._layerGroupsMap[group].map(function(lid) {
                return me.application.getLayerManager().getLayer(lid);
            });

            var count = 0;
            var total=layers.length;
            layers.forEach(function(l) {

                if(l===false){
                    total--;
                    return;
                }

                if (l.isVisible()) {
                    count++;
                }
            });

            if(count==total){
                category.addClass('all');
            }
            if(count<total){
                category.removeClass('all');
            }
            

            if(count==0){
                 category.removeClass('active');
            }
            if(count>0){
                category.addClass('active');
            }

        },

        addIconImage:function(group, element){

            var me=this;

            var category=me._layerGroupEls[group];

            var img=category.appendChild(Asset.image(element.firstChild.src, {
                    styles: {

                        "width": "22px",
                        "height": "auto",
                        "padding-top": "1px",
                        "padding-bottom": "1px"

                    }
                }));

            return img;
        },

        addToGroup: function(group, layer, element) {
            var me = this;
            var category = me._layerGroupEls[group];
            var groupKabob = group.toLowerCase().split(' ').join('-')
            if (!category) {
                category = new Element('li', {
                    "class": "layer"
                });

                if(me.options.showExpand){
                    category.addClass("expandable-parent");
                }

                me._layerGroupEls[group] = category;
                me._layerGroupChildren[group]=[];
                element.parentNode.insertBefore(category, element);
                
                me.addIconImage(group, element);




                category.appendChild(new Element('span', {
                    "class": "label",
                    html: group
                }));
                var indicator=category.appendChild(new Element('span', {
                    "class": "indicator-switch"
                }));

                element.addClass('first-nested-child');
                element.insertBefore(new Element('span', {
                    "class":"alt-toggle",
                    "events":{"click":function(e){
                        e.stop();
                        me.toggleNesting(group);
                    }}
                }), element.firstChild);

              


                category.addEvent('click', function(e) {


                    if(me.options.showExpand&e.target!==indicator){

                        me.toggleNesting(group);

                        return;
                    }


                    var layers = me._layerGroupsMap[group].map(function(lid) {
                        return me.application.getLayerManager().getLayer(lid);
                    });
                    var value = false;
                    layers.forEach(function(l) {
                        if (l.isVisible()) {
                            value = true;
                        }
                    });
                    layers.forEach(function(l) {
                        if (value) {
                            l.hide();
                        } else {
                            l.show();
                        }
                    });

                    if (!value) {
                    
                        if(me.options.zoomToExtents){
                            me.zoomToExtents(group);
                        }

                    }


                });

                me._layerGroupPopovers[group]=new UIPopover(category, {
                    title: Localize(group, groupKabob),
                    description: "",
                    anchor: UIPopover.AnchorTo([me.options.anchorTo]),
                    clickable:true
                }).addEvent('show',function(){
                    me.updatePopover(group);
                }).addEvent('click',function(){
                    me.toggleNesting(group);
                    me._layerGroupPopovers[group].hide();
                });



                me.fireEvent("addGroup", [group, category]);

            } 



            
            layer.addEvent('hide',function(){
                me.updateState(group);
            });
            layer.addEvent('show',function(){
                me.updateState(group);
            });

            me.updateState(group);
            me.updatePopover(group);

            element.addClass("nested-1");
            element.addClass(groupKabob);

            me._layerGroupChildren[group].push(element);
           

            if(me.options.showExpand){
                
                element.addClass("expandable-child");
            }



        }


    });
}

var me = this;
if (!me._layerGroups) {
    me._layerGroups = new UILayerGroup(application, {
        "Campsites": [2, 3, 8]
    }).addEvent('addGroup',function(group, groupEl){
        ([
            "https://www.bcmarinetrails.org/components/com_geolive/users_files/user_files_62/Uploads/tJf_Z34_jL9_[G]_[ImAgE].png?thumb=>22x>22",
            "https://www.bcmarinetrails.org/components/com_geolive/users_files/user_files_62/Uploads/E6W_OuT_Yn7_[ImAgE]_[G].png?thumb=>22x>22"          
        ]).forEach(function(img, i){
            
            groupEl.appendChild(Asset.image(img, {
                    
                    styles: {
                        "position":"absolute",
                        "width": "22px",
                        "height": "auto",
                        "padding-top": "1px",
                        "padding-bottom": "1px",
                            "left":5*(i+1),
                            "z-index": -(i+1)

                    }
                }));
            
        });
        
    })
    element.addClass("created-groups");
}

me._layerGroups.addLegendLayer(layer, element);