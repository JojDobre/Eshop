<?php

namespace Modules\Reservations;

use Modules\Reservations\Models\Booking;

/**
 * Kalendár vo formáte iCalendar (RFC 5545) pre Google, Outlook či Apple kalendár.
 */
final class ICalendar
{
    /**
     * @param  iterable<Booking>  $bookings
     */
    public static function render(iterable $bookings, string $name): string
    {
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//E-shop//Rezervacie//SK', 'CALSCALE:GREGORIAN', 'X-WR-CALNAME:'.self::escape($name)];

        foreach ($bookings as $booking) {
            $lines = [...$lines,
                'BEGIN:VEVENT',
                'UID:'.$booking->token.'@'.parse_url(url('/'), PHP_URL_HOST),
                'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
                'DTSTART:'.$booking->starts_at->copy()->utc()->format('Ymd\THis\Z'),
                'DTEND:'.$booking->ends_at->copy()->utc()->format('Ymd\THis\Z'),
                'SUMMARY:'.self::escape($booking->title().' – '.$booking->customer_name),
                'DESCRIPTION:'.self::escape(trim($booking->number.' · '.$booking->email.' '.$booking->phone."\n".$booking->note)),
                'STATUS:'.($booking->status === Booking::CANCELLED ? 'CANCELLED' : ($booking->status === Booking::PENDING ? 'TENTATIVE' : 'CONFIRMED')),
                'END:VEVENT',
            ];
        }

        $lines[] = 'END:VCALENDAR';

        // Riadky najviac 75 znakov (pokračovanie začína medzerou).
        return implode("\r\n", array_map(fn ($line) => implode("\r\n ", mb_str_split($line, 70)), $lines))."\r\n";
    }

    private static function escape(string $value): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $value);
    }
}
