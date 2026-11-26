<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Central place for the clinic's appointment booking rules.
 *
 *  - Appointments must be booked at least LEAD_DAYS days in advance.
 *  - Only a WINDOW_DAYS-long window of dates is open, starting at the earliest date.
 *  - The clinic accepts at most DAILY_LIMIT appointments per calendar day.
 */
class BookingRules
{
    public const LEAD_DAYS = 7;

    public const WINDOW_DAYS = 7;

    public const DAILY_LIMIT = 2;

    public const TIMEZONE = 'Asia/Manila';

    /** Statuses that occupy one of the daily appointment slots. */
    public const ACTIVE_STATUSES = ['requested', 'confirmed', 'in_progress'];

    public static function today(): Carbon
    {
        return Carbon::now(self::TIMEZONE)->startOfDay();
    }

    public const CANCELLATION_REASON_MIN = 5;

    /**
     * One active appointment per patient account. An appointment is "active" while it is
     * requested, confirmed or in progress and has not already passed.
     */
    public static function activeAppointmentFor(User $user): ?Appointment
    {
        return Appointment::where('user_id', $user->id)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->where('starts_at', '>=', self::today()->toDateTimeString())
            ->orderBy('starts_at')
            ->first();
    }

    public static function oneAppointmentPolicy(): string
    {
        return 'Each patient account may only hold one appointment at a time. To book a different date, your current appointment must first be cancelled, and a reason for the cancellation is required.';
    }

    public static function earliest(): Carbon
    {
        return self::today()->addDays(self::LEAD_DAYS);
    }

    public static function latest(): Carbon
    {
        return self::earliest()->addDays(self::WINDOW_DAYS - 1);
    }

    public static function bookedCount(CarbonInterface|string $date): int
    {
        $day = Carbon::parse($date)->toDateString();

        return Appointment::whereIn('status', self::ACTIVE_STATUSES)
            ->whereDate('starts_at', $day)
            ->count();
    }

    /**
     * Returns a human-readable reason why the date cannot be booked, or null when it can.
     */
    public static function unavailableReason(CarbonInterface|string $date): ?string
    {
        $day = Carbon::parse($date, self::TIMEZONE)->startOfDay();
        $earliest = self::earliest();
        $latest = self::latest();

        if ($day->lt(self::today())) {
            return 'This date has already passed.';
        }

        if ($day->lt($earliest)) {
            return sprintf(
                'Appointments must be booked at least %d days in advance. The earliest available date is %s.',
                self::LEAD_DAYS,
                $earliest->format('l, F j'),
            );
        }

        if ($day->gt($latest)) {
            return sprintf(
                'Booking is only open for %d days at a time (%s to %s). Please choose a date within that window.',
                self::WINDOW_DAYS,
                $earliest->format('M j'),
                $latest->format('M j'),
            );
        }

        if (self::bookedCount($day) >= self::DAILY_LIMIT) {
            return sprintf(
                'Fully booked. The clinic accepts only %d appointments per day and this date is already full. Please choose another date.',
                self::DAILY_LIMIT,
            );
        }

        return null;
    }

    /**
     * Day cards for the booking pickers: the lead-time days (blocked) followed by the open window.
     *
     * @return array<int, array{date:string,weekday:string,day:string,month:string,reason:?string,available:bool}>
     */
    public static function days(): array
    {
        $days = [];
        $cursor = self::today();
        $end = self::latest();

        while ($cursor->lte($end)) {
            $reason = self::unavailableReason($cursor);
            $days[] = [
                'date' => $cursor->toDateString(),
                'weekday' => $cursor->format('D'),
                'day' => $cursor->format('j'),
                'month' => $cursor->format('M'),
                'reason' => $reason,
                'available' => $reason === null,
            ];
            $cursor = $cursor->copy()->addDay();
        }

        return $days;
    }
}
