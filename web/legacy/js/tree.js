/**
 *  Crear el arbol que explora los archivos del servidor.
 *   -valueUrl: Identificador del elemento a actualizar
 *   -valueFile: Identificador del elemento a actualizar
 *   -dir: direcion del archivo
 *   -isFile: booelan que indica si el la direcion de dir en un directorio o un archivo
 **/
function fileServerTree(valueUl, valueFile, dir, isFile, div)
{

    var ul = valueUl.next();
    if (isFile){
	$(valueFile).value = dir;
	Effect.toggle(div, 'blind', { afterFinish:function(){ Modalbox.resizeToContent() }});
    }else{
	if (ul.innerHTML == '') {
	    valueUl.scrollIntoView();
	    new Ajax.Request('/editar.php/navigator/list', { /*urlencode*/
		method : 'post',
		parameters: 'dir=' + encodeURIComponent(dir),
		asynchronous:true, 
		evalScripts:true,
		onSuccess: function(res) {
		    var json_res = res.responseText.evalJSON();
		    for(i = 0; i < json_res.dirs.length; i++){
			var ul2 = new Element('ul', {id: ul.id + json_res.dirs[i] });
			var span = new Element('span', {
			    onclick: 'fileServerTree(this, "' + valueFile + '", "' + dir + "/" + json_res.dirs[i] + '", 0, "' + div + '")' }
					      ).update(json_res.dirs[i]);
			var li = new Element('li', { 'class': 'collapsed'});

			li.insert(span).insert(ul2);
			ul.insert(li);
		    }
		    for(i = 0; i < json_res.files.length; i++){
			var span = new Element('span', {
			    onclick: 'fileServerTree(this, "' + valueFile + '", "' + dir + "/" + json_res.files[i] + '", 1,"' + div + '")' }
					      ).update(json_res.files[i]);
			var li = new Element('li', { 'class': 'element'});

			li.insert(span);
			ul.insert(li);
		    }
		}
	    });
	    ul.parentNode.className = 'expanded';
	}else{
	    ul.innerHTML = '';
	    ul.parentNode.className = 'collapsed';
	}
    }
}





/**
 *  Crear el arbol que explora los archivos del servidor.
 *   -valueUrl: Identificador del elemento a actualizar
 *   -valueFile: Identificador del elemento a actualizar
 *   -dir: direcion del archivo
 *   -isFile: booelan que indica si el la direcion de dir en un directorio o un archivo
 **/
function dirServerTree(valueUl, valueFile, dir, isFile, div)
{

    var ul = valueUl.next();
    if (isFile){
	$(valueFile).value = dir;
	Effect.toggle('explorer_videoserv', 'blind', { afterFinish:function(){ Modalbox.resizeToContent() }});
    }else{
	if (ul.innerHTML == '') {
	    valueUl.scrollIntoView();
	    new Ajax.Request('/editar.php/navigator/list', { /*urlencode*/
		method : 'post',
		parameters: 'dir=' + encodeURIComponent(dir),
		asynchronous:true, 
		evalScripts:true,
		onSuccess: function(res) {
		    var json_res = res.responseText.evalJSON();
		    var span = new Element('span', {
			onclick: 'fileServerTree(this, "' + valueFile + '", "' + dir + '", 1,"' + div + '")' }
					  ).update('Este directorio (' + json_res.files.length + ' elem.)' );
		    var li = new Element('li', { 'class': 'element'});
		    
		    li.insert(span);
		    ul.insert(li);
		   
		    for(i = 0; i < json_res.dirs.length; i++){
			var ul2 = new Element('ul', {id: ul.id + json_res.dirs[i] });
			var span = new Element('span', {
			    onclick: 'dirServerTree(this, "' + valueFile + '", "' + dir + "/" + json_res.dirs[i] + '", 0,"' + div + '")' }
					      ).update(json_res.dirs[i]);
			var li = new Element('li', { 'class': 'collapsed'});

			li.insert(span).insert(ul2);
			ul.insert(li);
		    }
		}
	    });
	    ul.parentNode.className = 'expanded';
	}else{
	    ul.innerHTML = '';
	    ul.parentNode.className = 'collapsed';
	}
    }
}



/**
 *  Crear el arbol que explora los archivos del servidor. Lo mismo que fileServerTree sin animaciones.
 *   -valueUrl: Identificador del elemento a actualizar
 *   -valueFile: Identificador del elemento a actualizar
 *   -dir: direcion del archivo
 *   -isFile: booelan que indica si el la direcion de dir en un directorio o un archivo
 **/
function fileServerTree2(valueUl, valueFile, dir, isFile, div)
{

    var ul = valueUl.next();
    if (isFile){
	$(valueFile).value = dir;
    }else{
	if (ul.innerHTML == '') {
	    valueUl.scrollIntoView();
	    new Ajax.Request('/editar.php/navigator/list', { /*urlencode*/
		method : 'post',
		parameters: 'dir=' + encodeURIComponent(dir),
		asynchronous:true, 
		evalScripts:true,
		onSuccess: function(res) {
		    var json_res = res.responseText.evalJSON();
		    for(i = 0; i < json_res.dirs.length; i++){
			var ul2 = new Element('ul', {id: ul.id + json_res.dirs[i] });
			var span = new Element('span', {
			    onclick: 'fileServerTree2(this, "' + valueFile + '", "' + dir + "/" + json_res.dirs[i] + '", 0, "' + div + '")' }
					      ).update(json_res.dirs[i]);
			var li = new Element('li', { 'class': 'collapsed'});

			li.insert(span).insert(ul2);
			ul.insert(li);
		    }
		    for(i = 0; i < json_res.files.length; i++){
			var span = new Element('span', {
			    onclick: 'fileServerTree2(this, "' + valueFile + '", "' + dir + "/" + json_res.files[i] + '", 1,"' + div + '")' }
					      ).update(json_res.files[i]);
			var li = new Element('li', { 'class': 'element'});

			li.insert(span);
			ul.insert(li);
		    }
		}
	    });
	    ul.parentNode.className = 'expanded';
	}else{
	    ul.innerHTML = '';
	    ul.parentNode.className = 'collapsed';
	}
    }
}




/**
 *  Crear el arbol que explora los archivos del servidor. Lo mismo que dirServerTree sin animaciones.
 *   -valueUrl: Identificador del elemento a actualizar
 *   -valueFile: Identificador del elemento a actualizar
 *   -dir: direcion del archivo
 *   -isFile: booelan que indica si el la direcion de dir en un directorio o un archivo
 **/
function dirServerTree2(valueUl, valueFile, dir, isFile, div)
{

    var ul = valueUl.next();
    if (isFile){
	$(valueFile).value = dir;
    }else{
	if (ul.innerHTML == '') {
	    valueUl.scrollIntoView();
	    new Ajax.Request('/editar.php/navigator/list', { /*urlencode*/
		method : 'post',
		parameters: 'dir=' + encodeURIComponent(dir),
		asynchronous:true, 
		evalScripts:true,
		onSuccess: function(res) {
		    var json_res = res.responseText.evalJSON();
		    var span = new Element('span', {
			onclick: 'fileServerTree2(this, "' + valueFile + '", "' + dir + '", 1,"' + div + '")' }
					  ).update('Este directorio (' + json_res.files.length + ' elem.)' );
		    var li = new Element('li', { 'class': 'element'});
		    
		    li.insert(span);
		    ul.insert(li);
		   
		    for(i = 0; i < json_res.dirs.length; i++){
			var ul2 = new Element('ul', {id: ul.id + json_res.dirs[i] });
			var span = new Element('span', {
			    onclick: 'dirServerTree2(this, "' + valueFile + '", "' + dir + "/" + json_res.dirs[i] + '", 0,"' + div + '")' }
					      ).update(json_res.dirs[i]);
			var li = new Element('li', { 'class': 'collapsed'});

			li.insert(span).insert(ul2);
			ul.insert(li);
		    }
		}
	    });
	    ul.parentNode.className = 'expanded';
	}else{
	    ul.innerHTML = '';
	    ul.parentNode.className = 'collapsed';
	}
    }
}