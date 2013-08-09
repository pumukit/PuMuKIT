
'use strict';

/* App Module */

angular.module('cpuApp',[]).
    config(['$interpolateProvider', function ($interpolateProvider) {
        $interpolateProvider.startSymbol('[[');
        $interpolateProvider.endSymbol(']]'); 
    }]).
    config(function($routeProvider) {
        $routeProvider.
            when('/', {controller:ListCtrl, templateUrl:'list.html'}).
            when('/edit/:cpuId', {controller:EditCtrl, templateUrl:'detail.html'}).
            when('/new', {controller:CreateCtrl, templateUrl:'detail.html'}).
            otherwise({redirectTo:'/'});
    });