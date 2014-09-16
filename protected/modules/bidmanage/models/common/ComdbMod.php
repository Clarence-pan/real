<?php
class ComdbMod {

	const INSERT_INTO = "insert into ";
	
	const LEFT_BRACKET = " ( ";
	
	const RIGHT_BRACKET = " ) ";
	
	const VALUES = " values ";
	
	const DUPLICATE = " on duplicate key update ";
	
	const COMMA = ",";
	
	const COMMA_UP = "'";
	
	const LIMIT = 1000;
	
	const SEMICOLON = ";";
	
	const SPACE = " ";
	
	const EMPTY_STR = "";
	
	const EQUALITY = "=";
	
	/**
	 * 生成共通插入语句
	 */
	public function generateComInsert($tableName, $column, $columnValue, $data, $defaultValue) {
		$inserts = array();
		$insertObj = self::EMPTY_STR;
		$insertMajor = $this->generateInsertMajor($tableName, $column);
		$insertData = self::EMPTY_STR;
		$count = 0;
		foreach ($data as $dataObj) {
			$count++;
			$insertData = $insertData.$this->generateInsertSingleData($columnValue, $dataObj, $defaultValue).self::COMMA;
			if ($count % self::LIMIT == 0) {
				$insertObj = $insertMajor.substr($insertData, 0, strlen($insertData) - 1);
				$insertObj = $insertObj.self::SEMICOLON;
				array_push($inserts, $insertObj);
				$insertData = self::EMPTY_STR;
			} else if ($count == count($data)) {
				$insertObj = $insertMajor.substr($insertData, 0, strlen($insertData) - 1);
				$insertObj = $insertObj.self::SEMICOLON;
				array_push($inserts, $insertObj);
				$insertData = self::EMPTY_STR;
			}
		}
		
		return $inserts;	
	}
	
	public function generateInsertMajor($tableName, $column) {
		$insertMajor = self::INSERT_INTO.$tableName.self::LEFT_BRACKET;
		foreach ($column as $columnObj) {
			$insertMajor = $insertMajor.$columnObj.self::COMMA;
		}
		$insertMajor = substr($insertMajor, 0, strlen($insertMajor) - 1);
		
		return $insertMajor.self::RIGHT_BRACKET.self::VALUES;
	}
	
	public function generateInsertSingleData($columnValue, $data, $defaultValue) {
		$insertData = self::LEFT_BRACKET;
		foreach($columnValue as $columnValueObj) {
			$insertData = $insertData.self::COMMA_UP.$data[$columnValueObj].self::COMMA_UP.self::COMMA;
		}
		foreach($defaultValue as $defaultValueObj) {
			$insertData = $insertData.self::COMMA_UP.$defaultValueObj.self::COMMA_UP.self::COMMA;
		}
		$insertData = substr($insertData, 0, strlen($insertData) - 1);
	
		return $insertData.self::RIGHT_BRACKET;
	}	
	
	/**
	 * 生成共通更新语句
	 */
	public function generateComUpdate($tableName, $column, $columnValue, $data, $defaultValue, $updateColumn) {
		$updates = array();
		$updateObj = self::EMPTY_STR;
		$updateMajor = $this->generateInsertMajor($tableName, $column);
		$updateData = self::EMPTY_STR;
		$count = 0;
		foreach ($data as $dataObj) {
			$count++;
			$updateData = $updateData.$this->generateInsertSingleData($columnValue, $dataObj, $defaultValue).self::COMMA;
			if ($count % self::LIMIT == 0) {
				$updateObj = $updateMajor.substr($updateData, 0, strlen($updateData) - 1);
				$updateObj = $updateObj.self::DUPLICATE.$this->generateDuplicateColumn($updateColumn);
				$updateObj = $updateObj.self::SEMICOLON;
				array_push($updates, $updateObj);
				$updateData = self::EMPTY_STR;
			} else if ($count == count($data)) {
				$updateObj = $updateMajor.substr($updateData, 0, strlen($updateData) - 1);
				$updateObj = $updateObj.self::DUPLICATE.$this->generateDuplicateColumn($updateColumn); 
				$updateObj = $updateObj.self::SEMICOLON;
				array_push($updates, $updateObj);
				$updateData = self::EMPTY_STR;
			}
		}
		return $updates;	
	}
	
	public function generateDuplicateColumn($column) {
		$duplicateColumn = self::EMPTY_STR;
		foreach ($column as $columnObj) {
			$duplicateColumn = $duplicateColumn.$columnObj.self::EQUALITY.self::VALUES.self::LEFT_BRACKET.$columnObj.self::RIGHT_BRACKET.self::COMMA;
		}
		return substr($duplicateColumn, 0, strlen($duplicateColumn) - 1);
	}
	
}

?>