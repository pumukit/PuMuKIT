function comprobar_form_mm(){
    alert('entra');
       
    var msgs = $$('.error');
    for(i = 0; i < msgs.size(); i++){
        valor = $(msgs[i].getAttribute('input')).value;
	if( msgs[i] == null || msgs[i].length == 0 || /^\s+$/.test(msgs[i]) ) {
	    msgs[i].show();
	    return false;
	}
    }

    $$('.error').invoke('show');

    return true;  
}


function comprobar_form_serial(){
    alert('entra');
       
    var msgs = $$('.error');
    for(i = 0; i < msgs.size(); i++){
        valor = $(msgs[i].getAttribute('input')).value;
	if( msgs[i] == null || msgs[i].length == 0 || /^\s+$/.test(msgs[i]) ) {
	    msgs[i].show();
	    return false;
	}
    }

    $$('.error').invoke('show');

    return true;  
}


function comprobar_form_file(){
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

