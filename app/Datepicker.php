<?php

namespace Otomaties\WooCommerce\Datepicker;

use Illuminate\Support\Str;
use Otomaties\WooCommerce\Datepicker\Options;
use Illuminate\Pipeline\Pipeline;

class Datepicker
{
    public function __construct(protected int $id, protected Options $options)
    {
    }

    public function getId()
    {
        return $this->id;
    }

    public function disabledDays()
    {
        $days = collect([
            'sunday',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
        ]);

        $disabledDays = collect($this->options->disabledDays($this->getId()));

        return $disabledDays->map(function ($day) use ($days) {
            return $days->search($day);
        })->toArray();
    }

    public function administrationLabel()
    {
        return $this->options->administrationLabel($this->getId());
    }

    public function datepickerLabel()
    {
        return $this->options->datepickerLabel($this->getId());
    }

    public function disabledDates()
    {
        return $this->options->disabledDates($this->getId());
    }

    public function enabledDates()
    {
        return $this->options->enabledDates($this->getId());
    }

    public function minDate() : \DateTime
    {
        $timeZone = new \DateTimeZone(wp_timezone_string());
        $minDate = new \DateTime('now', $timeZone);
        return $minDate->modify('+' . $this->buffer() . ' hours');
    }

    /**
     * Get buffer in hours
     *
     * @return int
     */
    public function buffer()
    {
        return $this->options->buffer($this->getId()) ?? 0;
    }

    public function isDateInvalid(\DateTime|bool $date) 
    {
        if (! $date instanceof \DateTime) {
            return __('Please select a delivery date.', 'otomaties-woocommerce-datepicker');
        }

        foreach ($this->enabledDates() as $enabledPeriod) {
            if ($this->dateIsInRange($date, $enabledPeriod['from'], $enabledPeriod['to'])) {
                return false;
            }
        }

        return app(Pipeline::class)
            ->send($date)
            ->through([
                function ($date, $next) {
                    if (in_array($date->format('w'), $this->disabledDays())) {
                        return __('Please select a valid delivery date.', 'otomaties-woocommerce-datepicker');
                    }
                    return $next($date);
                },
                function ($date, $next) {
                    if ($date->format('Ymd') < $this->minDate()->format('Ymd')) {
                        return __('Please select a valid delivery date.', 'otomaties-woocommerce-datepicker');
                    }
                    return $next($date);
                },
                function ($date, $next) {
                    foreach ($this->disabledDates() as $disabledPeriod) {
                        if ($this->dateIsInRange($date, $disabledPeriod['from'], $disabledPeriod['to'])) {
                            return __('Please select a valid delivery date.', 'otomaties-woocommerce-datepicker');
                        }
                    }
                    return $next($date);
                },
                function ($date, $next) {
                    if ($invalidReason = apply_filters('otomaties_woocommerce_datepicker_is_date_invalid', false, $date, $this->getId())) {
                        return $invalidReason;
                    }
                    return $next($date);
                },
            ])
            ->then(function ($date) {
                return false;
            });
    }

    private function dateIsInRange($date, $from, $to) {
        if (
            ( $to && $date->format('Ymd') >= $from && $date->format('Ymd') <= $to )
            || ( ! $to && $date->format('Ymd') == $from )
        ) {
            return true;
        }
        return false;
    }

    public function enabledDatesFor($month, $year) {
        $days = collect(range(1, cal_days_in_month(CAL_GREGORIAN, $month, $year)));
        $days = $days->map(function ($day) use ($month, $year) {
            return new \DateTime($year . '-' . $month . '-' . $day);
        });
        $days = $days->filter(function ($day) {
            return !$this->isDateInvalid($day);
        });
        $days = $days->map(function ($day) {
            return $day->format('Y-m-d');
        });
        return $days->values()->toArray();
    }
    
    public function render($show = true)
    {
        echo view('Otomaties\Woocommerce\Datepicker::datepicker', [
            'datepickerArgs' => json_encode([
                'id' => $this->getId(),
                'minDate' => $this->minDate()->format('Y-m-d'),
                'disabledDays' => $this->disabledDays(),
                'disabledDates' => $this->disabledDates(),
                'enabledDates' => $this->enabledDates(),
            ]),
            'label' => $this->datepickerLabel(),
            'show' => $show,
        ]);
    }
}
