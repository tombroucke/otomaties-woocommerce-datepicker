<?php

namespace Otomaties\WooCommerce\Datepicker\Emails;

class DatepickerChangedEmail extends \WC_Email
{
    public $content;

    public $heading;

    public function __construct()
    {
        $this->id = 'wc_datepicker_changed';
        $this->customer_email = true;
        $this->enabled = true;
        $this->title = __('Datepicker changed', 'otomaties-woocommerce-datepicker');
        $this->description = __('Datepicker changed notification emails are sent when a datepicker is changed.', 'otomaties-woocommerce-datepicker');
        $this->template_html = 'emails/customer-datepicker-changed.php';
        $this->template_plain = 'emails/plain/customer-datepicker-changed.php';
        $this->placeholders = [
            '{order_date}' => '',
            '{order_number}' => '',
        ];

        // Triggers.
        add_action('woocommerce_otom_datepicker_changed_notification', [$this, 'trigger']);

        // Call parent constructor.
        parent::__construct();

    }

    /**
     * Checks if this email is enabled and will be sent.
     *
     * @return bool
     */
    public function is_enabled()
    {
        return true;
    }

    /**
     * Get email subject.
     *
     * @since  3.1.0
     *
     * @return string
     */
    public function get_default_subject()
    {
        return __('Your order has been updated', 'otomaties-woocommerce-datepicker');
    }

    /**
     * Get email heading.
     *
     * @since  3.1.0
     *
     * @return string
     */
    public function get_default_heading()
    {
        return $this->heading ? $this->heading : __('Date has changed', 'otomaties-woocommerce-datepicker');
    }

    /**
     * Get content html.
     *
     * @return string
     */
    public function get_content_html()
    {
        return wc_get_template_html(
            $this->template_html,
            [
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'content' => $this->content,
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => $this,
            ]
        );
    }

    /**
     * Get content plain.
     *
     * @return string
     */
    public function get_content_plain()
    {
        return wc_get_template_html(
            $this->template_plain,
            [
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'content' => $this->content,
                'sent_to_admin' => false,
                'plain_text' => true,
                'email' => $this,
            ]
        );
    }

    /**
     * Trigger.
     *
     * @param  array  $args  Email arguments.
     */
    public function trigger($args)
    {
        $this->setup_locale();

        if (! empty($args)) {
            $defaults = [
                'order_id' => '',
                'content' => '',
                'heading' => null,
            ];

            $args = wp_parse_args($args, $defaults);

            $order_id = $args['order_id'];
            $content = $args['content'];

            $this->heading = $args['heading'];

            if ($order_id) {
                $this->object = wc_get_order($order_id);

                if ($this->object) {
                    $this->recipient = $this->object->get_billing_email();
                    $this->content = $content;
                    $this->placeholders['{order_date}'] = wc_format_datetime($this->object->get_date_created());
                    $this->placeholders['{order_number}'] = $this->object->get_order_number();
                }
            }
        }

        if ($this->is_enabled() && $this->get_recipient()) {
            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        }

        $this->restore_locale();
    }
}
