<?php
/**
 * Plugin Name:       Ototsugu Connector
 * Description:       Manages consultation event schedules and delivers them to apps through the REST API.
 * Version:           0.2.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ototsugu-connector
 */

if (!defined('ABSPATH')) {
    exit; // 直接アクセス禁止
}

define('OTSG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OTSG_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once OTSG_PLUGIN_DIR . 'includes/class-date.php';
require_once OTSG_PLUGIN_DIR . 'includes/class-post-type.php';
require_once OTSG_PLUGIN_DIR . 'includes/class-meta-box.php';
require_once OTSG_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once OTSG_PLUGIN_DIR . 'includes/class-settings.php';
require_once OTSG_PLUGIN_DIR . 'includes/class-single-event.php';

function otsg_activate(): void
{
    OTSG_Post_Type::register();
    flush_rewrite_rules();
}

function otsg_deactivate(): void
{
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'otsg_activate');
register_deactivation_hook(__FILE__, 'otsg_deactivate');

add_action('init', ['OTSG_Post_Type', 'register']);
OTSG_Meta_Box::register();
OTSG_Settings::register();
OTSG_Single_Event::register();
add_action('rest_api_init', ['OTSG_Rest_Api', 'register_fields']);

// 相談会日程一覧の動的ブロック(FSE対応)。表示ロジックは blocks/event-list/render.php に集約。
add_action('init', function () {
    register_block_type(OTSG_PLUGIN_DIR . 'blocks/event-list');
});
