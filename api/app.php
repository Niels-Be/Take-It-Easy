<?php 
require_once 'piece.class.php';
require_once 'field.class.php';
require_once 'game.class.php';
require_once 'player.class.php';

session_start();

$db = new mysqli("localhost", "data", "abcTest123", "TakeItEasy");
if ($db->connect_errno) {
	die("Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error);
}

$app = new App;
// PUT  /Player/Register
// POST /Player/Login
// POST /Player/Logout
// GET  /Player/Update
// POST /Player/Place
// PUT  /Game/Create
// POST /Game/Join
// POST /Game/Leave
// POST /Game/Start
$app->route("POST", "@^/Player/Register$@", function($params) {
	global $db;
	$name = $db->escape_string($_POST['name']);
	$pw = $db->escape_string($_POST['password']);
	if($db->query("INSERT INTO player(name, password) VALUES('$name', PASSWORD('$pw'))")) {
		echo "OK";
		$_SESSION["pid"] = $db->query("SELECT LAST_INSERT_ID()")->fetch_row()[0];
	}
	else
		echo "ERROR";
});
$app->route("POST", "@^/Player/Login$@", function($params) {
	global $db;
	$name = $db->escape_string($_POST['name']);
	$pw = $db->escape_string($_POST['password']);
	if($res = $db->query("SELECT id FROM player WHERE name = '$name' AND password=PASSWORD('$pw')")) {
		if($res->num_rows > 0) {
			$_SESSION["pid"] = $res->fetch_row()[0];
			$res->close();
			echo "OK";
		} else
			echo "FAIL";
	} else
		echo "ERROR";
});
$app->route("GET", "@^/Game/List$@", function($params) {
	echo json_encode(Game::getList());
});

if(empty($_SESSION["pid"]))
	$app->route("GET", "@^/Player$@", function($params) {
		echo "ERROR";
	});
else
if(!empty($_SESSION["pid"])) {
	$player = Player::load($_SESSION["pid"]); 
	$app->route("GET", "@^/Player$@", function($params) {
		global $player;
		echo json_encode(array("Id" => $player->Id, "Name" => $player->Name, "State" => $player->getState()));
	});
	$app->route("POST", "@^/Player/Logout$@", function($params) {
		unset($_SESSION["pid"]);
		echo "OK";
	});
	
	$app->route("GET", "@^/Player/Update$@", function($params) {
		global $player;
		echo json_encode($player->update());
	});
	$app->route("POST", "@^/Player/Place$@", function($params) {
		global $player;
		echo json_encode($player->placePiece($_POST["x"], $_POST["y"]));
	});
	
	$app->route("POST", "@^/Game/Create$@", function($params) {
		global $player;
		echo json_encode($player->createGame($_POST["name"]));
	});
	$app->route("POST", "@^/Game/Join$@", function($params) {
		global $player;
		echo json_encode($player->joinGame(Game::load($_POST["Id"], $player)));
	});
	$app->route("POST", "@^/Game/Leave$@", function($params) {
		global $player;
		echo json_encode($player->leaveGame());
	});
	$app->route("POST", "@^/Game/Start$@", function($params) {
		global $player;
		echo json_encode($player->startGame());
	});
	$app->route("GET", "@^/Game/Field$@", function($params) {
		global $player;
		echo json_encode($player->Field->Field);
	});
}

$app->run($_GET["q"]);
//if(isset($player))
//	$player->save();


function startsWith($haystack, $needle) {
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
}

class App {
	public $Routes = array();
	public $toSave = array();

	public function route($method, $path, $callback) {
		$this->Routes[] = array(
				"Method" => $method,
				"Path" => $path,
				"Func" => $callback
		);
	}
	
	public function run($p) {
		
		if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_SERVER["CONTENT_TYPE"]) && startsWith($_SERVER["CONTENT_TYPE"], "application/json")) {
			$_POST = json_decode(file_get_contents("php://input"), true);
		}
		
		foreach($this->Routes as $route) {
			if($_SERVER['REQUEST_METHOD'] == $route["Method"] && preg_match($route["Path"], $p, $matches)) {
				$route["Func"]($matches);
				break;
			}
		}
		foreach($this->toSave as $s)
			$s->save();
	}
	
	public function saveObj($obj) {
		$this->toSave[] = $obj;
	}
};

?>