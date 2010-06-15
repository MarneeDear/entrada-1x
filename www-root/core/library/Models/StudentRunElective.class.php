<?php

class StudentRunElective {
	private $id;
	private $user_id;
	private $group_name;
	private $university;
	private $start_month;
	private $end_month;
	private $start_year;
	private $end_year;
	private $location;
	
	function __construct($id, $user_id, $group_name, $university, $location, $start_month, $start_year, $end_month, $end_year) {
		$this->id = $id;
		$this->user_id = $user_id;
		$this->group_name = $group_name;
		$this->university = $university;
		$this->location = $location;
		$this->start_month = $start_month;
		$this->end_month = $end_month;
		$this->start_year = $start_year;
		$this->end_year = $end_year;
	}
	
	public function getID() {
		return $this->id;
	}
	
	public function getUserID() {
		return $this->user_id;	
	}

	public function getUniversity() {
		return $this->university;
	}
	
	public function getLocation () {
		return $this->location;
	}
	
	public function getGroupName(){
		return $this->group_name;
	}
	
	public function getDetails() {
		$elements = array();
		$elements[] = $this->group_name;
		$elements[] = $this->university;
		$elements[] = $this->location;
		$details = implode("\n", $elements);
		return $details;
	}
	
	public function getStartMonth() {
		return $this->start_month;
	}
	
	public function getStartYear() {
		return $this->start_year;
	}
	
	public function getEndMonth() {
		return $this->end_month;
	}

	public function getEndYear() {
		return $this->end_year;
	}
	
	public function getStartDate() {
		return array(
			"m" => $this->start_month,
			"y" => $this->start_year
		);
	}
	
	public function getEndDate() {
		return array(
			"m" => $this->end_month,
			"y" => $this->end_year
		);
	}
	
	public function getPeriod() {
		return formatDateRange($this->getStartDate(), $this->getEndDate()); 
	}
	
	public function isApproved() {
		return (bool)($this->approved);
	}
	
		
	public static function get($id) {
		global $db;
		$query		= "SELECT * FROM `student_student_run_electives` WHERE `id` = ".$db->qstr($id);
		$result = $db->getRow($query);
		if ($result) {
			
			$sre =  new StudentRunElective($result['id'], $result['user_id'], $result['group_name'], $result['university'], $result['location'], $result['start_month'], $result['start_year'], $result['end_month'], $result['end_year']);
			return $sre;
		}
	} 

	public static function create($user, $group_name, $university, $location, $start_month, $start_year, $end_month, $end_year) {
		global $db,$SUCCESS,$SUCCESSSTR,$ERROR,$ERRORSTR;
		$user_id = $user->getID();
		$query = "insert into `student_student_run_electives` (`user_id`, `group_name`,`university`,`location`,`start_month`, `start_year`, `end_month`, `end_year`) value (".$db->qstr($user_id).", ".$db->qstr($group_name).", ".$db->qstr($university).", ".$db->qstr($location).", ".$db->qstr($start_month).", ".$db->qstr($start_year).", ".$db->qstr($end_month).", ".$db->qstr($end_year).")";
		if(!$db->Execute($query)) {
			$ERROR++;
			$ERRORSTR[] = "Failed to create new Student-Run Elective.";
			application_log("error", "Unable to update a student_student_run_electives record. Database said: ".$db->ErrorMsg());
		} else {
			$SUCCESS++;
			$SUCCESSSTR[] = "Successfully added new Student-Run Elective.";
			$insert_id = $db->Insert_ID();
			return self::get($insert_id); 
		}
	}
	
	public function delete() {
		global $db,$SUCCESS,$SUCCESSSTR,$ERROR,$ERRORSTR;
		$query = "DELETE FROM `student_student_run_electives` where `id`=".$db->qstr($this->id);
		if(!$db->Execute($query)) {
			$ERROR++;
			$ERRORSTR[] = "Failed to remove Student-run Elective from database.";
			application_log("error", "Unable to delete a student_student_run_electives record. Database said: ".$db->ErrorMsg());
		} else {
			$SUCCESS++;
			$SUCCESSSTR[] = "Successfully removed Student-Run Elective.";
		}		
	}
}