<?php

namespace Otomaties\WooCommerce\Datepicker;

class Admin
{
    public function addDateToOrderDetails($order)
    {
        $datepickerLabel = $order->get_meta('otom_wc_datepicker_label');
        $datepickerDate = $order->get_meta('otom_wc_datepicker_date');
        $timeslot = $order->get_meta('otom_wc_datepicker_timeslot');

        if ($datepickerDate === '') {
            return;
        }

        $datepickerLabel = $datepickerLabel === '' ? __('Datepicker', 'otomaties-woocommerce-datepicker') : $datepickerLabel;

        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $datepickerDate.' 12:00:00', new \DateTimeZone(wp_timezone_string()));
        $formattedDate = date_i18n(get_option('date_format'), $dateTime->getTimestamp());
        printf(
            '<p><strong>%s:</strong><br>%s</p>',
            $datepickerLabel,
            $timeslot ? $formattedDate.'<br> '.$timeslot : $formattedDate
        );
    }
}
