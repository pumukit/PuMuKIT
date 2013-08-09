
'use strict';

/* Controllers */

function ListCtrl($scope, $http) {
    $http.get('/app_dev.php/admin/transco/api/cpus.json').success(function(data) {
        $scope.cpus = data;
        $scope.orderProp = 'name';
    });

}
