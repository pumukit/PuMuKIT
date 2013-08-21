

angular.module('cpuApp', ['ngResource']).
    config(['$interpolateProvider', function ($interpolateProvider) {
        $interpolateProvider.startSymbol('[[');
        $interpolateProvider.endSymbol(']]'); 
    }]). 
    config(function($routeProvider) {
        $routeProvider.
          when('/', {controller: ListCtrl, templateUrl:'list.html'}).
          when('/edit/:cpuId', {controller:EditCtrl, templateUrl:'detail.html'}).
          when('/new', {controller:CreateCtrl, templateUrl:'detail.html'}).
            otherwise({redirectTo:'/error'});
    });
 
function ListCtrl($scope, $http) {
    $http.get('/app_dev.php/admin/transco/api/cpus.json').success(function(data) {
        $scope.cpus = data;
        $scope.orderProp = 'name';
    });
}

function EditCtrl($scope, $http, $resource, $routeParams) {
    $http.get('/app_dev.php/admin/transco/api/cpus/' + $routeParams.cpuId +  '.json').success(function(data) {
        $scope.cpus = data;
        $scope.orderProp = 'name';
    });
    
    var saveResource = $resource('/app_dev.php/admin/transco/api/cpus/' + $routeParams.cpuId +  '.json', {}, 
                                    {update:{method:'PUT'}});
    $scope.save = function() {

        saveResource.update($scope.cpus);
    };
}

function CreateCtrl($scope, $http) {
    $http.get('/app_dev.php/admin/transco/api/cpus.json').success(function(data) {
        $scope.cpus = data;
        $scope.orderProp = 'name';
    });
}

