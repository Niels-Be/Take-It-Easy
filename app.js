var app = angular.module('TakeItEasy', ['ngRoute', 'ui.bootstrap']);
app.config(['$compileProvider', function ($compileProvider) {
    $compileProvider.debugInfoEnabled(true);
  }]);
app.config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
    $routeProvider.
      when('/Home', {
	  templateUrl: 'gamelist.html',
	  controller: 'GameListController'
      }).
      when('/Login', {
	  templateUrl: 'login.html',
	  controller: 'LoginController'
      }).
      when('/Lobby', {
	  templateUrl: 'lobby.html',
	  controller: 'LobbyController'
      }).
      when('/Game', {
	  templateUrl: 'game.html',
	  controller: 'GameController'
      }).
      when('/ScoreBoard', {
	  templateUrl: 'scoreboard.html',
	  controller: 'ScoreBoardController'
      }).
      otherwise({
	  redirectTo: function(p1, p2, p3) {
	      console.log(p1);
	      console.log(p2);
	      console.log(p3);
	      return '/Home';
	  }
      });
    
   //$locationProvider.html5Mode(true);
  }]);
/*app.run(function($rootScope, $templateCache) {
    $rootScope.$on('$viewContentLoaded', function() {
       $templateCache.removeAll();
    });
 });*/


app.controller('GlobalController', [ '$scope', '$http', '$location', function($scope, $http, $location) {
    $scope.Me = {};
    $scope.Me.Name = "Guest";
    $scope.Me.Id = -1;
    $scope.Me.State = "None";
    $scope.IsLoggedIn = false;

    $scope.LoadPlayer = function() {
	$http.get('api/app.php?q=/Player').success(function(data) {
		if(data != "ERROR") {
		    $scope.Me.Name = data.Name;
		    $scope.Me.Id = data.Id;
		    $scope.Me.State = data.State;
		    if($scope.Me.State == "List")
			$location.path("/Home");
		    else if($scope.Me.State == "Lobby")
			$location.path("/Lobby");
		    else if($scope.Me.State == "Ingame")
			$location.path("/Game");
		    $scope.IsLoggedIn = true;
		    console.log($scope.Me);
		}
		else {
		    $scope.IsLoggedIn = false;
		    $scope.Me.Id = 0;
		    $location.path("/Login");
		}
	    });
    };
    if($scope.Me.Id == -1)
	$scope.LoadPlayer();
  
    $scope.logout = function() {
	$http.post('api/app.php?q=/Player/Logout').success(function(data) {
	    console.log("PlayerLogout", data);
	    $scope.IsLoggedIn = false;	    
	    $location.path("/Login");
	    $scope.LoadPlayer();
	});
	
    };
} ]);

app.controller('GameListController', [ '$scope', '$http', '$location', function($scope, $http, $location) {
    $scope.Games = [];
    
    $scope.LoadGameList = function() {
        $http.get('api/app.php?q=/Game/List').success(function(data) {
        	console.log("GameList", data);
        	$scope.Games = data;
        });
    };
    $scope.LoadGameList();
    
    $scope.join = function(id) {
	$http.post('api/app.php?q=/Game/Join', {Id: id}).success(function(data) {
		console.log("GameJoin", data);
		if(data=='"FAILED"')
		    alert("Game already running");
		else if(data.State == "Lobby") {
		    $location.path("/Lobby");
		}
	    });
    };
    
    $scope.create = function(gameName) {
	$http.post('api/app.php?q=/Game/Create', {name: gameName}).success(function(data) {
		console.log("GameCreate", data);
		if(data.State == "Lobby") {
		    $location.path("/Lobby");
		}
	    });
    };
} ]);

app.controller('LoginController', [ '$scope', '$http', '$location', function($scope, $http, $location) {
    
    if($scope.IsLoggedIn) {
	$location.path("/Home");
    }
    
    $scope.login = function(user,pw) {
	$http.post('api/app.php?q=/Player/Login', {name: user, password: pw}).success(function(data) {
	    console.log("PlayerLogin", data);
	    if(data=="OK") {
		$location.path("/Home");
		$scope.LoadPlayer();
	    }
	    else {
		$scope.error="Login Failed";
		$scope.IsLoggedIn = false;
	    }
	});
	
    };
    
    $scope.register = function(user,pw) {
	if(user=="" || pw=="")
	    return;
	$http.post('api/app.php?q=/Player/Register', {name: user, password: pw}).success(function(data) {
	    console.log("PlayerRegister", data);
	    if(data=="OK") {
		$location.path("/Home");
		$scope.LoadPlayer();
	    }
	    else {
		$scope.IsLoggedIn = false;
		$scope.error="Register Failed";
	    }
	});
    };
    
} ]);

app.controller('LobbyController', [ '$scope', '$http', '$location', '$interval', function($scope, $http, $location, $interval) {
    $scope.Player = [];
    
    $scope.LoadLobby = function() {
	$http.get('api/app.php?q=/Player/Update').success(function(data) {
	    console.log("LobbyPlayerUpdate", data);
	    if(data.State == "List") {
		$interval.cancel(update);
		$location.path("/Home");
	    } else if(data.State == "Lobby") {
		$scope.Player = data.Player;
		$scope.Host = data.Host;
		$scope.GameName = data.GameName;
	    } else if(data.State == "Ingame") {
		$interval.cancel(update);
		$location.path("/Game");
	    }
	});
    };
    var update = $interval($scope.LoadLobby, 1000);
    
    $scope.start = function() {
	$http.post('api/app.php?q=/Game/Start').success(function(data) {
	    console.log("GameStart", data);
	    if(data == '"OK"') {
		$interval.cancel(update);
		$location.path("/Game");
	    }
	});
    };
    
    $scope.leave = function() {
	$http.post('api/app.php?q=/Game/Leave').success(function(data) {
	    console.log("GameLeave", data);
	    $location.path("/Home");
	});
    };
    
} ]);