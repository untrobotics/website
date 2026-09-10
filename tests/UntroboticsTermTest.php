<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../template/classes/untrobotics.php';

/**
 * Semester + term/year logic on the core `untrobotics` class. These methods are
 * pure (no DB), so we construct with a null handle and assert the date math that
 * dues/good-standing bookkeeping depends on.
 */
final class UntroboticsTermTest extends TestCase
{
    private function u(): untrobotics
    {
        return new untrobotics(null);
    }

    public function testSemesterNameFromValue(): void
    {
        $this->assertSame('SPRING', Semester::get_name_from_value(Semester::SPRING));
        $this->assertSame('AUTUMN', Semester::get_name_from_value(Semester::AUTUMN));
        $this->assertSame('SUMMER', Semester::get_name_from_value(Semester::SUMMER));
        $this->assertNull(Semester::get_name_from_value(99));
    }

    public function testTermFromDate(): void
    {
        // Jan–Apr are SPRING; May onward is AUTUMN (lenient summer carry-over).
        $this->assertSame(Semester::SPRING, $this->u()->get_term_from_date(strtotime('2026-01-15')));
        $this->assertSame(Semester::SPRING, $this->u()->get_term_from_date(strtotime('2026-04-30')));
        $this->assertSame(Semester::AUTUMN, $this->u()->get_term_from_date(strtotime('2026-05-01')));
        $this->assertSame(Semester::AUTUMN, $this->u()->get_term_from_date(strtotime('2026-09-15')));
        $this->assertSame(Semester::AUTUMN, $this->u()->get_term_from_date(strtotime('2026-12-31')));
    }

    public function testNextAndPrevTermToggleBetweenTheTwoTerms(): void
    {
        $u = $this->u();
        $this->assertSame(Semester::AUTUMN, $u->get_next_term(Semester::SPRING));
        $this->assertSame(Semester::SPRING, $u->get_next_term(Semester::AUTUMN));
        $this->assertSame(Semester::AUTUMN, $u->get_prev_term(Semester::SPRING));
        $this->assertSame(Semester::SPRING, $u->get_prev_term(Semester::AUTUMN));
    }

    public function testYearFromDate(): void
    {
        $this->assertSame('2026', $this->u()->get_year_from_date(strtotime('2026-06-01')));
        $this->assertSame('2027', $this->u()->get_next_year_from_date(strtotime('2026-06-01')));
        $this->assertSame('2025', $this->u()->get_last_year_from_date(strtotime('2026-06-01')));
    }
}
