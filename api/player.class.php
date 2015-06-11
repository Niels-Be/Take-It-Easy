<?php 

class Player {
	public $Id;
	
	public $Name = "Gast";
	public $Field = null;
	public $Game = null;
	
	public $Ready = 0;
	public $NextRound = 0;
		
	public function __construct($id, $name) {
		$this->Id = $id;
		$this->Name = $name;
	}
	
	public static function load($id, $game = null) {
		global $db,$app;
		$res = $db->query("SELECT name,game,field,ready,nextRound FROM player WHERE id = $id");
		$row = $res->fetch_assoc();
		$ply = new Player($id, $row["name"]);
		$ply->Ready = $row["ready"];
		$ply->NextRound = $row["nextRound"];
		
		if($game)
			$ply->Game = $game;
		elseif($row["game"] > 0)
			$ply->Game = Game::load($row["game"], $ply);
		
		if($row["field"] > 0)
			$ply->Field = PlayerField::load($row["field"]);
		$app->saveObj($ply);
		return $ply;
	}

	/* API */
	public function createGame($name) {
		$this->Game = Game::create($name, $this->Id);
		$this->Game->addPlayer($this);
		return array("State" => "Lobby");
	}
	
	public function joinGame($game) {
		if(!$game->addPlayer($this))
			return "FAILED";
		
		$this->Game = $game;
		
		return array("State" => "Lobby");
	}
	
	public function leaveGame() {
		if($this->Game) {
			$this->Game->removePlayer($this);
			$this->Game = null;
		}
		if($this->Field) {
			$this->Field->destroy();
			$this->Field = null;
		}
		$this->Ready = 0;
		$this->NextRound = 0;
		return array("State" => "List");
	}
	
	public function startGame() {
		if($this->Game->Host != $this->Id)
			return "Not Host";
		$this->Game->start();
		return "OK";
	}
	
	public function getState() {
		if($this->Game == null && $this->Field == null)
			return "List";
		elseif($this->Game != null && $this->Field == null)
			return "Lobby";
		elseif($this->Game != null && $this->Field != null)
			return "Ingame";
		return "ERROR";
	}
	
	public function update() {
		$res = array();
		
		if($this->Game == null && $this->Field == null) { //Game List
			$res["State"] = "List";
			$res["Games"] = Game::getList();
		}
		elseif($this->Game != null && $this->Field == null) { //Game Lobby
			$res["State"] = "Lobby";
			$res["Player"] = array();
			foreach($this->Game->Player as $ply)
				$res["Player"][$ply->Id] = $ply->Name;
			$res["Host"] = $this->Game->Host;
			$res["GameName"] = $this->Game->Name;
		}
		elseif($this->Game != null && $this->Field != null) { // Ingame
			$res["State"] = "Ingame";
			$round = $this->Game->checkNextRound();
			if($round == "FINISHED") {
				//Show Results
				$res["State"] = "Results";
			}
			if($this->NextRound == true) {
				$this->NextRound = 0;
				$res["StartNextRound"] = true;
			}
			$res["Piece"] = $this->Game->CurrentPiece->get();
			$res["Round"] = $this->Game->Round;
			$res["Player"] = array();
			foreach($this->Game->Player as $ply) {
				if($ply == $this) continue;
				$res["Player"][] = array(
						"Name" => $ply->Name,
						"State" => $ply->Ready, 
						"Points" => $ply->Field->getPoints()
						);
			}
			$res["Points"] = $this->Field->getPoints();
			$res["GameName"] = $this->Game->Name;
			$res["Ready"] = $this->Ready;
		}
		return $res;
	}
	
	public function placePiece($x, $y) {
		if($this->Game == null || $this->Field == null) //Not Ingame
			return "Not Ingame";
		if($this->Ready)
			return "Placed";
		if(($r = $this->Field->placePiece($this->Game->CurrentPiece, $x, $y)) != "OK") 
			return $r;
		$this->Ready = 1;
		return "OK";
	}
	
	
	/* INTERNAL */
	public function enterGame() {
		$this->Field = PlayerField::create();
	}
	
	public function nextRound() {
		$this->Ready = 0;
		$this->NextRound = 1;
	}
	
	
	public function save() {
		global $db;
		$game = 0;
		if($this->Game){
			//$this->Game->save();
			$game = $this->Game->Id;
		}
		$field = 0;
		if($this->Field){
			//$this->Field->save();
			$field = $this->Field->Id;
		}
		$db->query("UPDATE player SET game=$game, field=$field, ready=$this->Ready, nextRound=$this->NextRound WHERE id=$this->Id") or die($db->error);
	}
};

?>