'use strict';
(function(){

//authenticationService, socketService, stateService, $http, colors, notify, $q, $filter,$routeParams,$rootScope,$location,$route) {
angular.module('app').controller("PMKController", function ($http, $q, $filter, $routeParams, $rootScope, $location, $route) {
    var pmk = this;

    var init = true;
    //TODO init with $rootScope.min_date
    pmk.min_date = '1970-01-01';
    pmk.set_url_parameters = set_url_parameters;
    pmk.go_to = go_to;
    pmk.go_to_page = go_to_page;
    pmk.range = range;
    pmk.toggle_daterange = toggle_daterange;
    pmk.check_all_history = check_all_history;
    pmk.clear_filter = clear_filter;


    pmk.current_span = 'month';
    pmk.span_format = {
        'year': {
            'moment':{
                'get':'YYYY',
                'show_short':'YYYY',
                'show_long': 'YYYY',
            },
            'filter':'yyyy',
        },
        'month': {
            'moment':{
                'get': 'YYYY-MM',
                'show_short': 'MMM YYYY',
                'show_long': 'MMMM YYYY',
            },
            'filter': 'MMMM yyyy',
        },
        'day': {
            'moment':{
                'get': 'YYYY-MM-DD',
                'show_short': 'DD MMM YYYY',
                'show_long': 'DD MMMM YYYY',
            },
            'filter': 'dd MMM yyyy',
        },
    };

    pmk.loading = {
        'mv': {
            'objects': {
                'general': false,
                'particular': false,
            },
            'series': {
                'general': false,
                'particular': false,
            },
        },
        'his': {
            'objects': {
                'general': false,
                'particular': false,
            },
            'series': {
                'general': false,
                'particular': false,
            },
        },
    };

    // Total views
    pmk.total_views = 0;

    // Pagination
    pmk.total_pages = {
        'mv' : 1,
    };

    pmk.total_items = {
        'mv' : 0,
    };

    pmk.page = {
        'mv' : 1,
    }
    pmk.items_page = {
        'mv' : 10,
    };

    // Navigation
    pmk.view = {
        'tabes': {
            'series': false,
            'objects': true,
        },
        'scope': 'general',  //general or particular
    };
    pmk.view_stack = [];
    pmk.current = {};

    // Filters
    pmk.filter = {
        'title': '',
    }

    pmk.slider_mv = {
        'min' : 1,
        'max' : 10,
    };

    pmk.mmobj_mv = true;

    //FIXME: Change name to datepicker
    pmk.datepicker_mv = {
        'model' : {
            'startDate': moment().subtract(30, 'days'),
            'endDate':   moment()
        },
        'locale': {
            'format': 'DD MMM YYYY',
        },
        'ranges': {
            'Today' : [moment(), moment()],
            'Yesterday': [moment().subtract(1,'days'), moment()],
            'Last 7 days': [moment().subtract(7, 'days'), moment()],
            'Last 30 days': [moment().subtract(30, 'days'), moment()],
            'Last 365 days': [moment().subtract(1, 'year'), moment()],
            'This month': [moment().startOf('month'), moment()],
            'This year': [moment().startOf('year'), moment()],
            'All history': [moment(pmk.min_date),moment()],
        },
        'model_debug': {
            'from_date':'',
            'to_date':'',
        },
        'min_date': pmk.min_date,
        'max_date': moment().format('YYYY-MM-DD'),
        'text': 'All history',
    }

    pmk.most_viewed = {
        'options' : {
            chart : {
                //noData: "Loading data...",
                type : 'multiBarHorizontalChart',
                showControls: false,
                showLegend: false,
                height: 400,
                x: function(d){
                    var id_label = [d.id,d.label];
                    var tab = pmk.view.tabes.series ? 'series':'objects';
                    var scope = pmk.view.scope == 'general' ? 'general':'particular';
                    id_label.push((pmk.page.mv-1)*10 + pmk.mv[tab][scope].data[0].values.indexOf(d) +1);
                    return id_label;
                },
                y: function(d){return d.value;},
                tooltip: {
                    gravity: 'e',
                    //valueFormatter: function(d){return d;}, // Returns the field size if it is a parent
                    //keyFormatter: function(d){return d;}, // Returns the field name of the node
                    contentGenerator: generateTooltip,
                },
                xAxis: {
                    showMaxMin: false,
                    tickPadding: 20,
                    tickFormat: function(d){
                        var label = d[2] + '. ' + d[1];
                        if (label.length>12){
                           label = (label.slice(0,11) + '...');
                        }
                        return label;
                    }
                },
                yAxis: {
                    axisLabel: 'Views',
                    tickFormat: function(d){
                        return d;
                    }
                },
                margin: {
                    left: 120, //170,
                    right: 50,
                }
            },
        },
        'data' : [],
        'config': {
            'extended': true,
        },
    };

    pmk.series_timeline = {
        'options' : {
            chart : {
                noData: "Loading data...",
                type: 'stackedAreaChart',
                showLegend: true,
                height: 400,
                x: function(d){return d[0];},
                y: function(d){return d[1];},
                // ??
                useInteractiveGuideline: true,
                xAxis: {
                    showMaxMin: false,
                    tickPadding: 10,
                    tickFormat: function(d){
                        if (typeof(d)=='number'){
                            return moment(d).format(pmk.span_format[pmk.current_span].moment.show_short);
                        }
                        return d;
                    }
                },
                yAxis: {
                    axisLabel: 'Views',
                    tickFormat: function(d){
                        return d;
                    }
                },
                zoom: {
                    enabled: true,
                    scaleExtent: [1, 10],
                    useFixedDomain: false,
                    useNiceScale: false,
                    horizontalOff: false,
                    verticalOff: true,
                    unzoomEventType: 'dblclick.zoom'
                }
            },
        },
        'data' : [],
        //{
        //    "key" : "North America" ,
        //    "values" : [[ '1025409600000' , 23.041422681023] , [ '1028088000000' , 19.854291255832]]
        //}
        //    ],
        'config': {
            'extended' : true,
        },
        'new_data': [],
    };

    pmk.historical = {
        'options': {
            chart: {
                type: 'historicalBarChart',
                height: 450,
                margin : {
                    top: 20,
                    right: 20,
                    bottom: 65,
                    left: 50
                },
                x: function(d){ return d[0];},
                y: function(d){ return d[1];},
                showValues: true,
                valueFormat: function(d){
                    return d;
                },
                xAxis: {
                    axisLabel: '',
                    tickFormat: function(d){
                        if (typeof(d)=='number'){
                            //Fix typo #9590
                            var tab = pmk.view.tabes.series ? 'series':'objects';
                            var scope = pmk.view.scope == 'general'? 'general':'particular';
                            if (d < pmk.his[tab][scope].data[0].values[0][0]) {
                                d = pmk.his[tab][scope].data[0].values[0][0];
                            }
                            return moment(d).format(pmk.span_format[pmk.current_span].moment.show_short);
                        }
                        return d;
                    },
                    rotateLabels: 45,
                    showMaxMin: false
                },
                yAxis: {
                    axisLabel: 'Views',
                    //axisLabelDistance: -10,
                    tickFormat: function(d){
                        return d;
                    }
                },
                tooltip: {
                    keyFormatter: function(d) {
                        return moment(d).format(pmk.span_format[pmk.current_span].moment.show_long);
                    }
                },
                zoom: {
                    enabled: false,
                    scaleExtent: [1, 10],
                    useFixedDomain: false,
                    useNiceScale: false,
                    horizontalOff: false,
                    verticalOff: true,
                    unzoomEventType: 'dblclick.zoom'
                },
            },
        },
        'data': [],
        'config': {
            'extended': true,
        }
    };

    activate();

    function activate(){

        // Load template depending on route and route params
        var obj_type = $location.path().search('objects') >=0 ? 'objects':'series';
        var obj_scope = $routeParams.id != undefined ? 'particular':'general';
        pmk.template = window.pmk.template + 'stats_' + obj_type + '_' + obj_scope + '.html';

        //Load URL parameters
        if ($routeParams.from_date != undefined){
            pmk.datepicker_mv.model.startDate = moment($routeParams.from_date, pmk.span_format.day.moment.get);
        }
        if ($routeParams.to_date != undefined){
            pmk.datepicker_mv.model.endDate = moment($routeParams.to_date,pmk.span_format.day.moment.get);
        }
        if ($routeParams.title != undefined){
            pmk.filter.title = $routeParams.title;
        }
        if ($routeParams.span != undefined){
            pmk.current_span = $routeParams.span;
        }
        if ($routeParams.page != undefined){
            pmk.page.mv = parseInt($routeParams.page);
        }

        //FIXME: change view.tabes true/false with the name of the 'tabe'
        pmk.view.tabes.series = obj_type == 'series';
        pmk.view.tabes.objects = obj_type == 'objects';
        pmk.view.scope = obj_scope == 'general' ? 'general':$routeParams.id;

        // Intialize the charts for the different views
        // The model can't be reused

        pmk.mv = {
            'series': {
                'general': angular.copy(pmk.most_viewed),
                'particular': angular.copy(pmk.most_viewed),
            },
            'objects': {
                'general': angular.copy(pmk.most_viewed),
                'particular': angular.copy(pmk.most_viewed),
            },
        };
        pmk.tl = {
            'series': {
                'general': angular.copy(pmk.series_timeline),
                'particular': angular.copy(pmk.series_timeline),
            },
            'objects': {
                'general': angular.copy(pmk.series_timeline),
                'particular': angular.copy(pmk.series_timeline),
            },
        };

        pmk.his = {
            'series': {
                'general': angular.copy(pmk.historical),
                'particular': angular.copy(pmk.historical),
            },
            'objects': {
                'general': angular.copy(pmk.historical),
                'particular': angular.copy(pmk.historical),
            },
        }


        setTimeout(function(){
            if (!(pmk.view.tabes.objects && pmk.view.scope != 'general')){
                get_most_viewed();
            }else if (pmk.view.scope != 'general'){
                load_particular_scope();
            }
            get_historical_data();
        },500);

    }


    // SCOPE FUNCTIONS

    function go_to_page(page){
        if (page > 0 && page < pmk.total_pages.mv +1){
            pmk.page.mv = page;
            set_url_parameters('page');
        }
    }

    function range(num){
        var range;
        // Allways show 5 items
        //
        // If num [0-3] --> 1,1,2,3,...,total
        // If num [total-3,total] --> 1,...,total-3,total-2,total-1,total
        // If num [4,total-4] --> 1,...,num-1,num,num+1,...,total
        var page = pmk.page.mv;
        if (num > 6){
            if (page > 3 && page < num-3){
                range = [1,'...',page-1,page,page+1,'...',num];
            }else if (page <= 3){
                range = [1,2,3,4,'...',num];
            }else if (page >= num-2){
                range = [1,'...',num-3,num-2,num-1,num];
            }
        }else{
            range = Array.apply(null,Array(num)).map(function (x, i) { return i+1; });
        }
        return range;
    }

    function toggle_daterange(event_click){
        $("#form_daterangepicker").click();
    }

    function go_to(tabe, scope_obj){

        var id = scope_obj ? '/'+scope_obj.id : '';
        var params = $routeParams;
        var params_encod = '?'
        angular.forEach(params, function(value,key){
            if (key !== 'page'){
                params_encod = params_encod + key + '=' + value + '&';
            }
        })
        $location.url('/admin/stats/' + tabe + id + params_encod);
    }

    function check_all_history(){
        var min_model = pmk.datepicker_mv.model_debug.from_date;
        var max_model = pmk.datepicker_mv.model_debug.to_date;
        return (min_model == pmk.datepicker_mv.min_date &&
                max_model == pmk.datepicker_mv.max_date);
    }

    function set_url_parameters(origin){

        save_string_dates();
        var tab = pmk.view.tabes.series ? 'series':'objects';
        var scope = pmk.view.scope == 'general'? 'general':'particular';

        if (!(origin=='datepicker' && typeof(pmk.datepicker_mv.model) === 'object')){

            // FIXME: Avoiding infinite loop with init var
            if (!init){
                var updated_params = {};
                if (origin == 'datepicker' || origin == 'all'){
                    updated_params.from_date = pmk.datepicker_mv.model_debug.from_date;
                    updated_params.to_date = pmk.datepicker_mv.model_debug.to_date;
                    updated_params.page = 1;
                }

                if (origin == 'filter' || origin == 'all'){
                    updated_params.title = pmk.filter.title == "" ? null:pmk.filter.title;
                    updated_params.page = 1;
                }

                if (origin == 'timespan'){
                    updated_params.span = pmk.current_span == "month" ? null:pmk.current_span;
                }

                if (origin == 'page'){
                    updated_params.page = pmk.page.mv != 0 ? pmk.page.mv:null;
                }

                reload(updated_params);
            }else{
                init = false;
            }
        }

        function reload(updated_params){
            var params = angular.copy($routeParams);
            angular.forEach(updated_params, function(value,key){
                if (value != null){
                    params[key] = value;
                }else{
                    delete params[key];
                }
            })
            if (angular.equals($routeParams,params)){
                $route.reload();
            }else{
                $location.search(params);
            }
        }

    }

    function clear_filter(filter_type){

        if (filter_type=='datepicker' || filter_type=='all'){
            var from_date = pmk.datepicker_mv.ranges['All history'][0]
                .format(pmk.datepicker_mv.locale.format);
            var to_date = pmk.datepicker_mv.ranges['All history'][1]
                .format(pmk.datepicker_mv.locale.format);
            pmk.datepicker_mv.model = from_date + ' - ' + to_date;
        }
        if (filter_type=='filter' || filter_type=='all'){
            pmk.filter.title = '';
        }
        set_url_parameters(filter_type);
    }

    //INTERNAL FUNCTIONS
    function load_particular_scope(){
            var obj_type = $location.path().search('objects') >=0 ? 'objects':'series';
            var type = obj_type == 'objects' ? 'mmobj':'series';
            $http({
                method: 'GET',
                url: '/api/media/' + type + '.json',
                headers: { 'X-Requested-With' :'XMLHttpRequest'},
                params:{
                    'criteria[id]' : $routeParams.id,
                }
            })
            .then( function (data){
                var type = pmk.view.tabes.series ? 'series':'mmobj';
                var generic_data_type = type.lastIndexOf('s') == type.length-1 ? type : (type + 's');
                var obj = data.data[generic_data_type][data.data.criteria.id];
                pmk.current = {
                    'id': obj.id,
                    'label': obj.title[obj.locale],
                }
                if (type == 'mmobj'){
                    //FIXME: search for the track with the maximum duration
                    pmk.current.duration = obj.tracks[0].duration;
                    pmk.current.description = obj.description;
                    pmk.current.date = obj.record_date;
                    if (obj.pics.length != 0 && obj.pics[0].url) {
                        pmk.current.img = obj.pics[0].url;
                    } else {
                        pmk.current.img = "/bundles/pumukitschema/images/video_none.jpg";
                    }
                    pmk.current.serie = {
                        'label': obj.series.title[obj.series.locale],
                        'id': obj.series.id,
                    }
                }
                //TODO: If type is series add number of objects of the serie.
            });
    }
    function get_most_viewed(origin){
        var tab = pmk.view.tabes.series ? 'series':'objects';
        var scope = pmk.view.scope == 'general' ? 'general':'particular';
        pmk.loading.mv[tab][scope] = true;
        var mv_data = (pmk.view.tabes.series && (pmk.view.scope == 'general')) ? "series":"mmobj";

        save_string_dates();

        if ((!(origin=='datepicker' && typeof(pmk.datepicker_mv.model) === 'object')) && !(pmk.mv[tab][scope].api == undefined)){

                var params = {
                    'limit': pmk.items_page.mv,
                    'from_date' : pmk.datepicker_mv.model_debug.from_date,
                    'to_date' : pmk.datepicker_mv.model_debug.to_date,
                    'page': pmk.page.mv - 1,
                }


            if (pmk.view.scope!='general' && pmk.view.tabes.series){
                params['criteria[series]'] = pmk.view.scope;
            }

            if (pmk.filter.title != ""){
                params['criteria[$text][$search]'] = pmk.filter.title;

            }else if(pmk.view.tabes.objects && pmk.view.scope != 'general'){
                // FIXME: Hardcoded search in english
                params['criteria[id]'] = pmk.view.scope;
            }


            $http({
                method: 'GET',
                url: '/api/media/'+ mv_data +'/most_viewed',
                headers: { 'X-Requested-With' :'XMLHttpRequest'},
                params: params,
            })
            .then(getMVSuccess)
                .catch(getMVError);

        }
    }

    function update_most_viewed(data){

        //var new_data = pmk.most_viewed.data;
        //new_data.values = data;
        var new_data = [{
            'key': '',
            'color': '#ED6D00',
            'values': data,
        }];
        var aux = angular.copy(new_data);
        var tab = pmk.view.tabes.series ? 'series':'objects';
        var scope = pmk.view.scope == 'general'? 'general':'particular';
        pmk.mv[tab][scope].data = aux;
        pmk.mv[tab][scope].api.update()
    }

    function generateTooltip(d){
        return "<table>" +
            "  <tbody>" +
            "    <tr>" +
            "      <td class='legend-color-guide'>" +
            "        <div style='background-color: " + d.color + " '>" +
            "        </div>" +
            "      </td>" +
            "      <td class='key'>" + d.data.label +
            "      </td>" +
            "      <td class='value'>" + d.data.value +
            "      </td>"
            "    </tr>" +
            "  </tbody>"+
            "</table>";

    }



    function getMVSuccess(data){
        if (data.data.total){
            pmk.total_items.mv = data.data.total;
        }
        pmk.total_pages.mv = Math.ceil(data.data.total/pmk.items_page.mv);
        // Series general -- series
        // Series particular -- mmobj
        // Objetos general -- mmobj
        // Objetos particular -- mmobj?
        var mv_data = pmk.view.tabes.series && pmk.view.scope == 'general' ? "series":"mmobj";
        var generic_data_type = mv_data.lastIndexOf('s') == mv_data.length-1 ? mv_data : (mv_data + 's');
        var most_viewed = [];
        var items = data.data[generic_data_type]
            for (var item_indx in items){
                data = {
                    'label': items[item_indx][mv_data].title[items[item_indx][mv_data].locale],
                    'value': items[item_indx].num_viewed,
                    'id': items[item_indx][mv_data].id,
                }
                if (mv_data == 'mmobj'){
                    var locale = items[item_indx][mv_data].series.locale;
                    data.serie = {
                        'label': items[item_indx][mv_data].series.title[locale],
                        'id': items[item_indx][mv_data].series.id,
                    }
                    data.duration = items[item_indx][mv_data].duration;
                    data.description = items[item_indx][mv_data].description[items[item_indx][mv_data].locale];
                    data.date = items[item_indx][mv_data].record_date;
                    if (items[item_indx][mv_data].pics.length != 0 && items[item_indx][mv_data].pics[0].url) {
                        data.img = items[item_indx][mv_data].pics[0].url;
                    } else {
                        data.img = "/bundles/pumukitschema/images/video_none.jpg";
                    }
                }else{
                    $http({
                        method: 'GET',
                        url: '/api/media/mmobj.json',
                        headers: { 'X-Requested-With' :'XMLHttpRequest'},
                        params: {
                            'criteria[series]': data.id,
                        },
                    })
                    .then(addSerieItems);
                }
                most_viewed.push(data);
            }
        update_most_viewed(most_viewed);
        //get_series_timeline();
        //get_historical_data();

        function addSerieItems(data){
            var serie_id = data.data.criteria.series;
            var tab = pmk.view.tabes.series ? 'series':'objects';
            var scope = pmk.view.scope == 'general'? 'general':'particular';
            var data_elem = jQuery.grep(pmk.mv[tab][scope].data[0].values, function(e){return e.id == serie_id});
            data_elem[0].items = Object.keys(data.data.mmobjs).length
        }

        var tab = pmk.view.tabes.series ? 'series':'objects';
        var scope = pmk.view.scope == 'general' ? 'general':'particular';
        pmk.loading.mv[tab][scope] = false;
    }

    function getMVError(){
        console.error({
            message: "There was an error getting most viewed changes",
            templateUrl: "/static/angular/angular-notify.html",
            classes: "danger alert-danger",
        });
    }

    function get_series_timeline(){
        //FIXME: hardcoded
        //var mock = ['568a8f7c8fe96702028b457f','568a8f858fe96702028b45fc','568a8f7f8fe96702028b45b3'];
        var ids = [];
        var tab = pmk.view.tabes.series ? 'series':'objects';
        var scope = pmk.view.scope == 'general'? 'general':'particular';
        var most_viewed = pmk.mv[tab][scope].data[0].values;
        for (var value_indx in most_viewed){
            ids.push(most_viewed[value_indx].id);
        }
        pmk.tl[tab][scope].new_data = [];
        var promises = []
            for (var serie_id_indx in ids){
                promises.push(createPromise(ids[serie_id_indx]));
            }
        $q.all(promises).then(getTLSuccess);
    }

    function createPromise(id){
        // Series general --> series
        // Series particular --> mmobj
        // Objects general --> mmobj
        // Objects particular --> mmobj
        var tl_data = pmk.view.tabes.series && pmk.view.scope == 'general' ? 'series':'mmobj';
        var params = {
            'from_date': pmk.datepicker_mv.model_debug.from_date,
            'to_date': pmk.datepicker_mv.model_debug.to_date,
        }
        if (tl_data == 'series'){
            params.series = id;
        }else{
            params.mmobj = id;
        }

        return(
                $http({
                    method: 'GET',
                    url: '/api/media/views/' + tl_data,
                    headers: { 'X-Requested-With' :'XMLHttpRequest'},
                    params: params,
                })
                .then(addDataElem)
              );
    }

    function addDataElem(data){
        var views = data.data.views;
        var tab = pmk.view.tabes.series ? 'series':'objects';
        var scope = pmk.view.scope == 'general'? 'general':'particular';
        var mv_data = pmk.view.tabes.series && pmk.view.scope == 'general' ? 'series':'mmobj';
        var orig = $filter('filter')(pmk.mv[tab][scope].data[0].values, {'id': data.data[mv_data + '_id']}, true);
        var mmobj = {
            //FIXME: series_id, later be mmobj_id
            'key' : orig[0].label,
            'values' : [],
        };
        for(var span_indx in views){
            //FIXME: hardcoded
            mmobj.values.push([
                    moment(views[span_indx]['_id'],pmk.span_format[pmk.current_span].moment.get).valueOf(),
                    //views[span_indx]['_id'],
                    views[span_indx].numView
                    ]);
        }
        pmk.tl[tab][scope].new_data.push(mmobj);
    }

    function getTLSuccess(){
        var tab = pmk.view.tabes.series ? 'series':'objects';
        var scope = pmk.view.scope == 'general'? 'general':'particular';
        pmk.tl[tab][scope].data = angular.copy(pmk.tl[tab][scope].new_data)
        pmk.tl[tab][scope].api.update();
        //notify({
        //    message: "Timeline series data successfully updated",
        //    templateUrl: "/static/angular/angular-notify.html",
        //    classes: "danger alert-success",
        //});
    }

    function getTLError(){
        console.error({
            message: "There was an error getting the series timeline",
            templateUrl: "/static/angular/angular-notify.html",
            classes: "danger alert-danger",
        });
    }


    function get_historical_data(origin){

        // The array could be of length 1 depending on the view
        var ids = [];
        var tab = pmk.view.tabes.series ? 'series':'objects';
        var scope = pmk.view.scope == 'general'? 'general':'particular';

        pmk.loading.his[tab][scope] = true;

        if ((!(origin=='datepicker' && typeof(pmk.datepicker_mv.model) === 'object')) && !(pmk.his[tab][scope].api == undefined)){

            if (pmk.his[tab][scope].api != undefined){
                if (scope == 'general'){
                    //var most_viewed = pmk.mv[tab][scope].data[0].values;
                    //for (var value_indx in most_viewed){
                    //    ids.push(most_viewed[value_indx].id);
                    //}
                    // FIXME
                    ids = [0];
                }else{
                    ids.push($routeParams.id);
                }

                pmk.his[tab][scope].new_data = [];
                var promises = []
                    for (var serie_id_indx in ids){
                        promises.push(createPromise_his(ids[serie_id_indx]));
                    }
                $q.all(promises).then(getHisSuccess);

            }
        }
    }

    function createPromise_his(id){

        var his_data = "";
        if (pmk.view.scope != 'general'){
            his_data = pmk.view.tabes.series ? 'series':'mmobj';
        }
        var params = {
            'from_date': pmk.datepicker_mv.model_debug.from_date,
            'to_date': pmk.datepicker_mv.model_debug.to_date,
            'group_by': pmk.current_span,
        }

        if (pmk.filter.title != ""){
            params['criteria[$text][$search]'] = pmk.filter.title;
        }

        if (parseInt(id)>0){
            params[his_data] = id;
        }
        if (his_data != ""){
            his_data = '/' + his_data;
        }
        return(
                $http({
                    method: 'GET',
                    url: '/api/media/views' + his_data,
                    headers: { 'X-Requested-With' :'XMLHttpRequest'},
                    params: params,
                })
                .then(addDataElem_his)
              );
    }

    function addDataElem_his(data){

        var views = data.data.views;
        var tab = pmk.view.tabes.series ? 'series':'objects';
        var scope = pmk.view.scope == 'general'? 'general':'particular';

        var data_elem = [];
        for(var span_indx=0; span_indx<views.length; span_indx++){
            data_elem.push([
                    moment(views[span_indx]['_id'],pmk.span_format[pmk.current_span].moment.get).valueOf(),
                    //views[span_indx]['_id'],
                    views[span_indx].numView
                    ]);
        }
        data_elem.sort( function (a,b){ return a[0]-b[0]});

        // Aggregate data
        if (pmk.his[tab][scope].new_data.length == 0){
            // TODO: better add 0 values when there are no views
            // first should be an array with 0 values as views
            pmk.his[tab][scope].new_data = angular.copy(data_elem);
        }else{
            var new_data = pmk.his[tab][scope].new_data;
            var count = 0;

            for (var bar_indx=0; bar_indx<new_data.length; bar_indx++){
                for (var i=count; i<data_elem.length; i++){
                    if (new_data[bar_indx][0] < data_elem[count][0]){
                        break;
                    }
                    if (new_data[bar_indx][0] == data_elem[count][0]){
                        new_data[bar_indx][1] = parseInt(new_data[bar_indx][1]) + parseInt(data_elem[count][1]);
                        count = count + 1;
                        break;
                    }
                    if (new_data[bar_indx][0] > data_elem[count][0]){
                        new_data.splice(bar_indx,0,data_elem[count]);
                        count = count + 1;
                    }
                }
            }
        }

    }

    function getHisSuccess(){
        var tab = pmk.view.tabes.series ? 'series':'objects';
        var scope = pmk.view.scope == 'general'? 'general':'particular';

        var data = {
            'key' : '',
            'bar' : true,
            'values' : pmk.his[tab][scope].new_data,
        }
        pmk.his[tab][scope].data = [angular.copy(data)];
        pmk.his[tab][scope].api.update();
        pmk.loading.his[tab][scope] = false;

        var aux_total_views = 0;
        angular.forEach(data.values, function(val){
            aux_total_views += val[1];
        })
        pmk.total_views = aux_total_views;

    }


    function save_string_dates(){
        //FIXME: Library bug. ng-change called twice. Second time not working
        if (typeof(pmk.datepicker_mv.model) === 'string'){
            console.log(pmk.datepicker_mv.model);
            pmk.datepicker_mv.model_debug.from_date = moment(pmk.datepicker_mv.model.split("-")[0], pmk.datepicker_mv.locale.format).format('YYYY-MM-DD');
            pmk.datepicker_mv.model_debug.to_date = moment(pmk.datepicker_mv.model.split("-")[1], pmk.datepicker_mv.locale.format).format('YYYY-MM-DD');
            console.log(pmk.datepicker_mv.model_debug);
        }
    }
});




})();
