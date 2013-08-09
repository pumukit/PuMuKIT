var app = angular.module('app', ['ngResource']).
config(['$interpolateProvider', function ($interpolateProvider) {
    $interpolateProvider.startSymbol('[[');
    $interpolateProvider.endSymbol(']]');
}]);
       
 
app.factory('cpu', function($resource){
    return $resource('/app_dev.php/admin/transco/api/cpus:cpuId.json', {}, {
        query: { method: 'GET', params: {productId: 'all'}, isArray: true},
        get: { method: 'GET'},
        save: { method : 'PUT', params: {entryId: '@entryId'}},
        create: { method : 'POST'},
        destroy: { method : 'DELETE'}
   });
});

        
app.directive('focus', function() {
  return {
    link: function(scope, element, attrs) {
      element[0].focus();
    }
  };
});

app.controller('MainCtrl', function($scope, cpu) {
    console.log(cpu.get());
    /*
    cpu = app.Cpu;
    cpu.get({entryId: $scope.entryId}, function() {
            $location.path('/');
    });
   
   
    $scope.showAllCpus = function() {
        var cpu = new Cpu();
        $scope.cpu.ip = cpu.get();
    };*/
}); 
