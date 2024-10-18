<?php
/**
 * Plugin Name:       Transients Manager
 * Plugin URI:        https://wordpress.org/plugins/transients-manager/
 * Description:       Provides a familiar interface to view, search, edit, and delete Transients.
 * Author:            WPBeginner
 * Author URI:        https://www.wpbeginner.com
 * Contributors:      wpbeginner, smub, mordauk, johnjamesjacoby
 * License:           GNU General Public License v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       transients-manager
 * Requires PHP:      5.6.20
 * Requires at least: 5.3
 * Version:           2.0.7
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

require_once( dirname( __FILE__ ) . '/src/TransientsManager.php' );
require_once( dirname( __FILE__ ) . '/src/CrossPromotion.php' );

define( 'AM_TM_VERSION', '2.0.7' );
define( 'AM_TM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

\AM\TransientsManager\TransientsManager::getInstance()->init();
\AM\TransientsManager\CrossPromotion::init();
