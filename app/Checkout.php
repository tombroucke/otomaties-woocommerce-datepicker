<?php

namespace Otomaties\WooCommerce\Datepicker;

use Illuminate\Support\Str;
use Otomaties\WooCommerce\Datepicker\Facades\Datepicker;
use Otomaties\WooCommerce\Datepicker\Facades\Options;

class Checkout
{
    public function validate($fields, $errors)
    {
        if (! isset($_POST['otomaties-woocommerce-datepicker--date'])) {
            return;
        }

        $chosenDate = sanitize_text_field($_POST['otomaties-woocommerce-datepicker--date'] ?? null);
        $chosenTime = sanitize_text_field($_POST['otomaties-woocommerce-datepicker--timeslot'] ?? null);

        $timeZone = new \DateTimeZone(wp_timezone_string());
        $dateTime = \DateTime::createFromFormat('Y-m-d', $chosenDate, $timeZone);

        if (! $dateTime) {
            $errors->add('validation', __('Please select a valid date.', 'otomaties-woocommerce-datepicker'));

            return;
        }

        if ($chosenTime) {
            $timeFrom = explode(' - ', $chosenTime)[0];
            [$hours, $minutes] = explode(':', $timeFrom);
            $dateTime
                ->setTime($hours, $minutes)
                ->sub(new \DateInterval('PT1S'));
        }

        $invalidReason = Datepicker::isDateInvalid($dateTime, $chosenTime);

        if ($invalidReason) {
            $errors->add('validation', $invalidReason);
        }

        // Timeslot validation
        $timeslotDate = sanitize_text_field($_POST['otomaties-woocommerce-datepicker--timeslot-date'] ?? null);
        $timeslot = sanitize_text_field($_POST['otomaties-woocommerce-datepicker--timeslot'] ?? null);

        if (! isset($_POST['otomaties-woocommerce-datepicker--timeslot-date'])) {
            return;
        }

        if (! $timeslot) {
            $errors->add('validation', __('Please select a timeslot', 'otomaties-woocommerce-datepicker'));
        }

        if ($timeslot && $timeslotDate !== $chosenDate) {
            $errors->add('validation', __('Invalid timeslot', 'otomaties-woocommerce-datepicker'));
        }

        $datepickerId = filter_var($_POST['otomaties-woocommerce-datepicker--id'] ?? null, FILTER_VALIDATE_INT);

        if (! $datepickerId) {
            $errors->add('validation', __('Invalid datepicker', 'otomaties-woocommerce-datepicker'));
        }
    }

    public function saveDateToSession($data)
    {
        parse_str($data, $postData);
        $date = sanitize_text_field($postData['otomaties-woocommerce-datepicker--date'] ?? null);
        $id = sanitize_text_field($postData['otomaties-woocommerce-datepicker--id'] ?? null);
        $timeSlot = sanitize_text_field($postData['otomaties-woocommerce-datepicker--timeslot'] ?? null);

        $this->saveDatepickerDataToSession($date, $id, $timeSlot);
    }

    public function updateSession()
    {
        $date = sanitize_text_field($_POST['otomaties-woocommerce-datepicker--date'] ?? null);
        $id = sanitize_text_field($_POST['otomaties-woocommerce-datepicker--id'] ?? null);
        $timeSlot = sanitize_text_field($_POST['otomaties-woocommerce-datepicker--timeslot'] ?? null);

        $this->saveDatepickerDataToSession($date, $id, $timeSlot);
    }

    private function saveDatepickerDataToSession($date, $id, $timeSlot = null)
    {
        if (! $date || ! $id) {
            return;
        }

        WC()->session->set('otomaties_woocommerce_datepicker_'.$id.'_date', wc_clean(wp_unslash($date)));

        if ($timeSlot) {
            WC()->session->set('otomaties_woocommerce_datepicker_'.$id.'_timeslot', wc_clean(wp_unslash($timeSlot)));
        }
    }

    public function saveDatepickerDate($orderId, $data, $order)
    {
        $datepickerId = sanitize_text_field($_POST['otomaties-woocommerce-datepicker--id'] ?? null);
        $datepickerDate = sanitize_text_field($_POST['otomaties-woocommerce-datepicker--date'] ?? null);
        $timeslot = sanitize_text_field($_POST['otomaties-woocommerce-datepicker--timeslot'] ?? null);

        if ($datepickerId) {
            $datepicker = new \Otomaties\WooCommerce\Datepicker\Datepicker($datepickerId, Options::instance());
            $order->update_meta_data('otom_wc_datepicker_id', wc_clean(wp_unslash($datepickerId)));
            $order->update_meta_data('otom_wc_datepicker_label', $datepicker->administrationLabel());
        }

        if ($datepickerDate) {
            $order->update_meta_data('otom_wc_datepicker_date', wc_clean(wp_unslash($datepickerDate)));
        }

        if ($timeslot) {
            $order->update_meta_data('otom_wc_datepicker_timeslot', wc_clean(wp_unslash($timeslot)));
        }

        $order->save();

        $sessionData = collect(WC()->session->get_session_data());
        $sessionData
            ->filter(function ($value, $key) {
                return Str::startsWith($key, 'otomaties_woocommerce_datepicker');
            })
            ->keys()
            ->each(function ($key) {
                WC()->session->__unset($key);
            });
    }
}
