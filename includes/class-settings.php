<?php

if (!defined('ABSPATH')) {
    exit;
}

class OTSG_Settings
{
    private const OPTION_NAME = 'otsg_detail_layout';
    private const PAGE_SLUG = 'otsg-settings';

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'add_menu']);
        add_action('admin_init', [self::class, 'register_settings']);
    }

    public static function get_detail_layout(): string
    {
        $layout = get_option(self::OPTION_NAME, 'standard');

        return in_array($layout, ['standard', 'two-pane'], true) ? $layout : 'standard';
    }

    public static function add_menu(): void
    {
        add_submenu_page(
            'edit.php?post_type=consultation_event',
            __('Consultation Event Settings', 'ototsugu-connector'),
            __('Settings', 'ototsugu-connector'),
            'manage_options',
            self::PAGE_SLUG,
            [self::class, 'render_page']
        );
    }

    public static function register_settings(): void
    {
        register_setting('otsg_settings', self::OPTION_NAME, [
            'type'              => 'string',
            'sanitize_callback' => [self::class, 'sanitize_layout'],
            'default'           => 'standard',
        ]);

        add_settings_section(
            'otsg_display_settings',
            __('Single Event Display Settings', 'ototsugu-connector'),
            '__return_false',
            self::PAGE_SLUG
        );

        add_settings_field(
            self::OPTION_NAME,
            __('Layout', 'ototsugu-connector'),
            [self::class, 'render_layout_field'],
            self::PAGE_SLUG,
            'otsg_display_settings'
        );
    }

    public static function sanitize_layout(string $layout): string
    {
        return in_array($layout, ['standard', 'two-pane'], true) ? $layout : 'standard';
    }

    public static function render_layout_field(): void
    {
        $layout = self::get_detail_layout();
        ?>
        <select name="<?php echo esc_attr(self::OPTION_NAME); ?>">
            <option value="standard" <?php selected($layout, 'standard'); ?>><?php esc_html_e('Standard', 'ototsugu-connector'); ?></option>
            <option value="two-pane" <?php selected($layout, 'two-pane'); ?>><?php esc_html_e('Two-pane', 'ototsugu-connector'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('In the two-pane layout, the post content is shown on the left and the consultation event details on the right. On narrow (mobile) screens it switches to a single column.', 'ototsugu-connector'); ?></p>
        <?php
    }

    public static function render_page(): void
    {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Consultation Event Settings', 'ototsugu-connector'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('otsg_settings');
                do_settings_sections(self::PAGE_SLUG);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
