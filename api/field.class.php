<?php

class PlayerField {
	public $Id;
	
	public $Field = array();
	private $Changed = false;
	private $Points = 0;
	
	static $Rows = array(
		array( //Up  |
			array([0,0], [1,0], [2,0]),
			array([0,1], [1,1], [2,1], [3,0]),
			array([0,2], [1,2], [2,2], [3,1], [4,0]),
			array([1,3], [2,3], [3,2], [4,1]),
			array([2,4], [3,3], [4,2])
		),
		array( //Left   /
			array([0,2], [1,3], [2,4]),
			array([0,1], [1,2], [2,3], [3,3]),
			array([0,0], [1,1], [2,2], [3,2], [4,2]),
			array([1,0], [2,1], [3,1], [4,1]),
			array([2,0], [3,0], [4,0])
		),
		array( //Right   \
			array([0,0], [0,1], [0,2]),
			array([1,0], [1,1], [1,2], [1,3]),
			array([2,0], [2,1], [2,2], [2,3], [2,4]),
			array([3,0], [3,1], [3,2], [3,3]),
			array([4,0], [4,1], [4,2])
		)
	);
	
	public function __construct($id) {
		$this->Id = $id;
		$this->Field[0] = array_fill(0, 3, null);
		$this->Field[1] = array_fill(0, 4, null);
		$this->Field[2] = array_fill(0, 5, null);
		$this->Field[3] = array_fill(0, 4, null);
		$this->Field[4] = array_fill(0, 3, null);
	}
	
	public static function create() {
		global $db, $app;
		$db->query("INSERT INTO field() VALUES()");
		$id = $db->query("SELECT LAST_INSERT_ID()")->fetch_row()[0];
		$field = new PlayerField($id);
		$app->saveObj($field);
		return $field;
	}
	
	public static function load($id) {
		global $db,$app;
		$field = new PlayerField($id);
		
		$row = $db->query("SELECT * FROM field WHERE id = $id")->fetch_assoc();
		$field->Points = $row["points"];
		for($x = 0; $x < 5; $x++)
			for($y = 0; $y < 5; $y++)
				if(array_key_exists($x, $field->Field) && array_key_exists($y, $field->Field[$x]) && $row["f".$x.$y])
					$field->Field[$x][$y] = Piece::load($row["f".$x.$y]);
		$app->saveObj($field);
		return $field;
	}
	
	public function placePiece($piece, $x, $y) {
		if(!array_key_exists($x, $this->Field) || !array_key_exists($y, $this->Field[$x]))
			return "Out of Range";
		if($this->Field[$x][$y] != null)
			return "Taken";
		$this->Field[$x][$y] = $piece;
		$this->Changed = true;
		$this->getPoints();
		
		return "OK";
	}
	
	public function getPoints() {
		if(!$this->Changed)
			return $this->Points;
		
		$this->Points = 0;
		
		for($direct = 0; $direct < 3; $direct++)
			for($row = 0; $row < 5; $row++) {
				if($this->Field[self::$Rows[$direct][$row][0][0]][self::$Rows[$direct][$row][0][1]]) {
					$old = $this->Field[self::$Rows[$direct][$row][0][0]][self::$Rows[$direct][$row][0][1]]->Number[$direct];
					$found = 0;
					foreach(self::$Rows[$direct][$row] as $elem)  {
						if(!$this->Field[$elem[0]][$elem[1]] || $this->Field[$elem[0]][$elem[1]]->Number[$direct] != $old) {
							$found = 0;
							break;
						}
						$found++;
					}
					$this->Points += $old * $found;
				}
			}
		$this->Changed = false;
		return $this->Points;	
	}
	
	public function destroy() {
		global $db;
		$db->query("UPDATE player SET field = 0 WHERE field = $this->Id");
		$db->query("DELETE FROM field WHERE id = $this->Id");
	}
	
	public function save() {
		global $db;
		$sql = "UPDATE field SET points=$this->Points ";
		for($x = 0; $x < 5; $x++)
			for($y = 0; $y < 5; $y++)
				if(array_key_exists($x, $this->Field) && array_key_exists($y, $this->Field[$x]))
					$sql .= ",f".$x.$y."=".($this->Field[$x][$y] ? $this->Field[$x][$y]->Id : "NULL")." ";
		$sql .= "WHERE id = $this->Id";
		//var_dump($this->Field);
		$db->query($sql) or die($sql."\n".$db->error);
	}
};

?>
