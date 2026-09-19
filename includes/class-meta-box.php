<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ACFを使わず、register_post_meta + 素のメタボックスでフィールドを実装する。
 * フィールドが単純(テキスト・日付・URL・選択肢のみ)かつ、
 * 他法人への配布物として依存関係を増やしたくないという判断による(CLAUDE.md参照)。
 */
class OTSG_Meta_Box
{
    private const FIELDS = ['start_at', 'time_note', 'location_name', 'location_address', 'status', 'reservation_url'];
    private const NONCE_ACTION = 'otsg_save_meta_box';
    private const NONCE_NAME = 'otsg_meta_box_nonce';

    public static function register(): void
    {
        add_action('add_meta_boxes', [self::class, 'add_box']);
        add_action('save_post_consultation_event', [self::class, 'save']);
    }

    public static function add_box(): void
    {
        add_meta_box(
            'otsg_event_details',
            __('Consultation Event Details', 'ototsugu-connector'),
            [self::class, 'render'],
            'consultation_event',
            'normal',
            'high'
        );
    }

    public static function render(WP_Post $post): void
    {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $start_at        = get_post_meta($post->ID, 'start_at', true);
        $time_note       = get_post_meta($post->ID, 'time_note', true);
        $location_name    = get_post_meta($post->ID, 'location_name', true) ?: get_post_meta($post->ID, 'location', true);
        $location_address = get_post_meta($post->ID, 'location_address', true);
        $status          = get_post_meta($post->ID, 'status', true) ?: 'open';
        $reservation_url = get_post_meta($post->ID, 'reservation_url', true);
        ?>
        <p>
                 <label for="otsg_start_at"><?php esc_html_e('Date & Sort Order', 'ototsugu-connector'); ?></label><br>
            <input type="datetime-local" id="otsg_start_at" name="otsg_start_at"
                   value="<?php echo esc_attr($start_at); ?>">
                 <span class="description"><?php esc_html_e('When there are several sessions on the same day, the time of this date and time is used to sort them in the list. Enter the time slots to display in "Time Note" below.', 'ototsugu-connector'); ?></span>
        </p>
        <p>
            <label for="otsg_time_note"><?php esc_html_e('Time Note (displayed, free text)', 'ototsugu-connector'); ?></label><br>
            <input type="text" id="otsg_time_note" name="otsg_time_note" class="widefat"
                   placeholder="<?php echo esc_attr__('e.g. 10:00 / 13:00 / 15:00 (45 minutes each)', 'ototsugu-connector'); ?>"
                   value="<?php echo esc_attr($time_note); ?>">
            <span class="description"><?php esc_html_e('Even when there are multiple time slots, they are not managed strictly; describe them here as display text.', 'ototsugu-connector'); ?></span>
        </p>
        <p>
            <label for="otsg_location_name"><?php esc_html_e('Venue Name', 'ototsugu-connector'); ?></label><br>
            <input type="text" id="otsg_location_name" name="otsg_location_name" class="widefat"
                   placeholder="<?php echo esc_attr__('e.g. City Community Center', 'ototsugu-connector'); ?>"
                   value="<?php echo esc_attr($location_name); ?>">
        </p>
        <p>
            <label for="otsg_location_address"><?php esc_html_e('Address', 'ototsugu-connector'); ?></label><br>
            <input type="text" id="otsg_location_address" name="otsg_location_address" class="widefat"
                   placeholder="<?php echo esc_attr__('e.g. 1-2-3 Example Street, Example City', 'ototsugu-connector'); ?>"
                   value="<?php echo esc_attr($location_address); ?>">
        </p>
        <p>
            <label for="otsg_status"><?php esc_html_e('Status', 'ototsugu-connector'); ?></label><br>
            <select id="otsg_status" name="otsg_status">
                <option value="open" <?php selected($status, 'open'); ?>><?php esc_html_e('Open', 'ototsugu-connector'); ?></option>
                <option value="full" <?php selected($status, 'full'); ?>><?php esc_html_e('Full', 'ototsugu-connector'); ?></option>
                <option value="closed" <?php selected($status, 'closed'); ?>><?php esc_html_e('Closed', 'ototsugu-connector'); ?></option>
            </select>
        </p>
        <p>
            <label for="otsg_reservation_url"><?php esc_html_e('Reservation URL (external system)', 'ototsugu-connector'); ?></label><br>
            <input type="url" id="otsg_reservation_url" name="otsg_reservation_url" class="widefat"
                   value="<?php echo esc_attr($reservation_url); ?>">
        </p>
        <?php
    }

    public static function save(int $post_id): void
    {
        if (
            !isset($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)
        ) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $map = [
            'otsg_start_at'        => 'start_at',
            'otsg_time_note'       => 'time_note',
            'otsg_location_name'   => 'location_name',
            'otsg_location_address' => 'location_address',
            'otsg_status'          => 'status',
            'otsg_reservation_url' => 'reservation_url',
        ];

        foreach ($map as $field_name => $meta_key) {
            if (!isset($_POST[$field_name])) {
                continue;
            }
            $value = $meta_key === 'reservation_url'
                ? esc_url_raw(wp_unslash($_POST[$field_name]))
                : sanitize_text_field(wp_unslash($_POST[$field_name]));

            update_post_meta($post_id, $meta_key, $value);
        }
    }
}
