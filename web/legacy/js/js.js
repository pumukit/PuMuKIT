//
var menuTabClass = new Class.create();
menuTabClass.prototype = {
    initialize : function(hash){
        var q = 'metaMm';
        if ((['#pubMmHash', '#metaMmHash', '#categoryMmHash', '#personMmHash', '#mediaMmHash'].indexOf(hash)) != -1){
          q =hash.substring(1, hash.length - 4);
        }
	this.activo = q;
	Element.addClassName($(q), 'siSel');
	$(q + 'Div').show();
        document.location.hash = q + 'Hash';
    },
    
    select : function(q){
	$(this.activo + 'Div').hide();
	this.activo = q;
	Element.removeClassName($$('.siSel').first(), 'siSel');
	Element.addClassName($(q), 'siSel');
 	//new Ajax.Updater('body', q+'.html');
	$(q + 'Div').show();
        document.location.hash = q + 'Hash';
    }
};

//MIERDA
var menuTab2Class = new Class.create();
menuTab2Class.prototype = {
    initialize : function(hash){
        var q = 'metaMmTemplate';
	this.activo = q;
	Element.addClassName($(q), 'siSelMmTemplate');
	$(q + 'Div').show();
    },
    
    select : function(q){
	$(this.activo + 'Div').hide();
	this.activo = q;
	Element.removeClassName($$('.siSelMmTemplate').first(), 'siSelMmTemplate');
	Element.addClassName($(q), 'siSelMmTemplate');
 	//new Ajax.Updater('body', q+'.html');
	$(q + 'Div').show();
    }
};


//
window.click_fila = function(element, tr, id)
{
  new Ajax.Updater('preview_'+element, '/editar.php/'+element+'s/preview/id/'+id, {
      asynchronous: true, 
      evalScripts: true
  });

  //$('list_'+element+'s').getElementsByClassName('tv_admin_row_this').invoke('removeClassName', 'tv_admin_row_this');
  $('list_'+element+'s').select('.tv_admin_row_this').invoke('removeClassName', 'tv_admin_row_this');
  tr.parentNode.addClassName('tv_admin_row_this');
}

//
window.click_fila_place = function(tr, id)
{
  new Ajax.Updater('list_precincts', '/editar.php/precincts/list/place/'+id, {
      asynchronous: true, 
      evalScripts: true
  });

  $$('.tv_admin_row_this').invoke('removeClassName', 'tv_admin_row_this');
  tr.parentNode.addClassName('tv_admin_row_this');
}

//
window.click_fila_edit = function(element, tr, id)
{
  new Ajax.Updater('preview_'+element, '/editar.php/'+element+'s/preview/id/'+id, {
      asynchronous: true, 
      evalScripts: true
  });

  new Ajax.Updater('edit_'+element+'s', '/editar.php/'+element+'s/edit/id/'+id, {
      asynchronous: true, 
      evalScripts: true
  });


  $$('.tv_admin_row_this').invoke('removeClassName', 'tv_admin_row_this');
  if (tr != null) tr.parentNode.addClassName('tv_admin_row_this');
}

//
window.dblclick_fila = function(element, tr, id)
{
  document.location.href= ('/editar.php/mms/index/serial/'+id);
}

//
window.click_fila_np = function(element, tr, id)
{
  new Ajax.Request('/editar.php/'+element+'s/preview/id/'+id, {asynchronous: true,  evalScripts: true});

  $$('.tv_admin_row_this').invoke('removeClassName', 'tv_admin_row_this');
  tr.parentNode.addClassName('tv_admin_row_this');
}


//
window.click_checkbox_all = function(element, value)
{
  $('list_'+element+'s').select('input.'+element+'_checkbox').each(function(s){s.checked=value});
}


//
window.change_select = function(elemento, selector)
{
    var i = selector.getValue(); 
    switch(i){
    case 'transc_pause':
        if (confirm('Pausar videos')) {
            new Ajax.Updater('list_transcoders', '/editar.php/transcoders/pause', {
		            asynchronous: true,
		            evalScripts: true,
                onSuccess:  selector.selectedIndex = 0,
		            parameters: 'ids='+$$('.transcoder_checkbox:checked').invoke('getAttribute', 'id').toJSON()}
			    );
        }
        break;
    case 'transc_play':
        if (confirm('Reanudar videos')) {
            new Ajax.Updater('list_transcoders', '/editar.php/transcoders/continue', {
		            asynchronous: true,
		            evalScripts: true,
                onSuccess:  selector.selectedIndex = 0,
		            parameters: 'ids='+$$('.transcoder_checkbox:checked').invoke('getAttribute', 'id').toJSON()}
			    );
        }
        break;
    case 'transc_borrar':
        if (confirm('Borrar objetos multimedia')) {
            new Ajax.Updater('list_transcoders', '/editar.php/transcoders/delete', {
		asynchronous: true,
		evalScripts: true,
                onSuccess:  selector.selectedIndex = 0,
		parameters: 'ids='+$$('.transcoder_checkbox:checked').invoke('getAttribute', 'id').toJSON()}
			    );
        }
        break;
    case 'transc_limpiar':
        if (confirm('Limpiar videos completados del listado')) {
            new Ajax.Updater('list_transcoders', '/editar.php/transcoders/clean', {
		            asynchronous: true,
		            evalScripts: true,
                onSuccess:  selector.selectedIndex = 0,
		            parameters: 'ids='+$$('.transcoder_checkbox:checked').invoke('getAttribute', 'id').toJSON()}
			    );
        }
        break;
      //borrar trans
    case 'delete_sel':
        seleccionados = $$('.' + elemento + '_checkbox:checked');
        if (seleccionados.length == 0) break;
        if (confirm('Seguro')) {
            new Ajax.Updater('list_'+elemento+'s', '/editar.php/'+elemento+'s/delete', {
		asynchronous: true,
		evalScripts: true,
                onSuccess:  selector.selectedIndex = 0,
		parameters: 'ids='+seleccionados.invoke('getAttribute', 'id').toJSON()}
			    );
        }
        break;
    case 'create':
        Modalbox.show(elemento+'s/create', {title:'Editar Nueva '+elemento, width:800}); 
        selector.selectedIndex = 0;
        break;
    case 'serial_preview':
        window.open('/editar.php/serials/previewall');
        selector.selectedIndex = 0;
        break;
    case 'serial_master':
	Modalbox.show('/editar.php/transcoders/masterserial', {title:'Master Multimedia', width:800});
        selector.selectedIndex = 0;
        break;
      //separar honores sel
    case 'hono_person_sel':
        new Ajax.Updater('list_'+elemento+'s', '/editar.php/'+elemento+'s/separe', {
	    asynchronous: true,
	    evalScripts: true,
            onSuccess:  selector.selectedIndex = 0,
	    parameters: 'ids='+$$('.'+elemento+'_checkbox:checked').invoke('getAttribute', 'id').toJSON()}
			);
        break;
      //separar honores sel
    case 'hono_person_all':
        new Ajax.Updater('list_'+elemento+'s', '/editar.php/'+elemento+'s/separe', {
	    asynchronous: true,
	    evalScripts: true,
            onSuccess:  selector.selectedIndex = 0,
	    parameters: 'all=1'}
			);
        break;
        //unificar varias personas en una
    case 'merge_person_sel':
      var ids = $$('.'+elemento+'_checkbox:checked').invoke('getAttribute', 'id');
      if (ids.length < 2 ){
        alert ('No ha seleccionado suficientes personas para unificar');
        break;
      }
      var seleccionados = [];
      var texto_selecc = '';
      for (var i = 0; i < ids.length; i++){
        // seleccionados[ids[i]] = $(ids[i]).parentElement.parentElement.children[5].textContent.strip();
        var nombre = $(ids[i]).parentElement.parentElement.children[5].textContent.strip();
        var videos = $(ids[i]).parentElement.parentElement.children[8].textContent.strip();
        seleccionados[i] = {'nombre' : nombre, 'videos': videos};
      }
      seleccionados.sort(function(a,b) {
        if (parseInt(a.videos,10) == parseInt(b.videos,10)){
          return a.nombre.length - b.nombre.length;
        } else {
          return parseInt(a.videos,10) - parseInt(b.videos,10);
        }
      });
      for (var i = 0; i < ids.length; i++){
        str_videos = (1 == seleccionados[i].videos) ? ' vídeo)\n' : ' vídeos)\n';
        texto_selecc += ((ids.length - 1) != i)? '\t\t\t' + seleccionados[i].nombre + '\t(' + seleccionados[i].videos + str_videos : 'dentro de: ' + seleccionados[i].nombre + '\t(' + seleccionados[i].videos + str_videos;
      }
      if (confirm('Unificar las personas: \n' + texto_selecc)) {
        new Ajax.Updater('list_'+elemento+'s', '/editar.php/'+elemento+'s/merge', {
          asynchronous: true,
          evalScripts: true,
          onSuccess:  selector.selectedIndex = 0,
          parameters: 'ids='+$$('.'+elemento+'_checkbox:checked').invoke('getAttribute', 'id').toJSON()}
        );
      }
      break;
      //separar honores sel
    case 'inv_working_sel':
        new Ajax.Updater('list_'+elemento+'s', '/editar.php/'+elemento+'s/working', {
	    asynchronous: true,
	    evalScripts: true,
            onSuccess:  selector.selectedIndex = 0,
	    parameters: 'ids='+$$('.'+elemento+'_checkbox:checked').invoke('getAttribute', 'id').toJSON()}
			);
        break;
      //separar honores sel
    case 'inv_working_all':
        new Ajax.Updater('list_'+elemento+'s', '/editar.php/'+elemento+'s/working', {
	    asynchronous: true,
	    evalScripts: true,
            onSuccess:  selector.selectedIndex = 0,
	    parameters: 'all=1'}
			);
        break;
    //order mm
    case 'set_order_rec_asc':
        new Ajax.Updater('list_'+elemento+'s', '/editar.php/'+elemento+'s/orderby/type/rec_asc', {
	    asynchronous: true,
	    evalScripts: true,
            onSuccess:  selector.selectedIndex = 0,
	    parameters: 'serial=' + $('mms_serial_id').value}
			);
        break;
    //order mm
    case 'set_order_rec_des':
        new Ajax.Updater('list_'+elemento+'s', '/editar.php/'+elemento+'s/orderby/type/rec_des', {
	    asynchronous: true,
	    evalScripts: true,
            onSuccess:  selector.selectedIndex = 0,
	    parameters: 'serial=' + $('mms_serial_id').value}
			);
        break;    //order mm
    case 'set_order_pub_asc':
        new Ajax.Updater('list_'+elemento+'s', '/editar.php/'+elemento+'s/orderby/type/pub_asc', {
	    asynchronous: true,
	    evalScripts: true,
            onSuccess:  selector.selectedIndex = 0,
	    parameters: 'serial=' + $('mms_serial_id').value}
			);
        break;    //order mm
    case 'set_order_pub_des':
        new Ajax.Updater('list_'+elemento+'s', '/editar.php/'+elemento+'s/orderby/type/pub_des', {
	    asynchronous: true,
	    evalScripts: true,
            onSuccess:  selector.selectedIndex = 0,
	    parameters: 'serial=' + $('mms_serial_id').value}
			);
        break;
    case 'cut_mm':
        seleccionados = $$('.mm_checkbox:checked');
        if (seleccionados.length == 0) break;
        new Ajax.Updater('list_'+elemento+'s', '/editar.php/'+elemento+'s/cut', {
	    asynchronous: true,
	    evalScripts: true,
            onSuccess:  selector.selectedIndex = 0,
	    parameters: 'ids='+seleccionados.invoke('getAttribute', 'id').toJSON()}
			);
        break;
    case 'paste_mm':
        if (confirm('Pegar objeto multimedia')) {
            new Ajax.Updater('list_'+elemento+'s', '/editar.php/'+elemento+'s/paste', {
		asynchronous: true,
		evalScripts: true,
		onSuccess:  selector.selectedIndex = 0,
		parameters: 'serial=' + $('mms_serial_id').value}
			    );
	}
        break;
      //status mm
    case 'set_status_0_sel':
        new Ajax.Updater('list_'+elemento+'s', '/editar.php/'+elemento+'s/change/status/0', {
	    asynchronous: true,
	    evalScripts: true,
            onSuccess:  selector.selectedIndex = 0,
	    parameters: 'ids='+$$('.'+elemento+'_checkbox:checked').invoke('getAttribute', 'id').toJSON()}
			);
        break;
    case 'set_status_1_sel':
        new Ajax.Updater('list_'+elemento+'s', '/editar.php/'+elemento+'s/change/status/1', {
	    asynchronous: true,
	    evalScripts: true,
            onSuccess:  selector.selectedIndex = 0,
	    parameters: 'ids='+$$('.'+elemento+'_checkbox:checked').invoke('getAttribute', 'id').toJSON()}
			);
        break;
    case 'set_status_2_sel':
        new Ajax.Updater('list_'+elemento+'s', '/editar.php/'+elemento+'s/change/status/2', {
	    asynchronous: true,
	    evalScripts: true,
            onSuccess:  selector.selectedIndex = 0,
	    parameters: 'ids='+$$('.'+elemento+'_checkbox:checked').invoke('getAttribute', 'id').toJSON()}
			);
        break;
    case 'set_status_3_sel':
        new Ajax.Updater('list_'+elemento+'s', '/editar.php/'+elemento+'s/change/status/3', {
	    asynchronous: true,
	    evalScripts: true,
            onSuccess:  selector.selectedIndex = 0,
	    parameters: 'ids='+$$('.'+elemento+'_checkbox:checked').invoke('getAttribute', 'id').toJSON()}
			);
        break;
    case 'inv_announce_sel':
        new Ajax.Updater('list_'+elemento+'s', '/editar.php/'+elemento+'s/inv/field/announce', {
	    asynchronous: true,
	    evalScripts: true,
            onSuccess:  selector.selectedIndex = 0,
	    parameters: 'ids='+$$('.'+elemento+'_checkbox:checked').invoke('getAttribute', 'id').toJSON()}
			);
        break;
    case 'inv_announce_virtualserial_sel':
        new Ajax.Updater('list_mms', '/editar.php/virtualserial/inv/field/announce', {
            asynchronous: true,
            evalScripts: true,
            onSuccess:  selector.selectedIndex = 0,
            parameters: 'ids='+$$('.'+elemento+'_checkbox:checked').invoke('getAttribute', 'id').toJSON()}
                        );
        break;
    }
  selector.selectedIndex = 0;

}


String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g,"");
}
