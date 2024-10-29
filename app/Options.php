<?php

namespace Otomaties\WooCommerce\Datepicker;

use StoutLogic\AcfBuilder\FieldsBuilder;

class Options
{
    public function findDatepickerByShippingMethod(string $shippingMethod)
    {
        global $wpdb;
        $datepickerIds = collect($this->activeDatepickers())->keys();

        $likeClauses = [];
        foreach ($datepickerIds as $datepickerId) {
            $likeClauses[] = "option_name = 'options_otomaties_wc_datepicker_".$datepickerId."_shipping_methods'";
        }

        $query = $wpdb->prepare(
            "
            SELECT *
            FROM {$wpdb->prefix}options
            WHERE (".implode(' OR ', $likeClauses).')
            AND option_value LIKE %s
            ',
            '%'.$shippingMethod.'%'
        );

        $records = collect($wpdb->get_results($query));

        if ($records->isEmpty()) {
            return false;
        }

        $option_name = $records->first()->option_name;

        preg_match('/options_otomaties_wc_datepicker_([0-9]+)_shipping_methods/', $option_name, $matches);

        return $matches[1] ?? null;
    }

    public function disabledDays(string $datepickerId)
    {
        return get_field('otomaties_wc_datepicker_'.$datepickerId.'_disabled_days', 'option');
    }

    public function timeslots(string $datepickerId)
    {
        return get_field('otomaties_wc_datepicker_'.$datepickerId.'_timeslots', 'option');
    }

    public function disabledDates(string $datepickerId)
    {
        return $this->getDateRepeaterField('otomaties_wc_datepicker_'.$datepickerId.'_disabled_dates');
    }

    public function enabledDates(string $datepickerId)
    {
        return $this->getDateRepeaterField('otomaties_wc_datepicker_'.$datepickerId.'_enabled_dates');
    }

    public function datepickerLabel(string $datepickerId)
    {
        return get_field('otomaties_wc_datepicker_'.$datepickerId.'_datepicker_label', 'option');
    }

    public function timeslotLabel(string $datepickerId)
    {
        return get_field('otomaties_wc_datepicker_'.$datepickerId.'_timeslot_label', 'option');
    }

    public function missingDateMessage(string $datepickerId)
    {
        return get_field('otomaties_wc_datepicker_'.$datepickerId.'_validation_missing', 'option');
    }

    public function invalidDateMessage(string $datepickerId)
    {
        return get_field('otomaties_wc_datepicker_'.$datepickerId.'_validation_incorrect', 'option');
    }

    public function administrationLabel(string $datepickerId)
    {
        return get_field('otomaties_wc_datepicker_'.$datepickerId.'_administration_label', 'option');
    }

    public function buffer(string $datepickerId)
    {
        return get_field('otomaties_wc_datepicker_'.$datepickerId.'_buffer', 'option');
    }

    public function daysToDisplay(string $datepickerId)
    {
        return get_field('otomaties_wc_datepicker_'.$datepickerId.'_days_to_display', 'option');
    }

    private function getDateRepeaterField(string $fieldName)
    {
        $repeater = [];
        $repeaterCount = get_option('options_'.$fieldName);
        for ($i = 0; $i < $repeaterCount; $i++) {
            $from = get_option('options_'.$fieldName.'_'.$i.'_from_date');
            $to = get_option('options_'.$fieldName.'_'.$i.'_to_date');
            $repeater[] = [
                'from' => $from,
                'to' => $to,
            ];
        }
        $repeater = collect($repeater)->map(function ($date) {
            return [
                'from' => $date['from'] ? substr($date['from'], 0, 4).substr($date['from'], 4, 2).substr($date['from'], 6, 2) : null,
                'to' => $date['to'] ? substr($date['to'], 0, 4).substr($date['to'], 4, 2).substr($date['to'], 6, 2) : null,
            ];
        });

        return $repeater->toArray();
    }

    public function addOptionsPage()
    {
        acf_add_options_page(
            [
                'page_title' => __('Datepicker', 'otomaties-woocommerce-datepicker'),
                'menu_title' => __('Datepicker', 'otomaties-woocommerce-datepicker'),
                'menu_slug' => 'otomaties-woocommerce-datepicker-settings',
                'icon_url' => 'dashicons-calendar',
                'capability' => 'manage_woocommerce',
                'redirect' => false,
            ]
        );

        return $this;
    }

    public function cleanUpInactiveDatepickers()
    {
        $currentScreen = get_current_screen();
        if ($currentScreen && strpos($currentScreen->id, 'otomaties-woocommerce-datepicker-settings') == true) {

            $activeDatepickers = collect($this->activeDatepickers())->keys();
            $activeDatepickerNames = $activeDatepickers
                ->map(function ($activeDatepickerId) {
                    return "option_name NOT LIKE '%options_otomaties_wc_datepicker_".$activeDatepickerId."_%'";
                });

            global $wpdb;

            $query = "
                DELETE FROM {$wpdb->prefix}options
                WHERE (option_name LIKE '%options_otomaties_wc_datepicker_%_%')
                AND (".implode(' AND ', $activeDatepickerNames->toArray()).")
                AND option_name NOT LIKE '%options_otomaties_wc_datepicker_datepickers%'
            ";
            $wpdb->query($query);
        }
    }

    public function addOptionsFields()
    {
        $datepickers = new FieldsBuilder('otomaties-woocommerce-datepicker-datepickers', [
            'title' => __('Datepickers', 'otomaties-woocommerce-datepicker'),
        ]);

        $datepickers
            ->addRepeater('otomaties_wc_datepicker_datepickers', [
                'label' => __('Datepickers', 'otomaties-woocommerce-datepicker'),
                'layout' => 'block',
                'button_label' => __('Add datepicker', 'otomaties-woocommerce-datepicker'),
            ])
            ->addText('label', [
                'label' => __('Label', 'otomaties-woocommerce-datepicker'),
                'instructions' => __('For admin eyes only. This will not be displayed to clients.', 'otomaties-woocommerce-datepicker'),
            ])
            ->endRepeater();

        $datepickers->setLocation('options_page', '==', 'otomaties-woocommerce-datepicker-settings');
        acf_add_local_field_group($datepickers->build());

        $datepickersDetails = new FieldsBuilder('otomaties-woocommerce-datepicker-settings', [
            'title' => __('Datepickers details', 'otomaties-woocommerce-datepicker'),
        ]);

        if (count($this->activeDatepickers()) > 0) {

            $wcShippingMethods = WC()->shipping->get_shipping_methods();

            foreach ($this->activeDatepickers() as $key => $label) {
                $datepickersDetails
                    ->addTab('otomaties_wc_datepicker_'.$key, [
                        'label' => $label,
                    ])
                    ->addSelect('otomaties_wc_datepicker_'.$key.'_shipping_methods', [
                        'label' => __('Shipping method', 'otomaties-woocommerce-datepicker'),
                        'choices' => array_map(function ($shippingMethod) {
                            return $shippingMethod->get_method_title();
                        }, $wcShippingMethods),
                        'multiple' => 1,
                    ])
                    ->addText('otomaties_wc_datepicker_'.$key.'_administration_label', [
                        'label' => __('Administration label', 'otomaties-woocommerce-datepicker'),
                        'instructions' => __('This will displayed in on the thankyou-page, e-mails and in the WooCommere backend', 'otomaties-woocommerce-datepicker'),
                        'default_value' => sprintf(__('%s date', 'otomaties-woocommerce-datepicker'), $label),
                    ])
                    ->addText('otomaties_wc_datepicker_'.$key.'_datepicker_label', [
                        'label' => __('Datepicker label', 'otomaties-woocommerce-datepicker'),
                        'instructions' => __('This will displayed right above the datepicker', 'otomaties-woocommerce-datepicker'),
                        'default_value' => sprintf(__('Choose a %s date', 'otomaties-woocommerce-datepicker'), strtolower($label)),
                    ])
                    ->addText('otomaties_wc_datepicker_'.$key.'_validation_missing', [
                        'label' => __('Missing date error message', 'otomaties-woocommerce-datepicker'),
                        'instructions' => __('When a client doesn\'t provide a date, this error message will be dipslayed.', 'otomaties-woocommerce-datepicker'),
                        'default_value' => sprintf(__('%s date is missing', 'otomaties-woocommerce-datepicker'), $label),
                    ])
                    ->addText('otomaties_wc_datepicker_'.$key.'_validation_incorrect', [
                        'label' => __('Incorrect date error message', 'otomaties-woocommerce-datepicker'),
                        'instructions' => __('When a client provides an invalid date, this error message will be dipslayed.', 'otomaties-woocommerce-datepicker'),
                        'default_value' => sprintf(__('%s date is invalid', 'otomaties-woocommerce-datepicker'), $label),
                    ])
                    ->addNumber('otomaties_wc_datepicker_'.$key.'_buffer', [
                        'label' => __('Buffer', 'otomaties-woocommerce-datepicker'),
                        'instructions' => __('The number of hours that should be added to the datepicker\'s min date', 'otomaties-woocommerce-datepicker'),
                        'default_value' => 0,
                    ])
                    ->addNumber('otomaties_wc_datepicker_'.$key.'_days_to_display', [
                        'label' => __('Days to display', 'otomaties-woocommerce-datepicker'),
                        'instructions' => __('The number of days that should be displayed in the datepicker', 'otomaties-woocommerce-datepicker'),
                        'default_value' => 120,
                    ])
                    ->addSelect('otomaties_wc_datepicker_'.$key.'_disabled_days', [
                        'label' => __('Disabled days', 'otomaties-woocommerce-datepicker'),
                        'multiple' => 1,
                        'ui' => 1,
                        'choices' => [
                            'monday' => __('Monday', 'otomaties-woocommerce-datepicker'),
                            'tuesday' => __('Tuesday', 'otomaties-woocommerce-datepicker'),
                            'wednesday' => __('Wednesday', 'otomaties-woocommerce-datepicker'),
                            'thursday' => __('Thursday', 'otomaties-woocommerce-datepicker'),
                            'friday' => __('Friday', 'otomaties-woocommerce-datepicker'),
                            'saturday' => __('Saturday', 'otomaties-woocommerce-datepicker'),
                            'sunday' => __('Sunday', 'otomaties-woocommerce-datepicker'),
                        ],
                    ])
                    ->addRepeater('otomaties_wc_datepicker_'.$key.'_disabled_dates', [
                        'label' => __('Disabled dates', 'otomaties-woocommerce-datepicker'),
                        'layout' => 'table',
                        'button_label' => __('Add date', 'otomaties-woocommerce-datepicker'),
                    ])
                    ->addDatepicker('from_date', [
                        'label' => __('Date from', 'otomaties-woocommerce-datepicker'),
                        'return_format' => 'Ymd',
                        'required' => true,
                    ])
                    ->addDatepicker('to_date', [
                        'label' => __('Date to', 'otomaties-woocommerce-datepicker'),
                        'return_format' => 'Ymd',
                        'instructions' => __('Leave empty if you want to disable only one date', 'otomaties-woocommerce-datepicker'),
                        'required' => false,
                    ])
                    ->endRepeater()
                    ->addRepeater('otomaties_wc_datepicker_'.$key.'_enabled_dates', [
                        'label' => __('Enabled dates', 'otomaties-woocommerce-datepicker'),
                        'layout' => 'table',
                        'button_label' => __('Add date', 'otomaties-woocommerce-datepicker'),
                    ])
                    ->addDatepicker('from_date', [
                        'label' => __('Date from', 'otomaties-woocommerce-datepicker'),
                        'return_format' => 'Ymd',
                        'required' => true,
                    ])
                    ->addDatepicker('to_date', [
                        'label' => __('Date to', 'otomaties-woocommerce-datepicker'),
                        'return_format' => 'Ymd',
                        'instructions' => __('Leave empty if you want to disable only one date', 'otomaties-woocommerce-datepicker'),
                        'required' => false,
                    ])
                    ->endRepeater()
                    ->addText('otomaties_wc_datepicker_'.$key.'_timeslot_label', [
                        'label' => __('Timeslot label', 'otomaties-woocommerce-datepicker'),
                        'instructions' => __('This will be displayed right above the timeslot picker', 'otomaties-woocommerce-datepicker'),
                        'default_value' => sprintf(__('Choose a %s timeslot', 'otomaties-woocommerce-datepicker'), strtolower($label)),
                    ])
                    ->addRepeater('otomaties_wc_datepicker_'.$key.'_timeslots', [
                        'label' => __('Timeslots', 'otomaties-woocommerce-datepicker'),
                        'layout' => 'table',
                        'button_label' => __('Add timeslot', 'otomaties-woocommerce-datepicker'),
                    ])
                    ->addSelect('days', [
                        'label' => __('Days', 'otomaties-woocommerce-datepicker'),
                        'choices' => [
                            'monday' => __('Monday', 'otomaties-woocommerce-datepicker'),
                            'tuesday' => __('Tuesday', 'otomaties-woocommerce-datepicker'),
                            'wednesday' => __('Wednesday', 'otomaties-woocommerce-datepicker'),
                            'thursday' => __('Thursday', 'otomaties-woocommerce-datepicker'),
                            'friday' => __('Friday', 'otomaties-woocommerce-datepicker'),
                            'saturday' => __('Saturday', 'otomaties-woocommerce-datepicker'),
                            'sunday' => __('Sunday', 'otomaties-woocommerce-datepicker'),
                        ],
                        'multiple' => 1,
                    ])
                    ->addRepeater('slots', [
                        'label' => __('Slots', 'otomaties-woocommerce-datepicker'),
                    ])
                    ->addTimePicker('time_from', [
                        'label' => __('Time from', 'otomaties-woocommerce-datepicker'),
                        'display_format' => 'H:i',
                        'return_format' => 'H:i',
                        'required' => true,
                    ])
                    ->addTimePicker('time_to', [
                        'label' => __('Time to', 'otomaties-woocommerce-datepicker'),
                        'display_format' => 'H:i',
                        'return_format' => 'H:i',
                        'required' => true,
                    ])
                    ->endRepeater()
                    ->endRepeater();
            }

            $datepickersDetails->setLocation('options_page', '==', 'otomaties-woocommerce-datepicker-settings');
            acf_add_local_field_group($datepickersDetails->build());
        }
    }

    private function activeDatepickers()
    {
        global $wpdb;
        $datepickers = $wpdb->get_results("SELECT option_id, option_value FROM $wpdb->options WHERE option_name LIKE 'options_otomaties_wc_datepicker_datepickers_%_label'");

        return collect($datepickers)
            ->pluck('option_value', 'option_id')
            ->toArray();
    }
}
