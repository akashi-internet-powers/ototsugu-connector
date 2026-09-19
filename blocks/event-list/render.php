<?php
/**
 * 「相談会日程一覧」ブロックのサーバーサイドレンダリング。
 * consultation-app 側のREST APIと同じデータソース(consultation_event投稿)を参照するため、
 * 表示ロジックはここに集約し、フィールド名の変更があった場合の修正箇所を1つにまとめる。
 *
 * @var array $attributes
 */

if (!defined('ABSPATH')) {
    exit;
}

$events = get_posts([
    'post_type'      => 'consultation_event',
    'posts_per_page' => -1,
    'orderby'        => 'meta_value',
    'meta_key'       => 'start_at',
    'order'          => 'ASC',
]);

if (empty($events)) {
    echo '<p>' . esc_html__('There are no published consultation events at the moment.', 'ototsugu-connector') . '</p>';
    return;
}

$layout          = isset($attributes['layout']) && in_array($attributes['layout'], ['list', 'table', 'card'], true)
    ? $attributes['layout']
    : 'list';
$show_detail_link = !isset($attributes['showDetailLink']) || $attributes['showDetailLink'];
$show_reservation_link = !isset($attributes['showReservationLink']) || $attributes['showReservationLink'];
$date_format      = isset($attributes['dateFormat']) ? $attributes['dateFormat'] : 'full';
$date_formats     = ['full', 'date', 'slash', 'short'];
$date_format      = in_array($date_format, $date_formats, true) ? $date_format : 'full';
$format_date      = static function (string $value) use ($date_format): string {
    $date = OTSG_Date::parse($value);
    if (!$date) {
        return $value;
    }

    return OTSG_Date::with_weekday($date, $date_format);
};

if ($layout === 'table') :
    ?>
    <div class="otsg-event-table-wrapper">
        <table class="otsg-event-table">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('Event Date', 'ototsugu-connector'); ?></th>
                    <th scope="col"><?php esc_html_e('Consultation Event', 'ototsugu-connector'); ?></th>
                    <th scope="col"><?php esc_html_e('Venue', 'ototsugu-connector'); ?></th>
                    <th scope="col"><?php esc_html_e('Status', 'ototsugu-connector'); ?></th>
                    <?php if ($show_detail_link) : ?>
                        <th scope="col"><?php esc_html_e('Details', 'ototsugu-connector'); ?></th>
                    <?php endif; ?>
                    <?php if ($show_reservation_link) : ?>
                        <th scope="col"><?php esc_html_e('Reservation', 'ototsugu-connector'); ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
    <?php
elseif ($layout === 'card') :
    ?>
    <ul class="otsg-event-cards">
    <?php
else :
    ?>
    <ul class="otsg-event-list">
    <?php
endif;

foreach ($events as $event) :
    $start_at        = get_post_meta($event->ID, 'start_at', true);
    $time_note       = get_post_meta($event->ID, 'time_note', true);
    $location_name   = get_post_meta($event->ID, 'location_name', true);
    $location_address = get_post_meta($event->ID, 'location_address', true);
    $legacy_location = get_post_meta($event->ID, 'location', true);
    if (!$location_name && !$location_address) {
        $location_name = $legacy_location;
    }
    $map_query = trim($location_name . ' ' . $location_address);
    $map_url   = $map_query
        ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($map_query)
        : '';
    $status          = get_post_meta($event->ID, 'status', true) ?: 'open';
    $reservation_url = get_post_meta($event->ID, 'reservation_url', true);
    $status_label    = [
        'open'   => __('Open', 'ototsugu-connector'),
        'full'   => __('Full', 'ototsugu-connector'),
        'closed' => __('Closed', 'ototsugu-connector'),
    ][$status] ?? '';
    $event_url       = get_permalink($event);
    $display_date    = $format_date($start_at);
    $card_date       = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $start_at, wp_timezone());
    $image_url       = get_the_post_thumbnail_url($event, 'medium');

    if ($layout === 'table') :
        ?>
        <tr class="otsg-event-table__row otsg-event-table__row--<?php echo esc_attr($status); ?>">
            <td><?php echo esc_html($display_date); ?><?php if ($time_note) : ?><br><small><?php echo esc_html($time_note); ?></small><?php endif; ?></td>
            <td><?php echo esc_html($event->post_title); ?></td>
            <td>
                <?php echo esc_html($location_name); ?>
                <?php if ($location_address) : ?><br><small><?php echo esc_html($location_address); ?></small><?php endif; ?>
                <?php if ($map_url) : ?><br><a href="<?php echo esc_url($map_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('View map', 'ototsugu-connector'); ?></a><?php endif; ?>
            </td>
            <td><?php echo esc_html($status_label); ?></td>
            <?php if ($show_detail_link) : ?>
                <td><a href="<?php echo esc_url($event_url); ?>"><?php esc_html_e('View details', 'ototsugu-connector'); ?></a></td>
            <?php endif; ?>
            <?php if ($show_reservation_link) : ?>
                <td>
                    <?php if ($reservation_url && $status === 'open') : ?>
                        <a href="<?php echo esc_url($reservation_url); ?>"><?php esc_html_e('Make a reservation', 'ototsugu-connector'); ?></a>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
        </tr>
        <?php
        continue;
    endif;

    if ($layout === 'card') :
        ?>
        <li class="otsg-event-card otsg-event-card--<?php echo esc_attr($status); ?>">
            <div class="otsg-event-card__date" aria-hidden="true">
                <?php if ($card_date) : ?>
                    <span class="otsg-event-card__month"><?php echo esc_html(OTSG_Date::format(
                        $card_date,
                        /* translators: Date format (PHP date() syntax) for the month shown on the event card, e.g. "M" for Sep. */
                        _x('M', 'event card month format', 'ototsugu-connector')
                    )); ?></span>
                    <strong class="otsg-event-card__day"><?php echo esc_html($card_date->format('j')); ?></strong>
                    <span class="otsg-event-card__weekday"><?php echo esc_html(OTSG_Date::format($card_date, 'l')); ?></span>
                <?php else : ?>
                    <span class="otsg-event-card__month"><?php esc_html_e('Event Date', 'ototsugu-connector'); ?></span>
                    <span class="otsg-event-card__day">-</span>
                <?php endif; ?>
            </div>
            <div class="otsg-event-card__body">
                <span class="otsg-event-card__status"><?php echo esc_html($status_label); ?></span>
                <h3 class="otsg-event-card__title">
                    <?php if ($show_detail_link) : ?><a href="<?php echo esc_url($event_url); ?>"><?php endif; ?>
                        <?php echo esc_html($event->post_title); ?>
                    <?php if ($show_detail_link) : ?></a><?php endif; ?>
                </h3>
                <?php if ($time_note) : ?><p class="otsg-event-card__time-note"><?php echo esc_html($time_note); ?></p><?php endif; ?>
                <?php if ($location_name || $location_address) : ?>
                    <p class="otsg-event-card__location">
                        <?php echo esc_html($location_name); ?><?php if ($location_address) : ?> / <?php echo esc_html($location_address); ?><?php endif; ?>
                        <?php if ($map_url) : ?> <a href="<?php echo esc_url($map_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('Map', 'ototsugu-connector'); ?></a><?php endif; ?>
                    </p>
                <?php endif; ?>
                <div class="otsg-event-card__actions">
                    <?php if ($show_detail_link) : ?><a class="otsg-event-card__detail" href="<?php echo esc_url($event_url); ?>"><?php esc_html_e('View details', 'ototsugu-connector'); ?></a><?php endif; ?>
                    <?php if ($show_reservation_link && $reservation_url && $status === 'open') : ?><a class="otsg-event-card__reserve" href="<?php echo esc_url($reservation_url); ?>"><?php esc_html_e('Make a reservation', 'ototsugu-connector'); ?></a><?php endif; ?>
                </div>
            </div>
            <?php if ($image_url) : ?>
                <img class="otsg-event-card__image" src="<?php echo esc_url($image_url); ?>" alt="" loading="lazy">
            <?php endif; ?>
        </li>
        <?php
        continue;
    endif;
    ?>
    <li class="otsg-event-list__item otsg-event-list__item--<?php echo esc_attr($status); ?>">
        <span class="otsg-event-list__title">
            <?php if ($show_detail_link) : ?><a href="<?php echo esc_url($event_url); ?>"><?php endif; ?>
                <?php echo esc_html($event->post_title); ?>
            <?php if ($show_detail_link) : ?></a><?php endif; ?>
        </span>
        <span class="otsg-event-list__meta">
            <?php echo esc_html($display_date); ?> / <?php echo esc_html($location_name); ?>
            <?php if ($location_address) : ?> / <?php echo esc_html($location_address); ?><?php endif; ?>
            <?php if ($map_url) : ?> / <a href="<?php echo esc_url($map_url); ?>" target="_blank" rel="noopener"><?php esc_html_e('Map', 'ototsugu-connector'); ?></a><?php endif; ?>
        </span>
        <?php if ($time_note) : ?>
            <span class="otsg-event-list__time-note">(<?php echo esc_html($time_note); ?>)</span>
        <?php endif; ?>
        <span class="otsg-event-list__status"><?php echo esc_html($status_label); ?></span>
        <?php if ($show_reservation_link && $reservation_url && $status === 'open') : ?>
            <span class="otsg-event-list__reserve">
                <a href="<?php echo esc_url($reservation_url); ?>"><?php esc_html_e('Make a reservation', 'ototsugu-connector'); ?></a>
            </span>
        <?php endif; ?>
    </li>
    <?php
endforeach;

if ($layout === 'table') :
    ?>
            </tbody>
        </table>
    </div>
    <?php
elseif ($layout === 'card') :
    ?>
    </ul>
    <?php
else :
    ?>
    </ul>
    <?php
endif;
return;
