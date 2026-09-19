<?php

if (!defined('ABSPATH')) {
    exit;
}

class OTSG_Single_Event
{
    public static function register(): void
    {
        add_filter('the_content', [self::class, 'append_details']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_styles']);
    }

    public static function enqueue_styles(): void
    {
        if (is_singular('consultation_event')) {
            wp_enqueue_style(
                'otsg-single-event',
                OTSG_PLUGIN_URL . 'assets/single-event.css',
                [],
                '0.2.0'
            );
        }
    }

    public static function append_details(string $content): string
    {
        if (!is_singular('consultation_event') || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        $post_id          = get_the_ID();
        $start_at         = get_post_meta($post_id, 'start_at', true);
        $time_note        = get_post_meta($post_id, 'time_note', true);
        $location_name    = get_post_meta($post_id, 'location_name', true);
        $location_address = get_post_meta($post_id, 'location_address', true);
        $legacy_location  = get_post_meta($post_id, 'location', true);
        $status            = get_post_meta($post_id, 'status', true) ?: 'open';
        $reservation_url  = get_post_meta($post_id, 'reservation_url', true);

        if (!$location_name && !$location_address) {
            $location_name = $legacy_location;
        }

        $fields = [];
        $date   = OTSG_Date::parse($start_at);

        if ($date) {
            $fields[] = '<dt>' . esc_html__('Event Date', 'ototsugu-connector') . '</dt><dd>' . esc_html(OTSG_Date::full($date)) . '</dd>';
        }
        if ($time_note) {
            $fields[] = '<dt>' . esc_html__('Time Note', 'ototsugu-connector') . '</dt><dd>' . esc_html($time_note) . '</dd>';
        }
        if ($location_name) {
            $fields[] = '<dt>' . esc_html__('Venue Name', 'ototsugu-connector') . '</dt><dd>' . esc_html($location_name) . '</dd>';
        }
        if ($location_address) {
            $fields[] = '<dt>' . esc_html__('Address', 'ototsugu-connector') . '</dt><dd>' . esc_html($location_address) . '</dd>';
        }

        $status_labels = [
            'open'   => __('Open', 'ototsugu-connector'),
            'full'   => __('Full', 'ototsugu-connector'),
            'closed' => __('Closed', 'ototsugu-connector'),
        ];
        $status_label = $status_labels[$status] ?? $status;
        $fields[]      = '<dt>' . esc_html__('Status', 'ototsugu-connector') . '</dt><dd>' . esc_html($status_label) . '</dd>';

        $details = '<section class="otsg-event-details">'
            . '<h2>' . esc_html__('Consultation Event Details', 'ototsugu-connector') . '</h2>'
            . '<dl>' . implode('', $fields) . '</dl>';

        if ($reservation_url && $status === 'open') {
            $details .= '<p class="otsg-event-details__reservation">'
                . '<a href="' . esc_url($reservation_url) . '" target="_blank" rel="noopener">' . esc_html__('Make a reservation', 'ototsugu-connector') . '</a>'
                . '</p>';
        }

        $details .= '</section>';

        if (OTSG_Settings::get_detail_layout() === 'two-pane') {
            return '<div class="otsg-event-details-layout otsg-event-details-layout--two-pane">'
                . '<div class="otsg-event-details-layout__main">' . $content . '</div>'
                . '<div class="otsg-event-details-layout__side">' . str_replace(
                    'class="otsg-event-details"',
                    'class="otsg-event-details otsg-event-details--card"',
                    $details
                ) . '</div>'
                . '</div>';
        }

        return $details . $content;
    }
}
