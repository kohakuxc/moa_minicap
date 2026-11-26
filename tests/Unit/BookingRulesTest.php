<?php

namespace Tests\Unit;

use App\Support\BookingRules;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class BookingRulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Tuesday 24 Nov 2026, clinic time.
        Carbon::setTestNow(Carbon::parse('2026-11-24 10:00:00', BookingRules::TIMEZONE));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_earliest_bookable_date_is_seven_days_from_today(): void
    {
        $this->assertSame('2026-12-01', BookingRules::earliest()->toDateString());
    }

    public function test_booking_window_spans_seven_days(): void
    {
        $this->assertSame('2026-12-07', BookingRules::latest()->toDateString());
    }

    public function test_dates_inside_lead_time_explain_the_rule(): void
    {
        $reason = BookingRules::unavailableReason('2026-11-27');

        $this->assertStringContainsString('at least 7 days in advance', $reason);
        $this->assertStringContainsString('December 1', $reason);
    }

    public function test_today_is_blocked_by_lead_time(): void
    {
        $this->assertStringContainsString('in advance', BookingRules::unavailableReason('2026-11-24'));
    }

    public function test_dates_beyond_window_explain_the_rule(): void
    {
        $this->assertStringContainsString('only open for 7 days', BookingRules::unavailableReason('2026-12-20'));
    }

    public function test_past_dates_are_reported_as_passed(): void
    {
        $this->assertStringContainsString('already passed', BookingRules::unavailableReason('2026-11-01'));
    }

    public function test_daily_limit_is_two(): void
    {
        $this->assertSame(2, BookingRules::DAILY_LIMIT);
    }

    public function test_one_appointment_policy_mentions_cancellation_and_reason(): void
    {
        $policy = BookingRules::oneAppointmentPolicy();

        $this->assertStringContainsString('one appointment', $policy);
        $this->assertStringContainsString('reason', $policy);
        $this->assertSame(5, BookingRules::CANCELLATION_REASON_MIN);
    }

    public function test_only_open_statuses_count_as_active(): void
    {
        $this->assertSame(['requested', 'confirmed', 'in_progress'], BookingRules::ACTIVE_STATUSES);
    }}
