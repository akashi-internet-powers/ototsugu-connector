<?php

if (!defined('ABSPATH')) {
    exit;
}

class OTSG_Post_Type
{
    public static function register(): void
    {
        add_filter('manage_consultation_event_posts_columns', [self::class, 'columns']);
        add_action('manage_consultation_event_posts_custom_column', [self::class, 'render_column'], 10, 2);

        register_post_type('consultation_event', [
            'label'        => __('Consultation Events', 'ototsugu-connector'),
            'public'       => true,
            'show_in_rest' => true, // consultation-app からの取得はこのフラグが前提
            'supports'     => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'menu_icon'    => 'dashicons-calendar-alt',
        ]);

        // カスタムフィールド(開催日・時間帯表示・場所名称・住所・ステータス・予約URL)
        // ACFは使わず register_post_meta + 自作メタボックス(class-meta-box.php)で実装(CLAUDE.md参照)
        register_post_meta('consultation_event', 'start_at', [
            'type'         => 'string',
            'single'       => true,
            'show_in_rest' => true,
        ]);
        register_post_meta('consultation_event', 'time_note', [
            'type'         => 'string', // 複数時間枠がある場合の表示用自由記述。例: "10:00〜 / 13:00〜 / 15:00〜"
            'single'       => true,
            'show_in_rest' => true,
        ]);
        register_post_meta('consultation_event', 'location', [
            'type'         => 'string',
            'single'       => true,
            'show_in_rest' => true,
        ]);
        register_post_meta('consultation_event', 'location_name', [
            'type'         => 'string',
            'single'       => true,
            'show_in_rest' => true,
        ]);
        register_post_meta('consultation_event', 'location_address', [
            'type'         => 'string',
            'single'       => true,
            'show_in_rest' => true,
        ]);
        register_post_meta('consultation_event', 'status', [
            'type'         => 'string', // "open" | "full" | "closed"
            'single'       => true,
            'show_in_rest' => true,
        ]);
        register_post_meta('consultation_event', 'reservation_url', [
            'type'         => 'string',
            'single'       => true,
            'show_in_rest' => true,
        ]);
    }

    public static function columns(array $columns): array
    {
        $new_columns = [];

        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;

            if ($key === 'title') {
                $new_columns['start_at']     = __('Event Date', 'ototsugu-connector');
                $new_columns['time_note']    = __('Time Note', 'ototsugu-connector');
                $new_columns['location_name'] = __('Venue Name', 'ototsugu-connector');
                $new_columns['status']       = __('Status', 'ototsugu-connector');
            }
        }

        return $new_columns;
    }

    public static function render_column(string $column, int $post_id): void
    {
        $value = get_post_meta($post_id, $column, true);

        if ($column === 'start_at') {
            $date = OTSG_Date::parse($value);
            echo $date ? esc_html(OTSG_Date::full($date)) : '';
            return;
        }

        if ($column === 'status') {
            $labels = [
                'open'   => __('Open', 'ototsugu-connector'),
                'full'   => __('Full', 'ototsugu-connector'),
                'closed' => __('Closed', 'ototsugu-connector'),
            ];
            echo esc_html($labels[$value ?: 'open'] ?? $value);
            return;
        }

        echo esc_html($value);
    }
}
