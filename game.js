
function polygon(ctx, x, y, radius, sides, startAngle, anticlockwise) {
  if (sides < 3) return;
  var a = (Math.PI * 2)/sides;
  a = anticlockwise?-a:a;
  ctx.save();
  ctx.translate(x,y);
  ctx.rotate(startAngle);
  ctx.beginPath();
  ctx.moveTo(radius,0);
  for (var i = 1; i < sides; i++) {
    ctx.lineTo(radius*Math.cos(a*i),radius*Math.sin(a*i));
  }
  ctx.closePath();
  ctx.restore();
}

function getMousePos(canvas, evt) {
    var rect = canvas.getBoundingClientRect();
    return {
      x: evt.clientX - rect.left,
      y: evt.clientY - rect.top
    };
  }

function Piece(x, y) {
    this.Size = 50;
    this.Colors = ["#ccc", "gray", "#ff66cc", "purple", "#66ccff", "#3079ed", "red", "green", "orange", "yellow"];
    
    this.Pos = {X: 0, Y: 0};
    
    this.X = x;
    this.Y = y;
    
    this.Id = -1;
    this.Number = [0,0,0]; // [ Up | , Left / , Right \ ]
    
    this.Highlight = false;
    this.Marked = false;
    
    this.setPiece = function(piece) {
	if(piece) {
	    this.Id = piece.Id;
	    this.Number = piece.Number;
	} else {
	    this.Id = -1;
	    this.Number = [0,0,0];
	}
    }
    
    this.draw = function(context) {
	var a = (Math.PI * 2)/6;
	
	var x = this.X * (this.Size*1.5);
	var y = this.Y * this.Size*Math.sin(a) + this.Size;
	if(this.Y % 2 == 0) {
	    x = this.X * (this.Size*1.5);
	}
	x+=this.Size;
	
	var size = this.Size;
	var d = 0.866025404 * size - 1;
	
	//Bg
	context.lineWidth = 2;
	context.fillStyle = this.Marked ? '#fff' : '#ccc';
	context.strokeStyle = this.Highlight ? 'red' : '#555';
	polygon(context, x, y, size, 6);
	context.fill();
	context.stroke();
	
	context.lineWidth = 10;
	
	//Left \ 
	context.strokeStyle = this.Colors[this.Number[1]];
	context.beginPath();
	context.moveTo(x + d*Math.cos(a/2), y - d*Math.sin(a/2));
	context.lineTo(x - d*Math.cos(a/2), y + d*Math.sin(a/2));
	context.stroke();
	
	
	//Right /
	context.strokeStyle = this.Colors[this.Number[2]];
	context.beginPath();
	context.moveTo(x - d*Math.cos(a/2), y - d*Math.sin(a/2));
	context.lineTo(x + d*Math.cos(a/2), y + d*Math.sin(a/2));
	context.stroke();
	
	//Up | 
	context.strokeStyle = this.Colors[this.Number[0]];
	context.beginPath();
	context.moveTo(x, y - size*Math.sin(a) + 1);
	context.lineTo(x, y + size*Math.sin(a) - 1);
	context.stroke();
	
	if(this.Id != -1 || this.Marked) {
		//Draw Numbers
	
		context.font = "30px Arial";
		context.lineWidth = 0.3;
		context.strokeStyle = '#000';

		context.fillStyle = this.Colors[this.Number[0]];
		context.fillText(this.Number[0], x - 1, 		   y - size*Math.sin(a)   + 25);
		context.fillStyle = this.Colors[this.Number[1]];
		context.fillText(this.Number[1], x - d*Math.cos(a/2) - 1,  y + d*Math.sin(a/2)    - 3);
		context.fillStyle = this.Colors[this.Number[2]];
		context.fillText(this.Number[2], x + d*Math.cos(a/2) - 16, y + size*Math.sin(a/2) - 8);
		context.strokeText(this.Number[0], x - 1, 		   y - size*Math.sin(a)   + 25);
		context.strokeText(this.Number[1], x - d*Math.cos(a/2) - 1,  y + d*Math.sin(a/2)    - 3);
		context.strokeText(this.Number[2], x + d*Math.cos(a/2) - 16, y + size*Math.sin(a/2) - 8);
	}
    };
    
    this.isInside = function(cx, cy) {
	var a = (Math.PI * 2)/6;
	
	var x = this.X * (this.Size*1.5);
	var y = this.Y * this.Size*Math.sin(a) + this.Size;
	if(this.Y % 2 == 0) {
	    x = this.X * (this.Size*1.5);
	}
	x+=this.Size;
	
	var size = this.Size;
	
	return ((x - cx)*(x - cx) + (y-cy)*(y-cy) < size*size);   
    };
}


app.controller('GameController', [ '$scope', '$http', '$location', '$interval', function($scope, $http, $location, $interval) {
    
    $scope.Piece = null;
    $scope.Round = 0;
    $scope.Points = 0;
    $scope.GameName = "";
    
    $scope.placeActive = false;
    
    $scope.Update = function() {
	$http.get('api/app.php?q=/Player/Update').success(function(data) {
	    console.log("PlayerUpdate", data);
	    if(data.State == "List") {
		$interval.cancel(update);
		$location.path("/Home");
	    } else if(data.State == "Lobby") {
		$interval.cancel(update);
		$location.path("/Lobby");
	    } else if(data.State == "Ingame") {
		$scope.Piece = data.Piece;
		$scope.Player = data.Player;
		$scope.GameName = data.GameName;
		
		if(data.Ready == "1")
		    $scope.placeActive = false;
		else if(piece.Id != data.Piece.Id) {
		    piece.Number = data.Piece.Number;
		    piece.Id = -2;
		    piece.draw(conCur);
		}
		
		if(data.StartNextRound) {
		    $scope.placeActive = true;
		    if(marked) {
			marked.Number = piece.Number;
			marked.draw(conField);
		    }
		}
		
		$scope.Round = data.Round;
		$scope.Points = data.Points;
		
	    } else if(data.State == "Results") {
		$interval.cancel(update);
	    }
	});
    };
    var update = $interval($scope.Update, 1000);
    
    $scope.leave = function() {
	$http.post('api/app.php?q=/Game/Leave').success(function(data) {
	    console.log("GameLeave", data);
	    $location.path("/Home");
	});
    };
    
    $scope.placePiece = function(px, py) {
	$scope.placeActive = false;
	$http.post('api/app.php?q=/Player/Place', {x: px, y: py}).success(function(data) {
	    console.log("PlacePiece", data);
	    if(data=='"OK"') {
		oldNumber = $scope.Piece.Number;
		$scope.in.x = null;
		$scope.in.y = null;
		
		field[px][py].setPiece($scope.Piece);
		field[px][py].draw(conField);
		//$scope.Pice = null;
		piece.Id = -1;
		piece.Number = [0,0,0];
		piece.draw(conCur);
	    } else {
		alert(data);
		
		if(data!='"Placed"')
		    $scope.placeActive = true;
		else {
		    $scope.in.x = null;
		    $scope.in.y = null;
		}
	    }
	});
    }
    
    var piece = new Piece(0,0);
    piece.Id = -1;
    var canCur = document.getElementById('currentPiece');
    var conCur = canCur.getContext('2d');
    canCur.width=100;
    canCur.height=100;
    piece.draw(conCur);
    
    var field = [ [ new Piece(0,6), new Piece(1,7), new Piece(2,8) ],
                  [ new Piece(0,4), new Piece(1,5), new Piece(2,6), new Piece(3,7) ],
                  [ new Piece(0,2), new Piece(1,3), new Piece(2,4), new Piece(3,5), new Piece(4,6) ],
                  [ new Piece(1,1), new Piece(2,2), new Piece(3,3), new Piece(4,4) ],
                  [ new Piece(2,0), new Piece(3,1), new Piece(4,2) ]
                ];
    
    var canField = document.getElementById('gameField');
    var conField = canField.getContext('2d');
    canField.width=10*50;
    canField.height=9*50;
    for(x in field)
	for(y in field[x]) {
	    field[x][y].Pos.X = x;
	    field[x][y].Pos.Y = y;
	    field[x][y].draw(conField);
	}
    
    $http.get('api/app.php?q=/Game/Field').success(function(data) {
	console.log("GameField", data);
	for(x in field)
	    for(y in field[x]) {
		field[x][y].setPiece(data[x][y]);
    		field[x][y].draw(conField);
    	    }
	$scope.placeActive = true;
    });
    
    var highlighted = null;
    var marked = null;
    var oldNumber = [0,0,0];
    canField.addEventListener('mousemove', function(evt) {
        var mp = getMousePos(canField, evt);
        if(highlighted) {
            if(highlighted.isInside(mp.x, mp.y))
                return;
            else {
        	highlighted.Highlight = false;
        	highlighted.draw(conField);
                highlighted = null;
            }
        }
        for(x in field)
	    for(y in field[x])
		if(field[x][y].isInside(mp.x, mp.y)) {
	            highlighted = field[x][y];
	            highlighted.Highlight = true;
		    highlighted.draw(conField);
		    return;
		}
      }, false);
    canField.addEventListener('mousedown', function(evt) {
	if(highlighted) {
	    $scope.in.x = highlighted.Pos.X;
	    $scope.in.y = highlighted.Pos.Y;
	    $scope.$digest();
	}
    }, false);
    
    var onInUpdate = function() {
	var remove = function() {
	    marked.Marked = false;
	    marked.Number = oldNumber;
	    marked.draw(conField);
	    marked = null;
	};
	if($scope.in.x == null && $scope.in.y == null) {
	    remove();
	    return;
	}
	if(!field[$scope.in.x] || !field[$scope.in.x][$scope.in.y])
	    return;
	if(marked) {
	    if(marked == field[$scope.in.x][$scope.in.y])
		return;
	    else 
		remove();
	}
	if(field[$scope.in.x][$scope.in.y].Id >= 0)
	    return;
	marked = field[$scope.in.x][$scope.in.y];
	oldNumber = marked.Number;
	marked.Number = $scope.placeActive ? piece.Number : [0,0,0];
	marked.Marked = true;
	marked.draw(conField);
    };
    $scope.$watch('in.x', onInUpdate);
    $scope.$watch('in.y', onInUpdate);
    
}]);
