function sfMediaLibrary_Engine()
{
  // Browser check
  var ua = navigator.userAgent;
  this.isMSIE = (navigator.appName == "Microsoft Internet Explorer");
  this.isMSIE5 = this.isMSIE && (ua.indexOf('MSIE 5') != -1);
  this.isMSIE5_0 = this.isMSIE && (ua.indexOf('MSIE 5.0') != -1);
  this.isGecko = ua.indexOf('Gecko') != -1;
  this.isSafari = ua.indexOf('Safari') != -1;
  this.isOpera = ua.indexOf('Opera') != -1;
  this.isMac = ua.indexOf('Mac') != -1;
  this.isNS7 = ua.indexOf('Netscape/7') != -1;
  this.isNS71 = ua.indexOf('Netscape/7.1') != -1;
  this.isTinyMCE = false;

  // Fake MSIE on Opera and if Opera fakes IE, Gecko or Safari cancel those
  if (this.isOpera) {
    this.isMSIE = true;
    this.isGecko = false;
    this.isSafari =  false;
  }
}

sfMediaLibrary_Engine.prototype = {
  init : function(url)
  {
    this.url = url;
  },

  fileBrowserReturn : function (url)
  {
    if(this.isTinyMCE)
    {
      tinyMCE.setWindowArg('editor_id', this.fileBrowserWindowArg);
      if (this.fileBrowserType == 'image')
      {
        this.fileBrowserWin.showPreviewImage(url);
      }
    }
    this.fileBrowserWin.document.forms[this.fileBrowserFormName].elements[this.fileBrowserFieldName].value = url;
  },

  fileBrowserCallBack : function (field_name, url, type, win)
  {
    this.isTinyMCE = true;
    this.fileBrowserWindowArg = tinyMCE.getWindowArg('editor_id');
    var template = new Array();
    template['title']  = 'Assets';
    var url = this.url;
    if (type == 'image')
      url += '/images_only/1';
    template['file']   = url;
    template['width']  = 550;
    template['height'] = 600;
    template['close_previous'] = 'no';

    this.fileBrowserWin = win;
    this.fileBrowserFormName = 0;
    this.fileBrowserFieldName = field_name;
    this.fileBrowserType = type;
    tinyMCE.openWindow(template, {inline : "yes", scrollbars: 'yes'});
  },

  openWindow : function(options)
  {
    var width, height, x, y, resizable, scrollbars, url;

    if (!options)
      return;
    if (!options['field_name'])
      return;
    if (!options['url'] && !this.url)
      return;
    this.fileBrowserWin = self;
    this.fileBrowserFormName = (options['form_name'] == '') ? 0 : options['form_name'];
    this.fileBrowserFieldName = options['field_name'];
    this.fileBrowserType = options['type'];

    url = this.url;
    if (options['type'] == 'image')
      url += '/images_only/1';

    if (!(width = parseInt(options['width'])))
      width = 550;

    if (!(width = parseInt(options['width'])))
      width = 550;

    if (!(height = parseInt(options['height'])))
      height = 600;

    // Add to height in M$ due to SP2 WHY DON'T YOU GUYS IMPLEMENT innerWidth of windows!!
    if (sfMediaLibrary.isMSIE)
      height += 40;
    else
      height += 20;

    x = parseInt(screen.width / 2.0) - (width / 2.0);
    y = parseInt(screen.height / 2.0) - (height / 2.0);

    resizable = (options && options['resizable']) ? options['resizable'] : "no";
    scrollbars = (options && options['scrollbars']) ? options['scrollbars'] : "no";

    var modal = (resizable == "yes") ? "no" : "yes";

    if (sfMediaLibrary.isGecko && sfMediaLibrary.isMac)
      modal = "no";

    if (options['close_previous'] != "no")
      try {sfMediaLibrary.lastWindow.close();} catch (ex) {}

    var win = window.open(url, "sfPopup" + new Date().getTime(), "top=" + y + ",left=" + x + ",scrollbars=" + scrollbars + ",dialog=" + modal + ",minimizable=" + resizable + ",modal=" + modal + ",width=" + width + ",height=" + height + ",resizable=" + resizable);

    if (options['close_previous'] != "no")
      sfMediaLibrary.lastWindow = win;

    eval('try { win.resizeTo(width, height); } catch(e) { }');

    // Make it bigger if statusbar is forced
    if (sfMediaLibrary.isGecko)
    {
      if (win.document.defaultView.statusbar.visible)
        win.resizeBy(0, sfMediaLibrary.isMac ? 10 : 24);
    }

    win.focus();

  }
}

var SfMediaLibrary = sfMediaLibrary_Engine; // Compatiblity with gzip compressors
var sfMediaLibrary = new sfMediaLibrary_Engine();
