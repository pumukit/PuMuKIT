jQuery(document).ready(function() {
  function in_array(needle, haystack) {
      for (var i = 0, maxi = haystack.length; i < maxi; ++i) {
        if (haystack[i] == needle) {
          return true;
        }
      }
      return false;
    }

    var cargando = $('#announces_loading');
    var announces_div = $('#announces');
    var loaded_months = [];
    var month_loaded = true;
    var AnnounceDate = function(month, year) {
        this.year = year;
        this.month = month;
    };
    AnnounceDate.prototype.initialize = function(month, year) {
        this.year = year;
        this.month = month;
    };
    AnnounceDate.prototype.decMonth = function() {
        this.month -= 1;
        if (this.month == -1){
            this.month = 11;
            this.year -= 1;
        }
    };
    AnnounceDate.prototype.toStringParameter = function() {
        return this.month + "/" + ( this.year )
    };
    var now = new Date();
    var anDate = new AnnounceDate( now.getMonth() + 1, now.getFullYear()  );

    function reloadMoreData()
    {
        if( month_loaded && !in_array(anDate,loaded_months))
        {

            month_loaded = false;
            cargando.show();
            $.get( url_latestuploads_pager, {date: anDate.toStringParameter()}).success(function(data, textStatus, response){
                cargando.hide();
                if( response.getResponseHeader( 'X-Date' ) != "---" )
                {
                    date_month = response.getResponseHeader('X-Date-Month');
                    date_year = response.getResponseHeader('X-Date-Year');
                    announces_div.append(data);
                    anDate.initialize( date_month, date_year );
                    anDate.decMonth();
                    month_loaded = true;
                    if( $('.main-content').height() <= $(window).height() ) {
                        reloadMoreData();
                    }
                }
            });
        }
    }
    reloadMoreData();
    $(window).scroll(function () {
        if ($(window).scrollTop() + window.innerHeight + $('footer').height() >= $(document).height()) {
            reloadMoreData();
        }
    });
});
