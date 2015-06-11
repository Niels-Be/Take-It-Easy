<?php

class Piece {
	public $Id;
	public $Number = array(0,0,0);
	
	static $Colors = array("gray", "pink", "purple", "lightblue", "blue", "red", "green", "orange", "yellow");
	
	public function __construct($id, $up, $left, $right) {
		$this->Id = $id;
		$this->Number[0] = $up; //     | 1,5,9
		$this->Number[1] = $left; //   / 2,6,7
		$this->Number[2] = $right; //  \ 3,4,8
	}
	
	public function get() {
		return array("Id" => $this->Id, "Number" => $this->Number);
	}
	
	public static function load($id) {
		$Pieces = array(
				new Piece( 0, 1,2,3),
				new Piece( 1, 5,2,3),
				new Piece( 2, 9,2,3),
				new Piece( 3, 1,6,3),
				new Piece( 4, 5,6,3),
				new Piece( 5, 9,6,3),
				new Piece( 6, 1,7,3),
				new Piece( 7, 5,7,3),
				new Piece( 8, 9,7,3),
		
				new Piece( 9, 1,2,4),
				new Piece(10, 5,2,4),
				new Piece(11, 9,2,4),
				new Piece(12, 1,6,4),
				new Piece(13, 5,6,4),
				new Piece(14, 9,6,4),
				new Piece(15, 1,7,4),
				new Piece(16, 5,7,4),
				new Piece(17, 9,7,4),
		
				new Piece(18, 1,2,8),
				new Piece(19, 5,2,8),
				new Piece(20, 9,2,8),
				new Piece(21, 1,6,8),
				new Piece(22, 5,6,8),
				new Piece(23, 9,6,8),
				new Piece(24, 1,7,8),
				new Piece(25, 5,7,8),
				new Piece(26, 9,7,8)
		);
		return $Pieces[$id];
	}
	
	public static function createAllPieces() {
		return array(
			new Piece( 0, 1,2,3),
			new Piece( 1, 5,2,3),
			new Piece( 2, 9,2,3),
			new Piece( 3, 1,6,3),
			new Piece( 4, 5,6,3),
			new Piece( 5, 9,6,3),
			new Piece( 6, 1,7,3),
			new Piece( 7, 5,7,3),
			new Piece( 8, 9,7,3),
		
			new Piece( 9, 1,2,4),
			new Piece(10, 5,2,4),
			new Piece(11, 9,2,4),
			new Piece(12, 1,6,4),
			new Piece(13, 5,6,4),
			new Piece(14, 9,6,4),
			new Piece(15, 1,7,4),
			new Piece(16, 5,7,4),
			new Piece(17, 9,7,4),
		
			new Piece(18, 1,2,8),
			new Piece(19, 5,2,8),
			new Piece(20, 9,2,8),
			new Piece(21, 1,6,8),
			new Piece(22, 5,6,8),
			new Piece(23, 9,6,8),
			new Piece(24, 1,7,8),
			new Piece(25, 5,7,8),
			new Piece(26, 9,7,8)
		);
	}
	
	
};


?>
