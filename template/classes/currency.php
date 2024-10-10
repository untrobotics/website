<?php

/**
 * Class to represent currency with precision. Separates the fraction from the whole number of the currency.
 * Also contains helper functions to add and subtract Currency objects
 */
class Currency
{

    const currencies_with_fractions = ["USD"];
    /**
     * @var int Whole number amount of the currency (i.e., left of the decimal)
     */
    public $whole_number;
    /**
     * @var int Fraction or decimal amount of the currency
     */
    public $fraction;
    /**
     * @var string 3-letter code for what type of currency is represented (e.g., USD, JPY, EUR)
     */
    public $currency_code;

    /**
     * @param int $whole_number The whole number amount of the currency (i.e., left of the decimal), (e.g., dollars)
     * @param int $fraction The fraction or decimal amount of the currency (e.g., cents)
     * @param string $currency_code The 3-letter code for what type of currency is represented (e.g., USD)
     */
    public function __construct(int $whole_number = 0, int $fraction = 0, string $currency_code = "USD") {
        $this->whole_number = $whole_number;
        $this->fraction = $fraction;
        $this->currency_code = $currency_code;
        self::get_valid_currency($this);
    }

    private static function get_valid_currency(Currency &$c): void {
        switch ($c->currency_code) {
            case "USD":
            {
                $max_fraction = 100;
                break;
            }
            default:
            {
                throw new Exception("Unknown currency code");
            }
        }

        $c->whole_number += intdiv($c->fraction, $max_fraction);
        $c->fraction -= ($c->fraction % $max_fraction) * $max_fraction;
    }

    /**
     * Creates a {@see Currency} object from a string
     * @param string $string A string in the format of "[0-9]*(\.[0-9]{0,2})?"
     * @param string|null $currency_code The 3-letter currency code (e.g., USD)
     * @return Currency
     */
    public static function from_string(string $string, ?string $currency_code = null) {
        $s = explode(".", $string);
        if ($currency_code === null) {
            $matches = array();
            if (!preg_match("/[a-z]{3}/i", end($s), $matches)) {
                $currency_code = "USD";
            } else {
                $currency_code = $matches[0];
            }
        }
        return new Currency(intval($s[0]), isset($s[1]) ? intval($s[1]) : 0, $currency_code);
    }

    /**
     * Adds the {@see Currency} arguments together and returns the result
     * @param Currency ...$summands The currency amounts to add together
     * @return Currency The sum of the summands
     * @throws Exception Throws an error if the operand doesn't have the same currency code or the currency code of the current object isn't implemented
     */
    public static function add_(Currency ...$summands): Currency {
        $sum = new Currency(0, 0, $summands[0]->currency_code);
        foreach ($summands as $summand) {
            $sum->add($summand);
        }
        return $sum;
    }

    /**
     * Adds another {@see Currency} object to this
     * @param Currency $y The currency object to add to this
     * @return void
     * @throws Exception Throws an error if the operand doesn't have the same currency code or the currency code of the current object isn't implemented
     */
    public function add(Currency $y) {
        if ($this->currency_code !== $y->currency_code) {
            throw new Exception("Incompatible currency codes");
        }
        $this->fraction += $y->fraction;
        $this->whole_number += $y->whole_number;
        self::get_valid_currency($this);
    }

    /**
     * Subtracts the {@see Currency} arguments from $minuend and returns the result
     * @param Currency $minuend The currency amount to be subtracted from
     * @param Currency ...$subtrahends The currency amounts to subtract from the minuend
     * @return Currency The difference between the minuend and the subtrahends
     * @throws Exception Throws an error if the operand doesn't have the same currency code or the currency code of the current object isn't implemented
     */
    public static function subtract_(Currency $minuend, Currency ...$subtrahends): Currency {
        $c = new Currency($minuend->whole_number, $minuend->fraction, $minuend->currency_code);

        foreach ($subtrahends as $subtrahend) {
            $c->subtract($subtrahend);
        }

        return $c;
    }

    /**
     * Subtracts another {@see Currency} object from this (i.e., $this - $y)
     * @param Currency $y The currency object to serve as the subtrahend
     * @return void
     * @throws Exception Throws an error if the operand doesn't have the same currency code or the currency code of the current object isn't implemented
     */
    public function subtract(Currency $y) {
        if ($this->currency_code !== $y->currency_code) {
            throw new Exception("Incompatible currency codes");
        }
        $this->fraction -= $y->fraction;
        $this->whole_number -= $y->whole_number;
        self::get_valid_currency($this);
    }

    /**
     * Tells you if the {@see Currency} amount is zero
     * @return bool True if the amount is zero. False otherwise
     */
    public function is_zero(): bool {
        return $this->fraction === 0 && $this->whole_number === 0;
    }

    public function le(Currency $c): bool {
        return $this->lt($c) || $this->equals($c);
    }

    public function lt(Currency $c): bool {
        if ($this->whole_number < $c->whole_number) {
            return true;
        }
        if ($this->whole_number > $c->whole_number) {
            return false;
        }
        return $this->fraction < $c->fraction;
    }

    public function equals(Currency $c): bool {
        return $this->whole_number === $c->whole_number && $this->fraction === $c->fraction;
    }

    public function ge(Currency $c): bool {
        return $this->gt($c) || $this->equals($c);
    }

    public function gt(Currency $c): bool {
        return !$this->lt($c);
    }

    public function __toString() {
        $f = '';
        if (self::has_fraction($this->currency_code)) {
            $f = ".{$this->fraction}";
        }
        return "{$this->whole_number}{$f}";
    }

    public static function has_fraction(string $currency_code): bool {
        return in_array($currency_code, self::currencies_with_fractions);
    }
}