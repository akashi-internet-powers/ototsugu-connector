(function (wp) {
    var __ = wp.i18n.__;

    wp.blocks.registerBlockType('ototsugu-connector/event-list', {
        title: __('Consultation Event List', 'ototsugu-connector'),
        category: 'widgets',
        icon: 'calendar-alt',
        description: __('A dynamic block that displays the consultation event list on the front end.', 'ototsugu-connector'),
        attributes: {
            layout: {
                type: 'string',
                default: 'list',
            },
            showDetailLink: {
                type: 'boolean',
                default: true,
            },
            dateFormat: {
                type: 'string',
                default: 'full',
            },
            showReservationLink: {
                type: 'boolean',
                default: true,
            },
        },
        supports: {
            html: false,
        },
        edit: function (props) {
            var SelectControl = wp.components.SelectControl;
            var ToggleControl = wp.components.ToggleControl;
            var Fragment = wp.element.Fragment;
            var InspectorControls = wp.blockEditor.InspectorControls;
            var blockProps = wp.blockEditor.useBlockProps({
                className: 'otsg-event-list-editor-placeholder',
            });

            return wp.element.createElement(
                Fragment,
                null,
                wp.element.createElement(
                    InspectorControls,
                    null,
                    wp.element.createElement(SelectControl, {
                        label: __('Display format', 'ototsugu-connector'),
                        value: props.attributes.layout,
                        options: [
                            { label: __('Single line', 'ototsugu-connector'), value: 'list' },
                            { label: __('Table', 'ototsugu-connector'), value: 'table' },
                            { label: __('Cards', 'ototsugu-connector'), value: 'card' },
                        ],
                        onChange: function (layout) {
                            props.setAttributes({ layout: layout });
                        },
                    }),
                    wp.element.createElement(SelectControl, {
                        label: __('Date format', 'ototsugu-connector'),
                        value: props.attributes.dateFormat,
                        options: [
                            /* translators: Example of the "full" date display format, with the weekday. Use the date format of your language. */
                            { label: __('September 6, 2026 (Sun)', 'ototsugu-connector'), value: 'full' },
                            /* translators: Example of the "slash" date display format, with the weekday. */
                            { label: __('2026/09/06 (Sun)', 'ototsugu-connector'), value: 'slash' },
                            /* translators: Example of the "short" date display format (without the year), with the weekday. */
                            { label: __('Sep 6 (Sun)', 'ototsugu-connector'), value: 'short' },
                        ],
                        onChange: function (dateFormat) {
                            props.setAttributes({ dateFormat: dateFormat });
                        },
                    }),
                    wp.element.createElement(ToggleControl, {
                        label: __('Show detail link', 'ototsugu-connector'),
                        checked: props.attributes.showDetailLink,
                        onChange: function (showDetailLink) {
                            props.setAttributes({ showDetailLink: showDetailLink });
                        },
                    }),
                    wp.element.createElement(ToggleControl, {
                        label: __('Show reservation link', 'ototsugu-connector'),
                        checked: props.attributes.showReservationLink,
                        onChange: function (showReservationLink) {
                            props.setAttributes({ showReservationLink: showReservationLink });
                        },
                    })
                ),
                wp.element.createElement(
                    'p',
                    blockProps,
                    __('Consultation event list (displayed on the front end)', 'ototsugu-connector')
                )
            );
        },
        save: function () {
            return null;
        },
    });
})(window.wp);
