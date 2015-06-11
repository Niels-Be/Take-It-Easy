<?php 

class Game { 
	public $Id;
	
	public $Name = "";
	public $Host = 0;
	public $Player = array();
	public $Round = 0;
	public $Pieces = null;
	public $CurrentPiece = null;
	
	public function __construct($id) {
		$this->Id = $id;
		$this->Pieces = Piece::createAllPieces();
	}
	
	
	static function create($name, $host) {
		global $db,$app;
		$db->query("INSERT INTO game(name, host) VALUES('$name', $host)");
		$id = $db->query("SELECT LAST_INSERT_ID()")->fetch_row()[0];
		$game = new Game($id);
		$game->Host = $host;
		$game->Name = $name;
		$app->saveObj($game);
		return $game;
	}
	
	static function load($id, $loader = null) {
		global $db,$app;
		$game = new Game($id);
		$row = $db->query("SELECT name,host,round,current_piece,unused_pieces FROM game WHERE id = $id")->fetch_assoc();
		$game->Name = $row["name"];
		$game->Host = $row["host"];
		$game->Round = $row["round"];
		$game->CurrentPiece = Piece::load($row["current_piece"]);
		
		$arr = explode(",", $row["unused_pieces"]);
		$game->Pieces = array();
		foreach($arr as $a)
			$game->Pieces[] = Piece::load($a);
		
		$res = $db->query("SELECT id FROM player WHERE game=$id");
		while($row = $res->fetch_row()) {
			if($loader && $loader->Id == $row[0])
				$game->Player[] = $loader;
			else
				$game->Player[] = Player::load($row[0], $game);
		}
		$app->saveObj($game);
		return $game;
	}
	
	static function getList() {
		global $db;
		$res = $db->query("SELECT g.id,g.name,g.round,COUNT(p.id) as `count` FROM game AS g LEFT JOIN player AS p ON g.id = p.game GROUP BY g.id,g.name,g.round");
		$erg = array();
		while($row = $res->fetch_assoc())
			$erg[] = $row;
		return $erg;
	}
	
	public function addPlayer($player) {
		if($this->Round > 0) {
			return false; //Game already running
		}
		$this->Player[] = $player;
		return true;
	}
	
	public function removePlayer($player) {
		if(($key = array_search($player, $this->Player)) !== false) {
		    unset($this->Player[$key]);
		}
		if(count($this->Player) == 0) {
			$this->destroy();
			return false;
		}
		else if($player->Id == $this->Host) {
			$this->Host = array_values($this->Player)[0]->Id;
		}
		return true;
	}
	
	public function isStarted() {
		return $this->Round > 0;
	}
	
	public function drawPiece() {
		$this->Round++;
		foreach($this->Player as $ply)
			$ply->nextRound();
		shuffle($this->Pieces);
		$this->CurrentPiece = array_pop($this->Pieces);
		return $this->CurrentPiece;
	}
	
	public function checkNextRound() {
		if($this->Round > 19)
			return "FINISHED";
		
		if($this->CurrentPiece != null) {
			foreach($this->Player as $ply)
				if(!$ply->Ready)
					return "IN_PROGRESS";
		}
		$this->drawPiece();
		return "NEXT_ROUND";
	}
	
	public function start() {
		if($this->Round > 0)
			return false;
		foreach($this->Player as $ply)
			$ply->enterGame();
		$this->drawPiece();
	}
	
	public function destroy() {
		global $db;
		$db->query("UPDATE player SET game = 0 WHERE game = $this->Id");
		$db->query("DELETE FROM game WHERE id = $this->Id");
	}
	
	public function save() {
		global $db;
		$piece = $this->CurrentPiece ? $this->CurrentPiece->Id : "NULL";
		$arr = array();
		foreach($this->Pieces as $p)
			$arr[] = $p->Id;
		$unused = implode(",", $arr);
		$db->query("UPDATE game SET host=$this->Host,round=$this->Round,current_piece=$piece,unused_pieces='$unused' WHERE id = $this->Id");
	}
};

?>