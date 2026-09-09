<?php
/**
 * The two academic terms the club operates on. Stored as the integer constant
 * value (e.g. in dues_payments.dues_term); there is no summer dues cycle, so
 * SUMMER exists only for completeness.
 */
class Semester {
	const SPRING = 0;
	const AUTUMN = 1;
    const SUMMER = 2;

	/**
	 * Reverse-map a Semester constant value back to its name (e.g. 0 -> "SPRING").
	 *
	 * @param int $x A Semester::* constant value.
	 * @return string|null The constant's name, or null if no constant matches.
	 */
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

/**
 * Core club domain logic: dues / good-standing status and the semester+year
 * bookkeeping that dues are keyed on. Instantiated once as the global
 * $untrobotics (see template/top.php) with the shared $db handle.
 */
class untrobotics {
	private $db;
	private $is_sandbox = false;

	/**
	 * @param mysqli $db The shared database handle (an mmysqli instance).
	 */
	public function __construct($db) {
		$this->db = $db;
	}

	/**
	 * Toggle sandbox mode (set from the authenticated user's `sandbox` flag).
	 * Sandbox users exercise test payment/flows without touching real state.
	 *
	 * @param bool $is_sandbox
	 * @return void
	 */
	public function set_sandbox($is_sandbox) {
		$this->is_sandbox = $is_sandbox;
	}

	/**
	 * @return bool Whether the current request is running in sandbox mode.
	 */
	public function get_sandbox() {
		return $this->is_sandbox;
	}

	// dues functions

	/**
	 * Look up a user row by their linked Discord account id.
	 *
	 * @param string $discord_id The Discord user (snowflake) id.
	 * @return array|null The user row as an associative array, or null if none.
	 */
    public function get_user_by_discord_id($discord_id) {
        $q = $this->db->query('SELECT * FROM users WHERE discord_id = "' . $this->db->real_escape_string($discord_id) . '"');
        if ($q) {
            $r = $q->fetch_array(MYSQLI_ASSOC);
            return $r;
        }
        return null;
    }

	/**
	 * Whether a user has paid (non-refunded) dues for the current term + year.
	 *
	 * @param array|int $userinfo A user row (uses its 'id') or a raw user id.
	 * @return bool True iff exactly one matching non-refunded dues payment exists.
	 */
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
				dues_year = "' . $this->get_current_year() . '" AND
				refunded = 0
			');

		if (!$q) {
			return false;
		}

		return $q->num_rows === 1;
	}

	// semester, dues functions

	/**
	 * @return int The current Semester::* term, derived from today's date.
	 */
	public function get_current_term() {
		return $this->get_term_from_date(time());
	}

	/**
	 * The term after the given one (two-term cycle: SPRING <-> AUTUMN).
	 *
	 * @param int|null $term A Semester::* value; defaults to the current term.
	 * @return int The next Semester::* term.
	 */
    public function get_next_term($term = null) {
	    if (is_null($term)) {
	        $term = $this->get_current_term();
        }
        if ($term == Semester::SPRING) {
            return Semester::AUTUMN;
        }
        return Semester::SPRING;
    }

	/**
	 * The term before the given one (two-term cycle: SPRING <-> AUTUMN).
	 *
	 * @param int|null $term A Semester::* value; defaults to the current term.
	 * @return int The previous Semester::* term.
	 */
    public function get_prev_term($term = null) {
        if (is_null($term)) {
            $term = $this->get_current_term();
        }
        if ($term == Semester::SPRING) {
            return Semester::AUTUMN;
        }
        return Semester::SPRING;
    }

	/**
	 * Map a timestamp to a term. Months Jan–Apr count as SPRING; May onward as
	 * AUTUMN — deliberately lenient so returning members aren't marked overdue
	 * over the summer before the term restarts in late August.
	 *
	 * @param int $timestamp A Unix timestamp.
	 * @return int The Semester::* term for that date.
	 */
	public function get_term_from_date($timestamp) {
		// is it spring semester or autumn semester?
		// we are going to allow some lee-way and not require new dues until september (term usually re-starts in late august)
		$month = date('m', $timestamp);
		if ($month <= 4) {
			return Semester::SPRING; // spring semester
		}
		return Semester::AUTUMN; // autumn semester
	}

	/**
	 * @return string The current calendar year ("YYYY").
	 */
	public function get_current_year() {
		return $this->get_year_from_date(time());
	}

	/**
	 * @param int $timestamp A Unix timestamp.
	 * @return string The year one year after $timestamp ("YYYY").
	 */
    public function get_next_year_from_date($timestamp) {
        return date('Y', strtotime('+1 year', $timestamp));
    }

	/**
	 * @return string Next calendar year ("YYYY").
	 */
    public function get_next_year() {
        return $this->get_next_year_from_date(time());
    }

	/**
	 * @param int $timestamp A Unix timestamp.
	 * @return string The year one year before $timestamp ("YYYY").
	 */
    public function get_last_year_from_date($timestamp) {
        return date('Y', strtotime('-1 year', $timestamp));
    }

	/**
	 * @return string Previous calendar year ("YYYY").
	 */
    public function get_last_year() {
        return $this->get_last_year_from_date(time());
    }

	/**
	 * @param int $timestamp A Unix timestamp.
	 * @return string The calendar year of $timestamp ("YYYY").
	 */
	public function get_year_from_date($timestamp) {
		return date('Y', $timestamp);
	}
}
