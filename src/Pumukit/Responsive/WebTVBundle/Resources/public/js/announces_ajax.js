jQuery(document).ready(function() {  
    var cargando = $('cargando');
    var announces_div = $('announces');
    var loaded_months = [];
    var month_loaded = true;
    var AnnounceDate = Class.create();
    AnnounceDate.prototype = {
        // NOTE: JS months are [0, 11]
        initialize: function(month, year) {
            this.year = year;
            this.month = month;
        },
        decMonth: function() {
            this.month -= 1;
            if (this.month == -1){
                this.month = 11;
                this.year -= 1;
            }
        },
        toStringParameter: function() {
            return this.month + "/" + ( this.year )
        }
    };
    var now = new Date();
    var anDate = new AnnounceDate( now.getMonth() + 1, now.getFullYear()  );

    function reloadMoreData()
    {
        if( month_loaded ) 
        {
            month_loaded = false;
            cargando.show();
            new Ajax.Request( url_latestuploads_pager , 
                             {
                                 method: 'get', 
                                 parameters: {date: anDate.toStringParameter()},
                                 onSuccess: function(response){
                                     cargando.hide();
                                     if( response.getResponseHeader( 'X-Date' ) != "---" ) 
                                     {
                                         date_month = response.getResponseHeader('X-Date-Month');
                                         date_year = response.getResponseHeader('X-Date-Year');
                                         announces_div.innerHTML = announces_div.innerHTML + response.responseText;
                                         anDate.initialize( date_month, date_year );
                                         anDate.decMonth();
                                         month_loaded = true;
                                         if(  document.viewport.getHeight() >= ( jQuery('footer').offset().top ) )
                                         {                                        
                                             reloadMoreData();
                                         }
                                     }
                                 }
                             }
                            );
        }
    }
    reloadMoreData();
    Event.observe(window, 'scroll', function(){
        var topOffset = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop;
        var scrollBottom = document.viewport.getHeight() + topOffset > jQuery('footer').offset().top;
        if ( scrollBottom  ){
            reloadMoreData();
        }
    });
});
