function comprobar_form_serial(date, re_date){
    
    $$('.error').invoke('hide');

    if (comprobar_date(date, re_date)){
	$('error_date').show();
	new Effect.Opacity('serial_save_error', {duration:3.0, from:1.0, to:0.0});
	return false;
    }

    return true;  
}

function comprobar_form_mm(date1, date2, re_date){
    $$('.error').invoke('hide');

    if (comprobar_date(date1, re_date)){
	$('error_date1').show();
	new Effect.Opacity('mm_save_error', {duration:3.0, from:1.0, to:0.0});
	return false;
    }

    if (comprobar_date(date2, re_date)){
	$('error_date2').show();
	new Effect.Opacity('mm_save_error', {duration:3.0, from:1.0, to:0.0});
	return false;
    }

    return true;  
}

function dmy2ymd(date){
  var fecha = date.strip(); // prototype trim
  var separa = fecha.split(" ");
  var parts = separa[0].split("/");
  var ymd = parts[2] + '/' + parts[1] + '/'+parts[0];
  if (separa.length > 1){
    ymd += ' ' + separa[1];
  }
  
  return ymd;
}

function comprobar_form_pub(checked1, start1, end1, checked2, start2, end2, re_date){
    $$('.error').invoke('hide');

    if (checked1){
        if (comprobar_date(start1, re_date)){
        $('error_start1').show();
        new Effect.Opacity('mm_save_error', {duration:3.0, from:1.0, to:0.0});
        return false;
        }

        if (comprobar_date(end1, re_date)){
        $('error_end1').show();
        new Effect.Opacity('mm_save_error', {duration:3.0, from:1.0, to:0.0});
        return false;
        }
        var dstart1 = new Date(Date.parse(dmy2ymd(start1)));
        var dend1   = new Date(Date.parse(dmy2ymd(end1)));
        if (dstart1 > dend1){
        new Effect.Opacity('mm_save_error', {duration:3.0, from:1.0, to:0.0});
        return false;  
        }
    }

    if (checked2){
        if (comprobar_date(start2, re_date)){
        $('error_start2').show();
        new Effect.Opacity('mm_save_error', {duration:3.0, from:1.0, to:0.0});
        return false;
        }

        if (comprobar_date(end2, re_date)){
        $('error_end2').show();
        new Effect.Opacity('mm_save_error', {duration:3.0, from:1.0, to:0.0});
        return false;
        }

        var dstart2 = new Date(Date.parse(dmy2ymd(start2)));
        var dend2   = new Date(Date.parse(dmy2ymd(end2)));
        if (dstart2 > dend2){
        new Effect.Opacity('mm_save_error', {duration:3.0, from:1.0, to:0.0});
        return false;  
        }
    }

    return true;
}

function comprobar_form_pass(){
  var ps1 = $('pass1');
  var ps2 = $('pass2');
  if ( $('broadcast_id').value == 2 ) {
    if ( (ps1.value != ps2.value) || ps1.value == '') {
      return false;
    } else {
      return true;
    }
  } else {
    return true;
  }
}
function comprobar_form_person(email, url){
    
    $$('.error').invoke('hide');

    if ((email != "")&&(comprobar_email(email))){
	$('error_email').show();
	return false;
    }

    if ((url != "")&&(comprobar_url_gen(url))){
	$('error_url').show();
	return false;
    }

    Modalbox.hide();
    return true;  
}


function comprobar_form_url(url){
    
    $$('.error').invoke('hide');

    if (comprobar_url_gen(url)){
	$('error_url').show();
	return false;
    }
    Modalbox.hide();
    return true;  
}

function comprobar_form_file_nmb(file){
    
    $$('.error').invoke('hide');

    if (file == ""){
	$('error_file').show();
	return false;
    }
    return true;  
}


function comprobar_form_place(coorgeo){
    
    $$('.error').invoke('hide');

    if ((coorgeo != "")&&(comprobar_coorgeo(coorgeo))){
	$('error_coorgeo').show();
	return false;
    }

    Modalbox.hide();
    return true;  
}


function comprobar_form_user(login, password, email){
    
    $$('.error').invoke('hide');

    if (login == ""){
	$('error_login').show();
	return false;
    }
    if (password == ""){
	$('error_password').show();
	return false;
    }
    if ((email != "")&&(comprobar_email(email))){
	$('error_email').show();
	return false;
    }
    Modalbox.hide();
    return true;  
}

function comprobar_form_direct(url){
    
    $$('.error').invoke('hide');

    if (comprobar_url_gen(url)){
	$('error_url').show();
	return false;
    }

    Modalbox.hide();
    return true;  
}


function comprobar_form_notice(date, re_date){
    
    $$('.error').invoke('hide');

    if (comprobar_date(date, re_date)){
	$('error_date').show();
	return false;
    }

    Modalbox.hide();
    return true;  
}


function comprobar_form_event(date, re_date, duration){
    
    $$('.error').invoke('hide');

    if (comprobar_date(date, re_date)){
	$('error_date').show();
	return false;
    }

    duration = parseInt(duration);

    if (isNaN(duration)){
	$('error_duration').show();
	return false;
    }

    Modalbox.hide();
    return true;  
}


function comprobar_form_cpu(ip, min, max, num){
    min = parseInt(min);
    max = parseInt(max);
    num = parseInt(num);
    
    $$('.error').invoke('hide');
     
    if (isNaN(max)){
	$('error_max_no_num').show();
	return false;
    }
    if (isNaN(min)){
	$('error_min_no_num').show();
	return false;
    }
    if (isNaN(num)){
	$('error_num_no_num').show();
	return false;
    }
    if (max < 0){
	$('error_max_negativo').show();
	return false;
    }
    if (min < 0){
	$('error_min_negativo').show();
	return false;
    } 
    if (num < 0){
	$('error_num_negativo').show();
	return false;
    }  
    if (max < min) {
	$('error_max').show();
	return false;
    }
    if (num > max){
	$('error_num_sup').show();
	return false;
    }
    if (num < min){
	$('error_num_inf').show();
	return false;
    }
    Modalbox.hide();
    return true;  
}


function comprobar_form_profile(channels, framerate, res1, res2){
  channels = parseInt(channels);
  framerate = parseInt(framerate);
  res1 = parseInt(res1);
  res2 = parseInt(res2);

  
  $$('.error').invoke('hide');
  
  if (isNaN(res1)){
    $('error_resolution_no_num').show();
    return false;
  }
  if (isNaN(res2)){
    $('error_resolution_no_num').show();
    return false;
  }
  if (isNaN(framerate)){
    $('error_framerate_no_num').show();
    return false;
  }
	if (isNaN(channels)){
    $('error_channels_no_num').show();
    return false;
  }
  Modalbox.hide();
  return true;  
}


/*******************************************************
*  
*******************************************************/

function comprobar_ip(ip){
    partes=ip.split('.');
    if (partes.length!=4) {
	return false;
    }
    for (i=0;i<4;i++) {
	numero =partes[i];
	if (numero>255 || numero<0 || numero.length==0 || isNaN(numero)){
	    return false;
	}
    }
    return true;
}


function comprobar_email(email){
    re  = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})$/;
    return !re.test(email);
}


function comprobar_date(date, re){
    return !re.test(date); 
}

function comprobar_url(url){
    var re=/^http:\/\/\w+(\.\w+)*/; 
    return !re.test(url); 
}

function comprobar_url_gen(url){
    var re=/^\w+:\/\/\w+(\.\w+)*/; 
    return !re.test(url); 
}

function comprobar_coorgeo(coorgeo){
    partes=coorgeo.split(',');
    if (partes.length!=2) {
	return true;
    }
    for (i=0;i<2;i++) {
	x =partes[i];
	y = parseFloat(x);
	if (isNaN(y) || x!=y ){
	    return true;
	}
    }
    return false;
}

function comprobar_form_mmwizard_several(url){

    $$('.error').invoke('hide');

    if(url.length  > 2 ) {
      Modalbox.hide();
      return true;
    }

    $('error_no_url').show();   
    return false;
}


function testDates(init, end, re_date) {

 if ((typeof(init) == 'undefined') || (init ==  null) || (init == "")) {
    return true;
 }

 if ((typeof(end) == 'undefined') || (end ==  null) || (end == "")) {
   return true;
  }

  var partes = init.split(" ");
  var fecha = partes[0];
  var hora = partes[1];
  var partes_fecha = fecha.split("/");
  if (typeof(hora) != 'undefined' && hora !=  null) {
      var partes_hora = hora.split(":");	
      var ini = new Date(partes_fecha[2], partes_fecha[1] - 1, partes_fecha[0], partes_hora[0], partes_hora[1]);
  }
  else{
    var ini = new Date(partes_fecha[2], partes_fecha[1] - 1, partes_fecha[0]);
  }

  partes = end.split(" ");
  fecha = partes[0];
  hora = partes[1];
  partes_fecha = fecha.split("/");
  if (typeof(hora) != 'undefined' && hora !=  null) {
      partes_hora = hora.split(":");
      var fi = new Date(partes_fecha[2], partes_fecha[1] - 1 , partes_fecha[0], partes_hora[0], partes_hora[1]);
  }
  else{
      var fi = new Date(partes_fecha[2], partes_fecha[1] - 1 , partes_fecha[0]);
  }

 if (comprobar_date(init, re_date)){
      alert ("El formato de la fecha de inicio no es el adecuado. Utilice dd/mm/aaaa hh:mm");
       return false;
    }
  
 if (comprobar_date(end, re_date)){
      alert ("El formato de fecha de fin no es el adecuado. Utilice dd/mm/aaaa hh:mm");
       return false;
    }

  if (ini.getTime() > fi.getTime()) {
    alert("La fecha inicial es posterior a la fecha final");
    return false;
  }

  Modalbox.hide();
  return true;  
}

function genpasswd(obj1, obj2) {
  var passwd = '';
  var randomchar = '';
  var numberofdigits = Math.floor((Math.random() * 7) + 6 );
  $('passwdFail').hide();//Borramos contraseña para currarnos en salud

  for (var count=1; count<=numberofdigits; count++) {
    var chargroup = Math.floor((Math.random() * 3) + 1 );
    if ( chargroup == 1 ) {
      randomchar = Math.floor((Math.random() * 26) + 65);
    }
    if ( chargroup == 2 ) {
      randomchar = Math.floor((Math.random() * 26) + 65);
    }
    if ( chargroup == 3 ) {
      randomchar = Math.floor((Math.random() * 26) + 65);
    }
    passwd+=String.fromCharCode(randomchar);
  }

  obj1.value = passwd;
  obj2.value = passwd;
}

change_pass = function(obj)
{
  if ( obj.value == 2 ) { 
     $('broadcast_password').show(); 
  } else { 
    $('broadcast_password').hide(); 
  }
}

function check_pass(pass1, pass2, control) {
  if ( pass1.value == '' && control == 2 ) {
     $('passwdFail').innerHTML = 'La contraseña no puede estar vacía.';
     $('passwdFail').show();
     return false;
  }
  if ( pass1.value == pass2.value || control == 1 ) {
     $('passwdFail').hide();
     return true;
  }  else {
     $('passwdFail').innerHTML = 'Las contraseñas deben ser iguales.';//Por si no se recarga la página y ya mostró el error de vacía
     $('passwdFail').show();
     return false;
  }
}

function replaceType(obj) {
  var newO=document.createElement('input');

  if (obj.getAttribute('type') == 'password') {
    newO.setAttribute('type','text');
  } else {
    newO.setAttribute('type','password');
  }
  newO.setAttribute('id',obj.getAttribute('id'));
  newO.setAttribute('size',obj.getAttribute('size'));
  newO.setAttribute('maxlength',obj.getAttribute('maxlength'));
  newO.setAttribute('name',obj.getAttribute('name'));
  newO.setAttribute('style',obj.getAttribute('style'));
  newO.setAttribute('placeholder',obj.getAttribute('placeholder'));
  newO.setAttribute('value',obj.value);
  newO.setAttribute('onchange',obj.getAttribute('onchange'));
  obj.parentNode.replaceChild(newO,obj);
  if (obj.getAttribute('id') == 'pass1' ){
    newO.focus();
  }
}
function toggleName(obj) {
  if ( obj.value == 'Ver contraseña') {
    obj.value = 'Ocultar contraseña';
  } else {
    obj.value = 'Ver contraseña';
  }
}

function comprobar_query(query){

if (query.checked) {
 
 $('error_email').hide();

 if (comprobar_email($('emailQuery').value)) {
     $('error_email').show();
     return false;
   }
   else {
	return true;	
    }
  }
 
  return true;
}
