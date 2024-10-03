/**
 * Transients Manager Extra Plugins
 */

'use strict';

var AmTmExtraPlugins = window.AmTmExtraPlugins || (function (document, window, $) {

    /**
     * Public functions and properties.
     */
    var app = {

        /**
         * Start the engine.
         */
        init: function () {
            $(app.ready);
        },

        /**
         * Document ready.
         */
        ready: function () {
            app.events();
        },

        /**
         * Dismissible notices events.
         */
        events: function () {
            $(document).on(
                'click',
                'button.am-tm-extra-plugin-item[data-plugin]',
                function (e) {
                    e.preventDefault();

                    if ($(this).hasClass('disabled')) {
                        return;
                    }

                    let button     = $(this);
                    let buttonText = $(this).html();

                    $(this).addClass('disabled');
                    $(this).html(l10nAmTmExtraPlugins.loading);

                    $.post(
                        am_tm_extra_plugins.ajax_url,
                        {
                            action: 'transients_manager_extra_plugin',
                            nonce: am_tm_extra_plugins.extra_plugin_install_nonce,
                            plugin: $(this).data('plugin'),
                        }
                    ).done(function (response) {
                        console.log(response);
                        if (response.success !== true) {
                            console.log("Plugin installed failed with message: " + response.data.message);
                            button.fadeOut(300);

                            setTimeout(function () {
                                button.html(buttonText);
                                button.removeClass('disabled');
                                button.fadeIn(100);
                            }, 3000);
                            return;
                        }

                        button.fadeOut(500);
                        button.html(l10nAmTmExtraPlugins.activated);
                        button.prepend('<span class="dashicons dashicons-yes"></span>');
                        button.fadeIn(300);
                    });
                }
            );

            $(document).on(
                'click',
                '.notice.cross-promotion .notice-dismiss',
                function (e) {
                    e.preventDefault();

                    $.post(
                        am_tm_extra_plugins.ajax_url,
                        {
                            action: 'transients_manager_cross_promo_dismiss',
                            nonce: am_tm_extra_plugins.cross_promo_dismiss_nonce,
                        }
                    ).done(function (response) {
                        if (response.success !== true) {
                            console.log("Error dismissing notice");
                            return;
                        }
                    });
                }
            );
        },


    };

    return app;

}(document, window, jQuery));

// Initialize.
AmTmExtraPlugins.init();
