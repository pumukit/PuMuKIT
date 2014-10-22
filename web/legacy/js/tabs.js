var varienTabs = new Class.create();

varienTabs.prototype = {
    initialize : function(element, containerId, destElementId,  activeTabId){
        this.containerId    = containerId;
        this.destElementId  = destElementId;
	this.element = element;
        this.activeTab = null;
        
        this.tabOnClick = this.tabMouseClick.bindAsEventListener(this);
        
        this.tabs = $$('#'+this.containerId+' li a.tab-item-link');
        
        this.hideAllTabsContent();
        for(var i = 0; i<this.tabs.size(); i++) {
        //for(var tab in this.tabs){
            Event.observe(this.tabs[i],'click',this.tabOnClick);
            // move tab contents to destination element
            if($(this.destElementId)){
                var tabContentElement = $(this.getTabContentElementId(this.tabs[i]));
                if(tabContentElement && tabContentElement.parentNode.id != this.destElementId){
                    $(this.destElementId).appendChild(tabContentElement);
                    tabContentElement.container = this;
                    tabContentElement.statusBar = this.tabs[i];
                    tabContentElement.tabObject  = this.tabs[i];
                    this.tabs[i].contentMoved = true;
                    this.tabs[i].container = this;
                    this.tabs[i].show = function(){
                        this.container.showTabContent(this);
                    }
                }
            }
        }
        this.showTabContent($(activeTabId));
        Event.observe(window,'load',this.moveTabContentInDest.bind(this));
    },
    
    moveTabContentInDest : function(){
        for(var i = 0; i<this.tabs.size(); i++) {
        //for(var tab in this.tabs){
            if($(this.destElementId) &&  !this.tabs[i].contentMoved){
                var tabContentElement = $(this.getTabContentElementId(this.tabs[i]));
                if(tabContentElement && tabContentElement.parentNode.id != this.destElementId){
                    $(this.destElementId).appendChild(tabContentElement);
                    tabContentElement.container = this;
                    tabContentElement.statusBar = this.tabs[i];
                    tabContentElement.tabObject  = this.tabs[i];
                    this.tabs[i].container = this;
                    this.tabs[i].show = function(){
                        this.container.showTabContent(this);
                    }
                }
            }
        }
    },
    
    getTabContentElementId : function(tab){
        if(tab){
            return tab.id+'_content';
        }
        return false;
    },
    
    tabMouseClick : function(event){
        var tab = Event.findElement(event, 'a');
        if(tab.href.indexOf('#') != tab.href.length-1){
            if(Element.hasClassName(tab, 'ajax')){
                
            }
            else{
                location.href = tab.href;
            }
        }
        else {
            this.showTabContent(tab);
        }
        
        Event.stop(event);
    },
    
    hideAllTabsContent : function(){
        for(var i = 0; i<this.tabs.size(); i++) {
        //for(var tab in this.tabs){
            this.hideTabContent(this.tabs[i]);
        }
    },
    
    showTabContent : function(tab){
        this.hideAllTabsContent();
        var tabContentElement = $(this.getTabContentElementId(tab));
        if(tabContentElement){
            Element.show(tabContentElement);
            //new Effect.Appear(tabContentElement, {duration :0.3});
            Element.addClassName(tab, 'active');
            this.activeTab = tab;

	    //Mio
	    new Ajax.Request('/editar.php/'+this.element+'/id/'+ tab.getAttribute('idElement'), {asynchronous: true, evalScripts: true});

	    if(this.element == 'grounds/previewtype'){
		$$('.list_grounds').invoke('setAttribute', 'id', '');
		$$('.list_grounds_'+tab.getAttribute('idElement')).first().id='list_grounds';
	    }
        }
    },
    
    hideTabContent : function(tab){
        var tabContentElement = $(this.getTabContentElementId(tab));
        if($(this.destElementId) && tabContentElement){
           Element.hide(tabContentElement);
           Element.removeClassName(tab, 'active');
        }
    }
}