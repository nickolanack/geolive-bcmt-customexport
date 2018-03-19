

function PaddlingRegionSearchBehavior(regions, layers, JsonQuery, iframeUrl) {



    try {
        //this removes side bar content. sorry need the space.
        $$('#rt-main .rt-grid-9')[0].removeClass('rt-grid-9').addClass('rt-grid-12');
        $$('#rt-main .rt-grid-3')[0].setStyle('display', 'none');
    } catch ( e ) {}

    var iframe = document.getElementById('mapFrame');
    if(!iframe){
        //throw 'No iframe!';
        var iframeContainer=document.getElementById('mapIframeContainer');
        if(!iframeContainer){
            throw 'No iframe, or container...';
        }
        iframe=iframeContainer.appendChild(new Element('iframe', {
            src:iframeUrl,
            style:"border: none; width: 100%; height: 550px;"
        }));
    }

    var selobj = document.getElementById('rgSelect');
    var rgImg = document.getElementById('regionImage');
    var paChs = document.getElementById('areaChoices');
    var paInstr = document.getElementById('paInstr');
    var paSubmit = document.getElementById('paSubmit');
    var paSubmitFooter = document.getElementById('paSubmitFooter');
    var exportOutput = document.getElementById('exportOutput');

    var exportJson = document.getElementById('exportJson');
    exportJson.value=JSON.stringify({"widget":"paddlingAreasTool"});
    
    var form = document.getElementById('exportForm');
    var siteCount = document.getElementById('siteCount');

    var sitePreviewArea = document.getElementById('site_preview');
    var sitePreviewHeader = document.getElementById('sitePreviewHeader');

    var siteSelectAll = document.getElementById('selectAllSites');
    var siteRemoveAll = document.getElementById('removeAllSites');

    var currentCountSites = 0;
    var currentSelectedSites = 0;
    var siteList = document.getElementById('siteList');

    selobj.addEventListener("change", displayPaddlingAreas);


    getSubmitButtons().forEach(function(button) {
        button.addEventListener('click', function() {
            if (button.getAttribute('disabled') !== null) return;
            var out = button.getAttribute('data-out');
            if (out === 'preview') {

                displaySelectedSites();


            } else {
                exportOutput.value = out

                if (currentSelectedSites < currentCountSites) {
                    siteList.value = JSON.stringify(getSiteCheckboxes().filter(function(cbx) {
                        return !!cbx.checked;
                    }).map(function(cbx) {
                        return parseInt(cbx.getAttribute('data-id'));
                    }));
                }
                form.submit();
            }

        });

    });


    function getSubmitButtons() {
        return Array.prototype.slice.call(paSubmit.childNodes, 0).filter(function(btn) {
            return (btn.nodeName === 'A'||btn.nodeName === 'BUTTON');
        }).concat(Array.prototype.slice.call(paSubmitFooter.childNodes, 0).filter(function(btn) {
            return (btn.nodeName === 'A'||btn.nodeName === 'BUTTON');
        }));
    }
    function getCheckboxes() {
        return Array.prototype.slice.call(document.getElementsByClassName("ckbxarea"), 0);
    }


    function getSiteCheckboxes() {
        return Array.prototype.slice.call(document.getElementsByClassName("site-ckbx"), 0);
    }


    /**
     * Queries for the list of site data objects (containing id, html, attributes, and details)
     * the server has been configured to return full site objects for the first 25 sites 
     * and simplified objects for the rest. detailed objects for the sites beyond 25 are then 
     * queired so that the quieries are staggered
     * 
     * @see displaySitePreviewElement
     */
    function displaySelectedSites() {

        paSubmitFooter.style.visibility = "visible";
        sitePreviewHeader.style.visibility = "visible";

        var json = {
            paddlingAreas: [],
            layers: [],
            region: selobj.value
        };

        getCheckboxes().forEach(function(cbx) {

            if (cbx.checked) {
                json.paddlingAreas.push(cbx.value);
            }

        });

        if (json.layers.length == 0) {
            json.layers = "*";
        }
        if (json.paddlingAreas.length == 0) {
            json.paddlingAreas = "*";
        }

        (new JsonQuery('list_sites', json)).addEvent('success', function(result) {
            sitePreviewArea.innerHTML = '';

            var drawHeader = 25
            var i = 0;

            if (result.success) {

                var sitesWithoutHtml = [];
                result.sites.forEach(function(site) {

                    if (site.html) {
                        if (i % drawHeader == 0) {
                            displaySitePreviewHeaderElement(site);
                        }
                        displaySitePreviewElement(site);
                        i++;
                    } else {
                        sitesWithoutHtml.push(site.id);
                    }

                });

                var staggerQuery = function() {
                    var group = [];
                    while (sitesWithoutHtml.length > 0 && group.length < 25) {
                        group.push(sitesWithoutHtml.shift());
                    }

                    if (group.length) {

                        (new JsonQuery('site_articles', {
                            sites: group
                        })).addEvent('success', function(result) {

                            result.sites.forEach(function(site) {

                                if (site.html) {

                                    if (i % drawHeader == 0) {
                                        displaySitePreviewHeaderElement(site);
                                    }
                                    displaySitePreviewElement(site);
                                    i++;
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

    function updateCountOfSites() {

        var json = {
            paddlingAreas: [],
            layers: [],
            region: selobj.value
        };

        getCheckboxes().forEach(function(cbx) {

            if (cbx.checked) {
                json.paddlingAreas.push(cbx.value);
            }

        });

        if (json.layers.length == 0) {
            json.layers = "*";
        }
        if (json.paddlingAreas.length == 0) {
            json.paddlingAreas = "*";
        }

        (new JsonQuery('count_sites', json)).addEvent('success', function(result) {

            if (result.success) {
                currentCountSites = result.count;
                currentSelectedSites = result.count;
                displayCountOfSites();
            }
        }).execute();
    }

    function displayCountOfSites() {

        if (currentSelectedSites < currentCountSites) {

            siteCount.innerHTML = " " + currentSelectedSites + "/" + currentCountSites + ' locations';
        } else {

            var s = (currentCountSites == 1 ? '' : 's');
            siteCount.innerHTML = " " + currentCountSites + ' location' + s;
        }

        updateSiteButtons();
    }

    function updateSiteButtons() {

        if (currentCountSites == currentSelectedSites) {
            siteSelectAll.setAttribute('disabled', true);
            siteSelectAll.className = 'btn';
        } else {
            siteSelectAll.removeAttribute('disabled');
            siteSelectAll.className = 'btn btn-info';
        }

        if (currentSelectedSites == 0) {
            siteRemoveAll.setAttribute('disabled', true);
            siteRemoveAll.className = 'btn';
            disableSubmitButtons();
        } else {
            enableSubmitButtons();
            siteRemoveAll.removeAttribute('disabled');
            siteRemoveAll.className = 'btn btn-info';
        }

    }

    siteSelectAll.addEvent('click', function() {
        getSiteCheckboxes().forEach(function(cbx) {
            cbx.checked = true;
            cbx.parentNode.addClass('selected');
        });
        currentSelectedSites = currentCountSites;
        displayCountOfSites();
    })

    siteRemoveAll.addEvent('click', function() {
        getSiteCheckboxes().forEach(function(cbx) {
            cbx.checked = false;
            cbx.parentNode.removeClass('selected');
        });

        currentSelectedSites = 0;
        displayCountOfSites();
    })



    /**
     * Displays a single article card or table row (these are the same thing with css style differences)
     * a site object currently has the format {id:int, html:string, attributes:object, details:object}
     * details contains icon, coordinates, attributes contain all geolive field attribute values
     */
    function displaySitePreviewElement(site) {



        var siteArticle = new Element('div', {
            'id': 'site-' + site.id,
            'class': 'selected'
        });
        siteArticle.innerHTML = site.html;

        var cbx = new Element('input', {
            type: 'checkbox',
            checked: true,
            'class': 'site-ckbx',
            'data-id': site.id
        })

        siteArticle.insertBefore(cbx, siteArticle.childNodes[0]);

        cbx.addEventListener('change', function() {

            if (cbx.checked) {
                siteArticle.addClass('selected');
                currentSelectedSites++;
            } else {
                siteArticle.removeClass('selected');
                currentSelectedSites--
            }
            displayCountOfSites();

        });



        sitePreviewArea.appendChild(siteArticle);

        var attributesEl = new Element('ul');
        Object.keys(site.attributes).forEach(function(name) {
            value = site.attributes[name];
            attributesEl.appendChild(new Element('li', {
                html: value,
                title: value,
                'data-field': name,
                'class': 'atr-' + name
            }));
        });
        attributesEl.appendChild(new Element('li', {
            html: site.details.coordinates.slice(0, 2).map(function(coord) {
                return Math.round(coord * 1000) / 1000.0;

            }).join(', '),
            'data-field': 'coordinates',
            'class': 'atr-coordinates'
        }));
        attributesEl.appendChild(new Element('li', {
            html: '<img title="' + site.details.layer + '" src="' + site.details.icon + '"/>',
            'data-field': 'icon',
            'class': 'atr-icon'
        }));


        var sizeElementFor = ['landingComments', 'campComments'];

        sizeElementFor.forEach(function(name) {
            if (site.attributes[name]) {
                var size = [55, 150, 250]
                var className = ['lng-txt', 'xlng-txt', 'xxlng-txt'];
                var len = site.attributes[name].length;
                for (var i = size.length - 1; i >= 0; i--) {
                    if (len >= size[i]) {
                        siteArticle.addClass(className[i]);
                        break;
                    }
                }
            }
        });
        siteArticle.appendChild(attributesEl);
        return siteArticle;
    }

    function displaySitePreviewHeaderElement(site) {

        var siteArticle = new Element('div', {
            'class': 'tbl-hr'
        });
        siteArticle.innerHTML = '<article><header><h1>Site Name</h1></header></article>';


        sitePreviewArea.appendChild(siteArticle);

        var attributesEl = new Element('ul');
        Object.keys(site.attributes).forEach(function(name) {
            attributesEl.appendChild(new Element('li', {
                html: name.replace(/([A-Z])/g, ' $1').replace(/^./, function(str) {
                    return str.toUpperCase();
                }),
                'data-field': name,
                'class': 'atr-' + name
            }));
        });
        attributesEl.appendChild(new Element('li', {
            html: 'Coordinates (lat, lng)',
            'data-field': 'coordinates',
            'class': 'atr-coordinates'
        }));
        attributesEl.appendChild(new Element('li', {
            html: '',
            'data-field': 'icon',
            'class': 'atr-icon'
        }));

        siteArticle.appendChild(attributesEl);
        return siteArticle;


    }

    function displayPaddlingAreas() {


        var selectedRegion = selobj.value;
        var areaChoicesHtml = "";
        var mapPrefix = '../images/stories/';
        for (var i in regions) {
            if (regions[i].rgName == selectedRegion) {
                var areas = regions[i].areas;

                rgImg.src = mapPrefix + regions[i].image;
                rgImg.alt = regions[i].rgName + " picture";
                for (var j in areas) {
                    if (typeof areas[j].paName !== "undefined") {
                        areaChoicesHtml += '<label><input class="ckbxarea" id="' + areas[j].paName.toLowerCase().replace(' ', '-') + '" type=checkbox name="paddlingAreas[]" value="' + areas[j].paName + '" />' + areas[j].paName + '</label>';
                    }
                }
            }
        }
        paChs.innerHTML = areaChoicesHtml;


        if (selectedRegion == 'choose a region') {
            paInstr.style.display = "none";
            //paSubmit.style.visibility="hidden";
            sitePreviewHeader.style.visibility = "hidden";
            rgImg.src = mapPrefix + 'sixregions.jpg';
        } else {
            paInstr.style.display = "block";
        //paSubmit.style.visibility="visible";
        }

        addSelectedAreasSubmitValidator();
        currentSelectedSites = 0;
        siteCount.innerHTML = "";


        sitePreviewArea.innerHTML = '';
        paSubmitFooter.style.visibility = "hidden";
        sitePreviewHeader.style.visibility = "hidden";

    }


    /**
     * 
     */

    var checked = 0;
    function addSelectedAreasSubmitValidator() {

        var checkboxes = getCheckboxes();
        var buttons = getSubmitButtons();

        checked = 0;

        buttons.forEach(function(btn) {
            btn.setAttribute('disabled', true);
            btn.className = 'btn';
        });


        checkboxes.forEach(function(cbx) {
            cbx.addEventListener("click", function() {

                if (cbx.checked) {
                    checked++;
                } else {
                    checked--;
                }




                updateOutputDisplay();


            });
        });


    }


    function updateOutputDisplay() {


        sitePreviewArea.innerHTML = '';
        paSubmitFooter.style.visibility = "hidden";
        sitePreviewHeader.style.visibility = "hidden";



        if (checked > 0) {
            updateCountOfSites();
            enableSubmitButtons();
        } else {
            siteCount.innerHTML = "";
            disableSubmitButtons();
        }



    }

    function enableSubmitButtons() {

        getSubmitButtons().forEach(function(btn) {

            btn.removeAttribute('disabled');
            if (btn.getAttribute('data-out') === 'preview') {
                btn.className = 'btn btn-primary';
            } else {
                btn.className = 'btn btn-success';
            }

        });

    }

    function disableSubmitButtons() {
        getSubmitButtons().forEach(function(btn) {
            btn.setAttribute('disabled', true);
            btn.className = 'btn';
        });

    }






    var gridView = document.getElementById('gridView');
    var tableView = document.getElementById('tableView');

    gridView.addEvent('click', function() {

        gridView.addClass('btn-primary');
        tableView.removeClass('btn-primary');


        gridView.addClass('active');
        tableView.removeClass('active');

        gridView.firstChild.src = gridView.firstChild.src.split('?')[0] + '?tint=rgba(255,255,255)'
        tableView.firstChild.src = tableView.firstChild.src.split('?')[0] + '?tint=rgb(0, 68, 204)'

        sitePreviewArea.addClass('grid-view');
        sitePreviewArea.removeClass('table-view');



    });

    tableView.addEvent('click', function() {
        tableView.addClass('btn-primary');
        gridView.removeClass('btn-primary');

        tableView.addClass('active');
        gridView.removeClass('active');

        tableView.firstChild.src = tableView.firstChild.src.split('?')[0] + '?tint=rgba(255,255,255)';
        gridView.firstChild.src = gridView.firstChild.src.split('?')[0] + '?tint=rgb(0, 68, 204)';

        sitePreviewArea.addClass('table-view');
        sitePreviewArea.removeClass('grid-view')

    });


    var getOutlets=function(){

        return (iframe.Outlets||iframe.contentWindow.Outlets||false)

    }

    var attachIframeListener = function() {


        getOutlets().onChange(function(mapselector) {

            var region = mapselector.getRegion();
            if (region === null) {
                region = 'choose a region';

            }
            if (selobj.value != region) {

                selobj.value = region;
                displayPaddlingAreas();
            }





            var checkboxes = getCheckboxes();
            var selectedCheckboxes = mapselector.getAreas().map(function(area) {
                var id = area.toLowerCase().replace(' ', '-');
                return $(id);

            });
            var needsupdate = false;
            checkboxes.forEach(function(cbx) {
                if (selectedCheckboxes.indexOf(cbx) >= 0) {

                    if (!cbx.checked) {
                        needsupdate = true;
                        cbx.checked = true;
                    }


                } else {

                    if (cbx.checked) {
                        needsupdate = true;
                        cbx.checked = false;
                    }

                }

            });

            if (needsupdate) {
                checked = selectedCheckboxes.length;
                updateOutputDisplay();
            }



        });

    };

    if (getOutlets()) {
        attachIframeListener();
    } else {
        iframe.addEvent('load', function() {

            console.log('iframe laoded');
            attachIframeListener();
        });
    }



}
