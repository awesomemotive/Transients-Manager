<?php

namespace AM\TransientsManager;

/**
 * Cross promotion class
 */
class CrossPromotion
{
    /** @var string Dismiss key */
    const NOTICE_DISMISS_KEY = 'am_tm_cross_promotion_dismissed';

    /**
     * Initialize the class
     *
     * @return void
     */
    public static function init()
    {
        add_action('admin_notices', [__CLASS__, 'notices']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueueScripts']);
        add_action('wp_ajax_transients_manager_extra_plugin', [__CLASS__, 'installPluginAjax']);
    }

    /**
     * Enqueue scripts
     *
     * @return void
     */
    public static function enqueueScripts()
    {
        if (!self::shouldShowNotice()) {
            return;
        }

        // Don't load in case Lite is installed. Pro only has a link to the website.
        if (self::isPluginInstalled('duplicator/duplicator.php')) {
            return;
        }

        wp_enqueue_script(
            'am-tm-extra-plugins',
            AM_TM_PLUGIN_URL . "assets/js/extra-plugins.js",
            array('jquery'),
            AM_TM_VERSION,
            true
        );
        wp_localize_script(
            'am-tm-extra-plugins',
            'l10nAmTmExtraPlugins',
            array(
                'loading'   => esc_html__('Loading...', 'transients-manager'),
                'failure'   => esc_html__('Failure', 'transients-manager'),
                'active'    => esc_html__('Active', 'transients-manager'),
                'activated' => esc_html__('Activated', 'transients-manager'),
            )
        );

        wp_localize_script(
            'am-tm-extra-plugins',
            'am_tm_extra_plugins',
            array(
                'ajax_url'                   => admin_url('admin-ajax.php'),
                'extra_plugin_install_nonce' => wp_create_nonce('transients_manager_extra_plugin'),
            )
        );
    }

    /**
     * Install plugin via ajax
     *
     * @return void
     */
    public static function installPluginAjax()
    {
        if (!self::shouldShowNotice()) {
            return;
        }

        try {
            if (check_ajax_referer('transients_manager_extra_plugin', 'nonce', false) === false) {
                throw new \Exception(__('Invalid nonce', 'transients-manager'));
            }

            if (!current_user_can('install_plugins')) {
                throw new \Exception(__('You do not have permission to install plugins', 'transients-manager'));
            }

            $slug = filter_input(INPUT_POST, 'plugin', FILTER_SANITIZE_STRING);
            if (empty($slug)) {
                throw new \Exception(__('Invalid plugin slug', 'transients-manager'));
            }

            if (self::installPlugin($slug)) {
                wp_send_json_success([
                    'success' => true,
                    'message' => __('Plugin installed successfully', 'transients-manager'),
                ]);
            } else {
                throw new \Exception(__('Failed to install plugin', 'transients-manager'));
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display admin notices
     *
     * @return void
     */
    public static function notices()
    {
        if (!self::shouldShowNotice()) {
            return;
        }

        self::renderStyles();
        self::render();
    }

    /**
     * True fi notice should be shown
     *
     * @return bool
     */
    private static function shouldShowNotice()
    {
        $tm     = TransientsManager::getInstance();
        $screen = get_current_screen();
        if ( $screen->id !== $tm->screen_id ) {
            return false;
        }

        if (!current_user_can('install_plugins')) {
            return false;
        }
        if ($tm->getInstallTime() + 2 * WEEK_IN_SECONDS < time()) {
            return false;
        }

        if (get_option(self::NOTICE_DISMISS_KEY, false)) {
            return false;
        }

        return true;
    }

    /**
     * Display admin notices
     *
     * @return void
     */
    private static function render()
    {
        foreach (self::getAllPlugins() as $slug => $pluginInfo) {
            if ($pluginInfo['isPro']) {
                continue;
            }

            $hasPro = isset($pluginInfo['pro']);
            if ($hasPro && self::isPluginInstalled($pluginInfo['pro'])) {
                continue;
            }

            if (!$hasPro && self::isPluginInstalled($slug)) {
                continue;
            }

            $isPro = false;
            if ($hasPro && self::isPluginInstalled($slug)) {
                $isPro      = true;
                $pluginInfo = self::getPluginBySlug($pluginInfo['pro']);
            }
?>
<div class="notice is-dismissible cross-promotion">
    <p class="intro-text">
        <em><?php esc_html_e('Enoying Transients Manager? Check out our other plugin...', 'transients-manager'); ?></em>
    </p>
    <div class="cross-promotion-plugin">
        <div class="cross-promotion-image">
            <img src="<?php echo esc_url(AM_TM_PLUGIN_URL . '/assets/img/duplicator-icon.svg'); ?>" alt="<?php esc_attr($pluginInfo['name']); ?>">
        </div>
        <div class="cross-promotion-info">
            <p class="name"><strong><?php echo esc_html($pluginInfo['name']); ?></strong></p>
            <p class="desc"><?php echo esc_html($pluginInfo['desc']); ?></p>
            <?php if (!$isPro) : ?>
                <button class="am-tm-extra-plugin-item button button-primary" data-plugin="<?php echo esc_attr($slug); ?>">
                    <?php esc_html_e('Install', 'transients-manager'); ?>
                </button>
            <?php else : ?>
                <a href="<?php echo esc_url($pluginInfo['url']); ?>" class="button button-primary" target="_blank"><?php esc_html_e('Install', 'transients-manager'); ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php 
        }
    }

    /**
     * Render styles
     *
     * @return void
     */
    private static function renderStyles()
    {
?>
        <style>
            .cross-promotion {
                display: flex;
                flex-direction: column;
                align-items: center;
                border-left-width: 1px;
                padding: 20px;
            }

            .cross-promotion .intro-text {
                font-size: 14px;
                align-self: flex-start;
            }

            .cross-promotion-plugin {
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: center;
                max-width: 800px;
            }

            .cross-promotion-plugin img {
                width: 100px;
                margin-right: 20px;
            }

            .cross-promotion-plugin .name {
                font-size: 16px;
            }

            .cross-promotion-plugin .desc {
                color: #5f5f5f;
                margin-bottom: 14px;
            }

            .cross-promotion-plugin button.button,
            .cross-promotion-plugin a.button {
                padding: 0px 30px;
            }
        </style>
<?php
    }

    /**
     * Is plugin installed
     *
     * @param string $slyg Plugin slug
     *
     * @return bool
     */
    protected static function isPluginInstalled($slug)
    {
        static $installedSlugs = null;
        if ($installedSlugs === null) {
            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $installedSlugs = array_keys(get_plugins());
        }
        return in_array($slug, $installedSlugs);
    }


    /**
     * Install plugin by slug
     *
     * @param string $slug Plugin slug
     *
     * @return bool true on success
     */
    protected static function installPlugin($slug)
    {
        if (self::isPluginInstalled($slug)) {
            return true;
        }

        if (($pluginInfo = self::getPluginBySlug($slug)) === false) {
            throw new \Exception('Plugin info not found');
        }

        if (!isset($pluginInfo['url']) || substr($pluginInfo['url'], -4) !== '.zip') {
            throw new \Exception('Invalid plugin url for installation');
        }

        if (!current_user_can('install_plugins')) {
            throw new \Exception('User does not have permission to install plugins');
        }

        if (!class_exists('Plugin_Upgrader')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }
        wp_cache_flush();

        $upgrader = new \Plugin_Upgrader(new \Automatic_Upgrader_Skin());
        if (!$upgrader->install($pluginInfo['url'])) {
            throw new \Exception('Failed to install plugin');
        }

        return true;
    }

    /**
     * Get the plugin info
     *
     * @return array<string, array{name:string,url:string,desc:string,pro:string|bool,isPro:bool}> Plugin info or false if not found
     */
    protected static function getAllPlugins()
    {
        return [
            'duplicator/duplicator.php' => [
                'name' => __('Duplicator - WordPress Migration & Backup Plugin', 'transients-manager'),
                'url'  => 'https://downloads.wordpress.org/plugin/duplicator.zip',
                'desc' => __('Leading WordPress backup & site migration plugin. Over 1,500,000+ smart website owners use Duplicator to make easy, reliable and secure WordPress backups to protect their websites.', 'transients-manager'),
                'pro'  => 'duplicator-pro/duplicator-pro.php' ,
                'isPro' => false,
            ],
            'duplicator-pro/duplicator-pro.php' => [
                'name'  => __('Duplicator Pro - WordPress Migration & Backup Plugin', 'transients-manager'),
                'url'   => 'http://duplicator.com/?utm_source=transientsmanager&utm_medium=link&utm_campaign=Cross%20Promotion',
                'desc'  => __('Leading WordPress backup & site migration plugin. Smart website owners use Duplicator Pro to make easy, reliable and secure WordPress backups to protect their websites.', 'transients-manager'),
                'pro'  => false,
                'isPro' => true,
            ],
        ];

    }

    /**
     * Get the plugin info
     *
     * @param string $slug Plugin slug
     *
     * @return array{name:string,slug:string,url:string,desc:string}|false Plugin info or false if not found
     */
    protected static function getPluginBySlug($slug)
    {
        $allPlugins = self::getAllPlugins();
        if (isset($allPlugins[$slug])) {
            return $allPlugins[$slug];
        }

        return false;
    }
}
