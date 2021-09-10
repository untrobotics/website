<?php
class Semester {
	const SPRING = 0;
	const AUTUMN = 1;
    const SUMMER = 2;

	public static function get_name_from_value($x) {
		$semesterClass = new ReflectionClass ( 'Semester' );
		$constants = $semesterClass->getConstants();

		$constName = null;
		foreach ( $constants as $name => $value )
		{
			if ( $value == $x )
			{
				$constName = $name;
				break;
			}
		}

		return $constName;
	}
}

class untrobotics {
	private $db;
	private $is_sandbox = false;
	
	public function __construct($db) {
		$this->db = $db;
	}
	
	public function set_sandbox($is_sandbox) {
		$this->is_sandbox = $is_sandbox;
	}
	public function get_sandbox() {
		return $this->is_sandbox;
	}
	
	// dues functions
    public function get_user_by_discord_id($discord_id) {
        $q = $this->db->query('SELECT * FROM users WHERE discord_id = "' . $this->db->real_escape_string($discord_id) . '"');
        if ($q) {
            $r = $q->fetch_array(MYSQLI_ASSOC);
            return $r;
        }
        return null;
    }

	public function is_user_in_good_standing($userinfo) {
	    $uid = null;
	    if (is_array($userinfo)) {
	        $uid = $userinfo['id'];
        } else {
	        $uid = $userinfo;
        }

		$q = $this->db->query('
			SELECT * FROM dues_payments
			WHERE
				uid = "' . $this->db->real_escape_string($uid) . '" AND
				dues_term = "' . $this->get_current_term() . '" AND
				dues_year = "' . $this->get_current_year() . '"
			');
		
		if (!$q) {
			return false;
		}
		
		return $q->num_rows === 1;
	}

	// semester, dues functions
	public function get_current_term() {
		return $this->get_term_from_date(time());
	}
    public function get_next_term() {
        if ($this->get_current_term() == Semester::SPRING) {
            return Semester::AUTUMN;
        }
        return Semester::SPRING;
    }
	public function get_term_from_date($timestamp) {
		// is it spring semester or autumn semester?
		// we are going to allow some lee-way and not require new dues until september (term usually re-starts in late august)
		$month = date('m', $timestamp);
		if ($month <= 4) {
			return Semester::SPRING; // spring semester
		}
		return Semester::AUTUMN; // autumn semester
	}
	public function get_current_year() {
		return $this->get_year_from_date(time());
	}
    public function get_next_year_from_date($timestamp) {
        return date('Y', strtotime('+1 year', $timestamp));
    }
    public function get_next_year() {
        return $this->get_next_year_from_date(time());
    }
	public function get_year_from_date($timestamp) {
		return date('Y', $timestamp);
	}
}