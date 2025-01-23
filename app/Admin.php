<?php

namespace Otomaties\WooCommerce\Datepicker;

use Otomaties\WooCommerce\Datepicker\Emails\DatepickerChangedEmail;

class Admin
{
    public function addDatepickerToShippingFields($fields, $order, $context)
    {

        $fields['otom_wc_datepicker_date'] = [
            'label' => __('Datepicker', 'otomaties-woocommerce-datepicker'),
            'show' => false,
            'type' => 'date',
            'value' => $this->getValue('date', $order),
        ];

        $fields['otom_wc_datepicker_timeslot'] = [
            'label' => __('Timeslot', 'otomaties-woocommerce-datepicker'),
            'show' => false,
            'value' => $this->getValue('timeslot', $order),
        ];

        $fields['otom_wc_datepicker_send_email'] = [
            'label' => __('Send notification', 'otomaties-woocommerce-datepicker'),
            'show' => true,
            'type' => 'checkbox',
            'description' => __('Send a change notification email to the customer', 'otomaties-woocommerce-datepicker'),
            'value' => 'yes',
        ];

        return $fields;
    }

    public function getValue($key, $order)
    {
        $value = $order->get_meta('otom_wc_datepicker_'.$key);

        return $value ? $value : null;
    }

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
            '<div class="address"><strong>%s</strong><p>%s</p></div>',
            $datepickerLabel,
            $timeslot ? $formattedDate.'<br> '.$timeslot : $formattedDate
        );
    }

    public function saveDatepickerShippingFields($orderid)
    {
        $order = wc_get_order($orderid);

        $sendEmail = sanitize_text_field($_POST['_shipping_otom_wc_datepicker_send_email'] ?? 'no');

        $currentDate = $order->get_meta('otom_wc_datepicker_date');
        $currentTimeslot = $order->get_meta('otom_wc_datepicker_timeslot');

        $newDate = isset($_POST['_shipping_otom_wc_datepicker_date']) ? sanitize_text_field($_POST['_shipping_otom_wc_datepicker_date']) : null;
        $newTimeslot = isset($_POST['_shipping_otom_wc_datepicker_timeslot']) ? sanitize_text_field($_POST['_shipping_otom_wc_datepicker_timeslot']) : null;

        $datepickerLabel = $order->get_meta('otom_wc_datepicker_label');

        if ($newDate && $newDate !== $currentDate) {
            $order->update_meta_data('otom_wc_datepicker_date', $newDate);
        }

        if ($newTimeslot && $newTimeslot !== $currentTimeslot) {
            $order->update_meta_data('otom_wc_datepicker_timeslot', $newTimeslot);
        }

        if (($currentDate !== $newDate || $currentTimeslot !== $newTimeslot) && $sendEmail === 'yes') {
            $newDateTime = \DateTime::createFromFormat('Y-m-d', $newDate);
            $heading = sprintf(
                __('%s has been changed', 'otomaties-woocommerce-datepicker'),
                $datepickerLabel
            );

            $content = sprintf(
                __('%s has been changed to %s at %s.', 'otomaties-woocommerce-datepicker'),
                $datepickerLabel,
                date_i18n(get_option('date_format'), $newDateTime->getTimestamp()),
                $newTimeslot
            );

            if (! $newTimeslot) {
                $content = sprintf(
                    __('%s has been changed to %s.', 'otomaties-woocommerce-datepicker'),
                    $datepickerLabel,
                    date_i18n(get_option('date_format'), $newDateTime->getTimestamp())
                );
            }

            WC()->mailer()->get_emails()['WC_Datepicker_Changed_Email']->trigger([
                'order_id' => $orderid,
                'content' => $content,
                'heading' => $heading,
            ]);

        }
    }

    public function addDatepickerChangedEmail($emailClasses)
    {
        $emailClasses['WC_Datepicker_Changed_Email'] = new DatepickerChangedEmail;

        return $emailClasses;
    }

    public function datepickerChangeEmailTemplate($template, $template_name, $args, $template_path)
    {
        if ($template_name === 'emails/customer-datepicker-changed.php') {
            $template = view('Otomaties\Woocommerce\Datepicker::woocommerce.emails.customer-datepicker-changed')->getPath();
        } elseif ($template_name === 'emails/plain/customer-datepicker-changed.php') {
            $template = view('Otomaties\Woocommerce\Datepicker::woocommerce.emails.plain.customer-datepicker-changed')->getPath();
        }

        return $template;
    }
}
