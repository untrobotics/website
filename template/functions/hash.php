<?php
class Hashids implements HashidsInterface {
	const SEP_DIV = 3.5;
	const GUARD_DIV = 12;
	protected $alphabet;
	protected $seps = 'cfhistuCFHISTU';
	protected $guards;
	protected $minHashLength;
	protected $salt;
	public function __construct($salt = '', $minHashLength = 0, $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890') {
		$this->salt = $salt;
		$this->minHashLength = $minHashLength;
		$this->alphabet = implode('', array_unique(str_split($alphabet)));

		if (strlen($this->alphabet) < 16) {
			throw new HashidsException('Alphabet must contain at least 16 unique characters.');
		}

		if (strpos($this->alphabet, ' ') !== false) {
			throw new HashidsException('Alphabet can\'t contain spaces.');
		}

		$alphabetArray = str_split($this->alphabet);
		$sepsArray = str_split($this->seps);

		$this->seps = implode('', array_intersect($sepsArray, $alphabetArray));
		$this->alphabet = implode('', array_diff($alphabetArray, $sepsArray));
		$this->seps = $this->shuffle($this->seps, $this->salt);

		if (!$this->seps || (strlen($this->alphabet) / strlen($this->seps)) > self::SEP_DIV) {
			$sepsLength = (int) ceil(strlen($this->alphabet) / self::SEP_DIV);

			if ($sepsLength > strlen($this->seps)) {
				$diff = $sepsLength - strlen($this->seps);
				$this->seps .= substr($this->alphabet, 0, $diff);
				$this->alphabet = substr($this->alphabet, $diff);
			}
		}

		$this->alphabet = $this->shuffle($this->alphabet, $this->salt);
		$guardCount = (int) ceil(strlen($this->alphabet) / self::GUARD_DIV);

		if (strlen($this->alphabet) < 3) {
			$this->guards = substr($this->seps, 0, $guardCount);
			$this->seps = substr($this->seps, $guardCount);
		} else {
			$this->guards = substr($this->alphabet, 0, $guardCount);
			$this->alphabet = substr($this->alphabet, $guardCount);
		}
	}
	public function encode() {
		$ret = '';
		$numbers = func_get_args();

		if (func_num_args() == 1 && is_array(func_get_arg(0))) {
			$numbers = $numbers[0];
		}

		if (!$numbers) {
			return $ret;
		}

		foreach ($numbers as $number) {
			$isNumber = ctype_digit((string) $number);

			if (!$isNumber) {
				return $ret;
			}
		}

		$alphabet = $this->alphabet;
		$numbersSize = count($numbers);
		$numbersHashInt = 0;

		foreach ($numbers as $i => $number) {
			$numbersHashInt += Math::intval(Math::mod($number, ($i + 100)));
		}

		$lottery = $ret = $alphabet[$numbersHashInt % strlen($alphabet)];
		foreach ($numbers as $i => $number) {
			$alphabet = $this->shuffle($alphabet, substr($lottery.$this->salt.$alphabet, 0, strlen($alphabet)));
			$ret .= $last = $this->hash($number, $alphabet);

			if ($i + 1 < $numbersSize) {
				$number %= (ord($last) + $i);
				$sepsIndex = Math::intval(Math::mod($number, strlen($this->seps)));
				$ret .= $this->seps[$sepsIndex];
			}
		}

		if (strlen($ret) < $this->minHashLength) {
			$guardIndex = ($numbersHashInt + ord($ret[0])) % strlen($this->guards);

			$guard = $this->guards[$guardIndex];
			$ret = $guard.$ret;

			if (strlen($ret) < $this->minHashLength) {
				$guardIndex = ($numbersHashInt + ord($ret[2])) % strlen($this->guards);
				$guard = $this->guards[$guardIndex];

				$ret .= $guard;
			}
		}

		$halfLength = (int) (strlen($alphabet) / 2);
		while (strlen($ret) < $this->minHashLength) {
			$alphabet = $this->shuffle($alphabet, $alphabet);
			$ret = substr($alphabet, $halfLength).$ret.substr($alphabet, 0, $halfLength);

			$excess = strlen($ret) - $this->minHashLength;
			if ($excess > 0) {
				$ret = substr($ret, $excess / 2, $this->minHashLength);
			}
		}

		return $ret;
	}
	public function decode($hash) {
		$ret = [];

		if (!is_string($hash) || !($hash = trim($hash))) {
			return $ret;
		}

		$alphabet = $this->alphabet;

		$ret = [];

		$hashBreakdown = str_replace(str_split($this->guards), ' ', $hash);
		$hashArray = explode(' ', $hashBreakdown);

		$i = count($hashArray) == 3 || count($hashArray) == 2 ? 1 : 0;

		$hashBreakdown = $hashArray[$i];

		if (isset($hashBreakdown[0])) {
			$lottery = $hashBreakdown[0];
			$hashBreakdown = substr($hashBreakdown, 1);

			$hashBreakdown = str_replace(str_split($this->seps), ' ', $hashBreakdown);
			$hashArray = explode(' ', $hashBreakdown);

			foreach ($hashArray as $subHash) {
				$alphabet = $this->shuffle($alphabet, substr($lottery.$this->salt.$alphabet, 0, strlen($alphabet)));
				$result = $this->unhash($subHash, $alphabet);
				if (Math::greaterThan($result, PHP_INT_MAX)) {
					$ret[] = Math::strval($result);
				} else {
					$ret[] = Math::intval($result);
				}
			}

			if ($this->encode($ret) != $hash) {
				$ret = [];
			}
		}

		return $ret;
	}
	public function encodeHex($str) {
		if (!ctype_xdigit((string) $str)) {
			return '';
		}

		$numbers = trim(chunk_split($str, 12, ' '));
		$numbers = explode(' ', $numbers);

		foreach ($numbers as $i => $number) {
			$numbers[$i] = hexdec('1'.$number);
		}

		return call_user_func_array([$this, 'encode'], $numbers);
	}
	public function decodeHex($hash) {
		$ret = '';
		$numbers = $this->decode($hash);

		foreach ($numbers as $i => $number) {
			$ret .= substr(dechex($number), 1);
		}

		return $ret;
	}
	protected function shuffle($alphabet, $salt) {
		$saltLength = strlen($salt);

		if (!$saltLength) {
			return $alphabet;
		}

		for ($i = strlen($alphabet) - 1, $v = 0, $p = 0; $i > 0; $i--, $v++) {
			$v %= $saltLength;
			$p += $int = ord($salt[$v]);
			$j = ($int + $v + $p) % $i;

			$temp = $alphabet[$j];
			$alphabet[$j] = $alphabet[$i];
			$alphabet[$i] = $temp;
		}

		return $alphabet;
	}
	protected function hash($input, $alphabet) {
		$hash = '';
		$alphabetLength = strlen($alphabet);

		do {
			$hash = $alphabet[Math::intval(Math::mod($input, $alphabetLength))].$hash;

			$input = Math::divide($input, $alphabetLength);
		} while (Math::greaterThan($input, 0));

		return $hash;
	}
	protected function unhash($input, $alphabet) {
		$number = 0;
		$inputLength = strlen($input);

		if ($inputLength && $alphabet) {
			$alphabetLength = strlen($alphabet);
			$inputChars = str_split($input);

			foreach ($inputChars as $char) {
				$position = strpos($alphabet, $char);
				$number = Math::multiply($number, $alphabetLength);
				$number = Math::add($number, $position);
			}
		}

		return $number;
	}
}
//use InvalidArgumentException;
class HashidsException extends InvalidArgumentException {
	//
}
interface HashidsInterface {
	public function encode();
	public function decode($hash);
	public function encodeHex($str);
	public function decodeHex($hash);
}
class Math {
	public static function add($a, $b) {
		if (function_exists('gmp_add')) {
			return gmp_add($a, $b);
		}

		return bcadd($a, $b, 0);
	}
	public static function multiply($a, $b) {
		if (function_exists('gmp_mul')) {
			return gmp_mul($a, $b);
		}

		return bcmul($a, $b, 0);
	}
	public static function divide($a, $b) {
		if (function_exists('gmp_div_q')) {
			return gmp_div_q($a, $b);
		}

		return bcdiv($a, $b, 0);
	}
	public static function mod($n, $d) {
		if (function_exists('gmp_mod')) {
			return gmp_mod($n, $d);
		}

		return bcmod($n, $d);
	}
	public static function greaterThan($a, $b) {
		if (function_exists('gmp_cmp')) {
			return gmp_cmp($a, $b) > 0;
		}

		return bccomp($a, $b, 0) > 0;
	}
	public static function intval($a) {
		if (function_exists('gmp_intval')) {
			return gmp_intval($a);
		}

		return intval($a);
	}
	public static function strval($a) {
		if (function_exists('gmp_strval')) {
			return gmp_strval($a);
		}

		return $a;
	}
	public static function get($a) {
		if (function_exists('gmp_init')) {
			return gmp_init($a);
		}

		return $a;
	}
}

//use Hashids\Hashids;
function obfuscate_hash($hash, $length = -1) {
	if ($length == -1) {
		$length = strlen($hash);
	}
	$hashids = new Hashids(WEBSITE_NAME, $length);
	return $hashids->encodeHex(md5($hash));
}

function encode_hash($hash, $length = -1) {
	if ($length == -1) {
		$length = strlen($hash);
	}
	$hashids = new Hashids(WEBSITE_NAME, $length);
	return $hashids->encode($hash);
}

function decode_hash($hash) {
	$hashids = new Hashids(WEBSITE_NAME);
	return $hashids->decode($hash);
}
