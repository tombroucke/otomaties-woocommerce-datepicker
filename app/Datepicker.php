<?php

namespace Otomaties\WooCommerce\Datepicker;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;

class Datepicker
{
    const DAYS = [
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
    ];

    public function __construct(protected int $id, protected Options $options) {}

    public function getId()
    {
        return $this->id;
    }

    public function disabledDays(): Collection
    {
        $days = collect(self::DAYS);

        $disabledDays = collect($this->options->disabledDays($this->getId()));

        return $disabledDays->map(function ($day) use ($days) {
            return $days->search($day);
        });
    }

    public function timeslots(\DateTime $date): Collection
    {
        $return = collect([]);
        collect($this->options->timeslots($this->getId()))
            ->each(function ($timeslot) use ($return, $date) {
                if (! ($timeslot['days'] ?? false) || ! ($timeslot['slots'] ?? false)) {
                    return;
                }
                collect($timeslot['days'])
                    ->each(function ($day) use ($timeslot, $return, $date) {
                        if (strtolower($date->format('l')) === $day) {
                            foreach ($timeslot['slots'] as $slot) {
                                $return->push([
                                    'time_from' => $slot['time_from'],
                                    'time_to' => $slot['time_to'],
                                ]);
                            }
                        }
                    });
            });

        $return = $return
            ->filter(function ($timeslot) use ($date) {
                [$hours, $minutes] = explode(':', $timeslot['time_from']);
                $dateTimeFrom = $date->setTime($hours, $minutes);

                return $dateTimeFrom >= $this->minDate();
            })
            ->unique()
            ->map(fn ($timeslot) => $timeslot['time_from'] . ' - ' . $timeslot['time_to'])
            ->sort();
            
        return apply_filters('otomaties_woocommerce_datepicker_timeslots', $return, $date);
    }

    public function administrationLabel()
    {
        return $this->options->administrationLabel($this->getId());
    }

    public function datepickerLabel()
    {
        return $this->options->datepickerLabel($this->getId());
    }

    public function timeslotLabel()
    {
        return $this->options->timeslotLabel($this->getId());
    }

    public function missingDateMessage()
    {
        return $this->options->missingDateMessage($this->getId()) ?? __('Please select a delivery date.', 'otomaties-woocommerce-datepicker');
    }

    public function invalidDateMessage()
    {
        return $this->options->invalidDateMessage($this->getId()) ?? __('Please select a valid delivery date.', 'otomaties-woocommerce-datepicker');
    }

    public function invalidDateTimeMessage()
    {
        return $this->options->invalidDateTimeMessage($this->getId()) ?? __('Please select a valid delivery date and time.', 'otomaties-woocommerce-datepicker');
    }

    public function disabledDates(): Collection
    {
        return apply_filters('otomaties_woocommerce_datepicker_disabled_dates', $this->dateRangesToArray($this->options->disabledDates($this->getId())), $this);
    }

    public function enabledDates(): Collection
    {
        return apply_filters('otomaties_woocommerce_datepicker_enabled_dates', $this->dateRangesToArray($this->options->enabledDates($this->getId())), $this);
    }

    /**
     * Convert an array of date ranges to an array of dates
     *
     * @param  array<array<string, string>>  $ranges  Array of date ranges like [['from' => '20230832', 'to' => '20230832'], ['from' => '20230832', 'to' => '20230832']]
     */
    public function dateRangesToArray(array $ranges): Collection
    {
        $dates = [];
        foreach ($ranges as $key => $range) {
            if (! $range['from']) { // No date / invalid date
                continue;
            } elseif (! $range['to']) { // Single date
                $dates[] = (new \DateTime($range['from']))->format('Y-m-d');

                continue;
            } else { // Date range
                $from = new \DateTime($range['from']);
                $to = new \DateTime($range['to']);
                $to->modify('+1 day'); // To include the last day

                $range = new \DatePeriod($from, new \DateInterval('P1D'), $to);
                foreach ($range as $date) {
                    $dates[] = $date->format('Y-m-d');
                }
            }
        }

        return collect($dates);
    }

    public function minDate(): \DateTime
    {
        $timeZone = new \DateTimeZone(wp_timezone_string());
        $minDate = new \DateTime('now', $timeZone);

        return $minDate->modify('+'.$this->buffer().' hours');
    }

    public function maxDate(): \DateTime
    {
        $timeZone = new \DateTimeZone(wp_timezone_string());
        $maxDate = new \DateTime('now', $timeZone);

        return $maxDate->modify('+'.$this->daysToDisplay().' days');
    }

    public function daysToDisplay(): int
    {
        return $this->options->daysToDisplay($this->getId()) ?? 120;
    }

    /**
     * Get buffer in hours
     *
     * @return int
     */
    public function buffer()
    {
        return intval($this->options->buffer($this->getId()));
    }

    /**
     * Test if certain day is disabled each week
     */
    public function isDayDisabled(\DateTime $date): bool
    {
        return $this->disabledDays()->contains($date->format('w'));
    }

    /**
     * Test if certain date is smaller than the min date
     */
    public function isDateEarlierThanMindate(\DateTime $date): bool
    {
        return $date->format('Ymd') < $this->minDate()->format('Ymd');
    }

    /**
     * Test if certain date with time is smaller than the min date with time
     */
    public function isDateTimeEarlierThanMindate(\DateTime $date): bool
    {
        return $date < $this->minDate();
    }

    /**
     * Test if certain date is bigger than the min date
     */
    public function isDateLaterThanMaxdate(\DateTime $date): bool
    {
        return $date->format('Ymd') > $this->maxDate()->format('Ymd');
    }

    /**
     * Test if date is in disabled dates
     */
    public function isDateDisabled(\DateTime $date): bool
    {
        return $this->disabledDates()->contains($date->format('Y-m-d'));
    }

    /**
     * Test if date is in enabled dates
     */
    public function isDateEnabled(\DateTime $date): bool
    {
        return $this->enabledDates()->contains($date->format('Y-m-d'));
    }

    /**
     * Test if date is invalid. Return false if date is valid, return error message if date is invalid.
     *
     * @return bool
     */
    public function isDateInvalid(\DateTime|bool $date, string $time = null)
    {
        if (! $date instanceof \DateTime) {
            return $this->missingDateMessage();
        }

        if ($this->isDateEnabled($date)) {
            return false;
        }

        return app(Pipeline::class)
            ->send($date)
            ->through([
                function ($date, $next) use ($time) {
                    if ($time) {
                        return $this->isDateTimeEarlierThanMindate($date) ? $this->invalidDateTimeMessage() : $next($date);
                    }
                    return $this->isDateEarlierThanMindate($date) ? $this->invalidDateMessage() : $next($date);
                },
                function ($date, $next) {
                    return $this->isDateLaterThanMaxdate($date) ? $this->invalidDateMessage() : $next($date);
                },
                function ($date, $next) {
                    return $this->isDayDisabled($date) ? $this->invalidDateMessage() : $next($date);
                },
                function ($date, $next) {
                    return $this->isDateDisabled($date) ? $this->invalidDateMessage() : $next($date);
                },
            ])
            ->then(function ($date) {
                return false;
            });
    }

    public function render($show = true)
    {
        $disabledDays = $this->disabledDays()
            ->unique()
            ->values()
            ->toArray();

        $disabledDates = $this->disabledDates()
            ->diff($this->enabledDates())
            ->filter(function ($date) {
                $dateTime = \DateTime::createFromFormat('Y-m-d', $date);

                return ! $this->isDateEarlierThanMindate($dateTime) && ! $this->isDateLaterThanMaxdate($dateTime);
            })
            ->unique()
            ->values()
            ->toArray();

        $enabledDates = $this->enabledDates()
            ->filter(function ($date) {
                $dateTime = \DateTime::createFromFormat('Y-m-d', $date);

                return ! $this->isDateEarlierThanMindate($dateTime) && ! $this->isDateLaterThanMaxdate($dateTime);
            })
            ->unique()
            ->values()
            ->toArray();

        $selectedDate = WC()->session->get('otomaties_woocommerce_datepicker_'.$this->getId().'_date');

        $datepickerArgs = apply_filters('otomaties_woocommerce_datepicker_datepicker_args', [
            'id' => $this->getId(),
            'locale' => substr(get_locale(), 0, 2),
            'minDate' => $this->minDate()->format('Y-m-d'),
            'maxDate' => $this->maxDate() ? $this->maxDate()->format('Y-m-d') : null,
            'disabledDays' => $disabledDays,
            'disabledDates' => $disabledDates,
            'enabledDates' => $enabledDates,
            'selectedDate' => $selectedDate,
        ]);

        echo view('Otomaties\Woocommerce\Datepicker::datepicker', [
            'datepickerArgs' => json_encode($datepickerArgs),
            'selectedDate' => $selectedDate,
            'datepickerLabel' => $this->datepickerLabel(),
            'timeslotLabel' => $this->timeslotLabel(),
            'show' => $show,
            'id' => $this->getId(),
            'useTimeslots' => $this->options->timeslots($this->getId()),
            'timeslots' => $selectedDate ? $this->timeslots(\DateTime::createFromFormat('Y-m-d', $selectedDate)) : [],
        ]);
    }
}
