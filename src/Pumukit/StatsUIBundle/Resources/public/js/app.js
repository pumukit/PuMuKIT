'use strict';
(function(){

angular.module('app', ['ngRoute', 'nvd3', 'ngBootstrap', 'anguFixedHeaderTable']);
angular.module('app').config(function($routeProvider, $locationProvider) {
  $locationProvider.html5Mode({
      enabled: true,
      requireBase: false
  });
  var route = { 
      templateUrl: window.pmk.template + 'pmk_dashboard.html',
      controller: 'PMKController',
      controllerAs: 'pmkCtrl'
  };
  $routeProvider
    .when('/', route)
    .when('/admin/stats/objects', route)
    .when('/admin/stats/objects/:id', route)
    .when('/admin/stats/series', route)
    .when('/admin/stats/series/:id', route)
    .when('/app_dev.php/admin/stats/objects', route)
    .when('/app_dev.php/admin/stats/objects/:id', route)
    .when('/app_dev.php/admin/stats/series', route)
    .when('/app_dev.php/admin/stats/series/:id', route)
    .otherwise({ redirectTo: '/'});
});
angular.module('app').filter('asDate', function(){
    return function(input){
        return new Date(input);
    }
});
angular.module('app').filter('asDuration', function(){
 return function(ms){

            var duration = "";

            var s = Math.floor(ms/1000);
            var m = Math.floor(s/60);
            var s = s % 60;
            var h = Math.floor(m/60);
            var m = m % 60;

            if (h !=0 && !(isNaN(h))){
                duration += h + "h ";
            }
            if (m !=0 && !(isNaN(m))){
                duration += m + "min ";
            }
            if (s !=0 && !(isNaN(s))){
                duration += s + "s ";
            }

            return duration;
     }
});

})();
