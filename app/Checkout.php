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

        $timeZone = new \DateTimeZone(wp_timezone_string());
        $dateTime = \DateTime::createFromFormat('Y-m-d', $_POST['otomaties-woocommerce-datepicker--date'], $timeZone);
        $invalidReason = Datepicker::isDateInvalid($dateTime);

        if ($invalidReason) {
            $errors->add('validation', $invalidReason);
        }
    }

    public function saveDateToSession($data)
    {
        parse_str($data, $postData);
        $date = $postData['otomaties-woocommerce-datepicker--date'] ?? null;
        $id = $postData['otomaties-woocommerce-datepicker--id'] ?? null;
        $this->saveDatepickerDataToSession($date, $id);
    }

    public function updateSession()
    {
        $date = $_POST['otomaties-woocommerce-datepicker--date'] ?? null;
        $id = $_POST['otomaties-woocommerce-datepicker--id'] ?? null;
        $this->saveDatepickerDataToSession($date, $id);
    }

    private function saveDatepickerDataToSession($date = null, $id = null)
    {
        if (! $date || ! $id) {
            return;
        }

        WC()->session->set('otomaties_woocommerce_datepicker_'.$id.'_date', wc_clean(wp_unslash($date)));
    }

    public function saveDatepickerDate($orderId, $data, $order)
    {
        $datepickerId = $_POST['otomaties-woocommerce-datepicker--id'] ?? null;
        $datepickerDate = $_POST['otomaties-woocommerce-datepicker--date'] ?? null;

        if ($datepickerId) {
            $datepicker = new \Otomaties\WooCommerce\Datepicker\Datepicker($datepickerId, Options::instance());
            $order->update_meta_data('otom_wc_datepicker_id', wc_clean(wp_unslash($datepickerId)));
            $order->update_meta_data('otom_wc_datepicker_label', $datepicker->administrationLabel());
        }

        if ($datepickerDate) {
            $order->update_meta_data('otom_wc_datepicker_date', wc_clean(wp_unslash($datepickerDate)));
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
