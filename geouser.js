// trim
if (!String.prototype.trim) {
  String.prototype.trim = function () {
    return this.replace(/^\s+|\s+$/g, '');
  };
}



jQuery(document).ready(function($){

var _map_id = 'geouser-map';
var _map = $('#' + _map_id);
var _search = $('#geouser-locatization .regular-text');
var _lat = $('#input_shandora_listing_maplatitude > input');
var _lng = $('#input_shandora_listing_maplongitude > input');
var _allowedLvl1 = ['ro','ac','am','rr','pa','ap','to','ma','pi','ce','rn','pb','pe','al','se','ba','mg','es','rj','sp','pr','sc','rs','ms','mt','go','df'];
var city;
var uf;

if (!_map.length)
    return false;

var initial_center;
if (_lat.val() && _lng.val()) {
    initial_center = new google.maps.LatLng(_lat.val(), _lng.val());
    initial_zoom = 16;
} else {
    initial_center = new google.maps.LatLng(geouser.initial_lat, geouser.initial_lng);
    initial_zoom = 4;
}

var options = {
    center: initial_center,
    zoom: initial_zoom,
    maxZoom: 17,
    mapTypeId: google.maps.MapTypeId.ROADMAP,
    mapTypeControl: false,
    panControl: false,
    streetViewControl: false,
    zoomControlOptions: { style: google.maps.ZoomControlStyle.SMALL }
};

var map = new google.maps.Map(document.getElementById(_map_id), options);
var geocoder = new google.maps.Geocoder();
var marker;

google.maps.event.addDomListener(window, 'load', function(e) {
    if (!_lat.val() || !_lng.val())
        return false;
    marker = new google.maps.Marker({
        map: map,
        draggable: true,
        position: initial_center
    });
});

// Adiciona o listener pra atualizar o mapa e 
// as infos quando o usuário clica no mapa
google.maps.event.addListener(map, 'click', function(e) {
    geouser_addmarker(e.latLng.lat(), e.latLng.lng());
    geouser_update_latlon(e.latLng.lat(), e.latLng.lng());
    geouser_geocode(e.latLng, 'addr');
});

// Compila as informações para busca quando 
// o usuário escreve no formulário
$('.regular-text').on('keyup', function() {

    fulltext = $('.regular-text');
    stext = [];

    $.each(fulltext, function(i,v) {
        stext.push( $(this).val() );
    });
    
    geouser_geocode( stext.join(' ') , 'coord');

});

function geouser_update_latlon(lat, lng) {
    _lat.val(lat);
    _lng.val(lng);
}

function geouser_update_uf_city(props) {
    var flag = false;
    var ufObj;

    $.each(props, function(i,v) {
        liVal = $(this).text().trim();
        
        // estamos dentro dos estados permitidos?
        if($.inArray(liVal.toLowerCase(), _allowedLvl1) > -1) {

            // estamos dentro do estado da cidade selecionada?
            if(liVal == uf) {
                ufObj = $(this);
                $(this).find('input').prop('checked', true);

                inprops = $(this).siblings('.children').find('li > label');
                
                if(inprops.length > 0) {
                    $.each(inprops, function(i,v) {
                        liliVal = $(this).text().trim();
                        
                        // a cidade selecionadas já existe na lista?
                        // se sim, marque
                        if(liliVal == city) {
                            flag = true;
                            $(this).find('input').prop('checked', true);

                            return false;
                        }
                    });
                }
                
                if(!flag) {
                    if(confirm(city+'-'+uf+' não encontrado. Adicionar?')) {
                            
                            $.post(geouser.ajaxurl, {
                                action: 'ecotemporadas_register_taxonomy',
                                uf: uf,
                                city: city
                            }, function(response) {
                                
                                if(response.status == 'ok') {
                                    children = ufObj.siblings('.children');
                                    if(children.length < 1)
                                        ufObj.after('<ul class="children"></ul>');

                                    ufObj.siblings('.children').append('<label class="selectit"><input checked="checked" value="'+response.term_id+'" type="checkbox" name="tax_input[property-location][]" id="in-property-location-'+response.term_id+'"> '+response.name+'</label>');

                                    alert(city+'-'+uf+' adicionada.');
                                }
                            
                            });                        
                    }
                    
                    return false;                    
                }    
            }            

        } else {
            alert('A localidade deve estar dentro do país');
            return false;
        }
    });
}

function geouser_addmarker(lat, lng) {

    center = new google.maps.LatLng(lat, lng);

    if (marker) {
        marker.setPosition(center);
    } else {
        marker = new google.maps.Marker({
            map: map,
            draggable: true,
            position: new google.maps.LatLng(lat, lng)
        });

        marker.setPosition(center);
    }

    // Adiciona o listener pra atualizar o mapa e 
    // as infos quando arrasta o marker
    google.maps.event.addListener(marker, 'dragend', function(e) {
        geouser_addmarker(e.latLng.lat(), e.latLng.lng());
        geouser_update_latlon(e.latLng.lat(), e.latLng.lng());
        geouser_geocode(e.latLng, 'addr');
    });
}

/**
*   type = coord OU addr
*/
function geouser_geocode(val, type) {

    var location;

    if(typeof(type) === 'undefined')
        type = 'coord';


    if(type == 'coord') {
        geocoder.geocode({'address': val}, function(results){
            if (!results || !results.length)
                return false;
            
            location = results[0].geometry.location;
            map.setCenter(location);
            map.setZoom(16);

            geouser_update_latlon(location.lat(), location.lng());
            geouser_addmarker(location.lat(), location.lng());
            
            

        });        
    } else if(type == 'addr') {
        geocoder.geocode({'location': val}, function(results){
            if (!results || !results.length)
                return false;
            
            location = results[0].geometry.location;
            comp = results[0].address_components;
            map.setCenter(location);
            map.setZoom(16);         
            
            geouser_update_latlon(location.lat(), location.lng());
            geouser_addmarker(location.lat(), location.lng());

            $('#geouser .regular-text').val('');
            // Campos separados por tipo de endereço
            $.each(comp, function(i,v) {
                if('administrative_area_level_1' == comp[i].types[0]) {
                    uf = comp[i].short_name;
                    $('#geouser-search-'+comp[i].types[0]).val(comp[i].short_name);
                } else {
                    if('administrative_area_level_2' == comp[i].types[0])
                        city = comp[i].long_name;
                    $('#geouser-search-'+comp[i].types[0]).val(comp[i].long_name);
                }
                
            });

            $('#property-locationchecklist input').attr('checked', false);
            props = $('#property-locationchecklist > li > label');
            geouser_update_uf_city(props);

            _lat.val(location.lat().toString());
            $('#geouser-search').val();
            $('#geouser-search').val(results[0].formatted_address);

            


        });                
    }
}

});
