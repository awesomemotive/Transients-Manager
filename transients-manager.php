<?php
/**
 * Plugin Name:       Transients Manager
 * Plugin URI:        https://wordpress.org/plugins/transients-manager/
 * Description:       Provides a familiar interface to view, search, edit, and delete Transients.
 * Author:            WPBeginner
 * Author URI:        http://www.wpbeginner.com
 * Contributors:      wpbeginner, smub, mordauk, johnjamesjacoby
 * License:           GNU General Public License v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       transients-manager
 * Requires PHP:      5.6.20
 * Requires at least: 5.3
 * Version:           2.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

class AM_Transients_Manager {

	/**
	 * ID of the plugin page
	 *
	 * @since 2.0
	 * @var string
	 */
	public $page_id = 'transients-manager';

	/**
	 * Timestamp for right now
	 *
	 * @since 2.0
	 * @var int
	 */
	public $time_now = 0;

	/**
	 * Timestamp of the next time WPCron will delete expired transients
	 *
	 * @since 2.0
	 * @var int
	 */
	public $next_cron_delete = 0;

	/**
	 * Get things started
	 *
	 * @since 1.0
	 */
	public function __construct() {
		add_action( 'plugins_loaded',    array( $this, 'text_domain' ) );
		add_action( 'admin_init',        array( $this, 'set_times' ) );
		add_action( 'admin_init',        array( $this, 'process_actions' ) );
		add_action( 'admin_menu',        array( $this, 'tools_link' ) );
		add_action( 'admin_notices',     array( $this, 'notices' ) );
		add_action( 'admin_bar_menu',    array( $this, 'suspend_transients_button' ), 999 );
		add_filter( 'pre_update_option', array( $this, 'maybe_block_update_transient' ), -1, 2 );
		add_filter( 'pre_get_option',    array( $this, 'maybe_block_update_transient' ), -1, 2 );
		add_action( 'added_option',      array( $this, 'maybe_block_set_transient' ), -1, 1 );

		// Styles
		add_action( "admin_print_styles-tools_page_{$this->page_id}", array( $this, 'print_styles' ) );
	}

	/**
	 * Set the related timestamp values
	 *
	 * @since 2.0
	 */
	public function set_times() {
		$this->time_now         = time();
		$this->next_cron_delete = wp_next_scheduled( 'delete_expired_transients' );
	}

	/**
	 * Load text domain
	 *
	 * @since 1.0
	 */
	public function text_domain() {
		load_plugin_textdomain( 'transients-manager' );
	}

	/**
	 * Register menu link under Tools
	 *
	 * @since 1.0
	 */
	public function tools_link() {

		// Set the screen ID
		$this->screen_id = add_management_page(
			__( 'Transients Manager', 'transients-manager' ),
			__( 'Transients', 'transients-manager' ),
			'manage_options',
			$this->page_id,
			array( $this, 'admin' )
		);
	}

	/**
	 * Editing or showing?
	 *
	 * @since 2.0
	 * @return string
	 */
	public function page_type() {

		// Sanitize the action
		$action = ! empty( $_GET['action'] )
			? sanitize_key( $_GET['action'] )
			: '';

		// Edit or Show?
		return ! empty( $action ) && ( 'edit_transient' === $action )
			? 'edit'
			: 'show';
	}

	/**
	 * Render the admin UI
	 *
	 * @since 1.0
	 */
	public function admin() {

		// Editing a single Transient
		( 'edit' === $this->page_type() )
			? $this->page_edit_transient()
			: $this->page_show_transients();
	}

	/**
	 * Admin notices
	 *
	 * @since 2.0
	 */
	public function notices() {

		// Get the current screen
		$screen = get_current_screen();

		// Bail if not the correct screen
		if ( $screen->id !== $this->screen_id ) {
			return;
		}

		// Persistent Transients
		if ( wp_using_ext_object_cache() ) :

?>
<div class="notice notice-info">
	<p><?php _e( 'You are using a persistent object cache. This screen may show incomplete information.', 'transients-manager' ); ?></p>
</div>
<?php

		endif;

		// Updated Transient
		if ( ! empty( $_GET['updated'] ) ) :

?>
<div class="notice notice-success is-dismissible">
	<p><strong><?php _e( 'Trasient updated.', 'transients-manager' ); ?></strong></p>
</div>
<?php

		endif;

		// Deleted Transients
		if ( ! empty( $_GET['deleted'] ) ) :

?>
<div class="notice notice-success is-dismissible">
	<p><strong><?php _e( 'Trasient deleted.', 'transients-manager' ); ?></strong></p>
</div>
<?php

		endif;
	}

	/**
	 * Output the page HTML for the Transients mock-list-table
	 *
	 * @since 2.0
	 */
	public function page_show_transients() {

		// Vars
		$search   = ! empty( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
		$per_page = ! empty( $_GET['per_page'] ) ? absint( $_GET['per_page'] ) : 30;
		$page     = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$offset   = $per_page * ( $page - 1 );
		$count    = $this->get_total_transients( $search );
		$pages    = ceil( $count / $per_page );
		$one_page = ( 1 === $pages ) ? 'one-page' : '';

		// Pagination
		$pagination = paginate_links( array(
			'base'      => 'tools.php?%_%',
			'format'    => '&paged=%#%',
			'prev_text' => '&laquo;',
			'next_text' => '&raquo;',
			'total'     => $pages,
			'current'   => $page
		) );

		// Transients
		$transients = $this->get_transients( array(
			'search' => $search,
			'offset' => $offset,
			'number' => $per_page
		) );

?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php _e( 'Transients', 'transients-manager' ); ?></h1>
	<hr class="wp-header-end">

	<form method="get">
		<p class="search-box">
			<label class="screen-reader-text" for="transient-search-input"><?php _e( 'Search', 'transients-manager' ); ?></label>
			<input type="search" id="transient-search-input" name="s" value="<?php echo esc_attr( $search ); ?>" />
			<input type="submit" id="search-submit" class="button" value="<?php _e( 'Search Transients', 'transients-manager' ); ?>" />
			<input type="hidden" name="page" value="<?php echo esc_html( $this->page_id ); ?>" />
		</p>
	</form>

	<form method="post">
		<input type="hidden" name="transient" value="all" />
		<?php wp_nonce_field( 'transients_manager' ); ?>

		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<label for="bulk-action-selector-top" class="screen-reader-text"><?php _e( 'Select Bulk action', 'transients-manager' ); ?></label>
				<select name="action" id="bulk-action-selector-top">
					<option value="-1"><?php _e( 'Bulk actions', 'transients-manager' ); ?></option>

					<optgroup label="<?php _e( 'Selected', 'transients-manager' ); ?>">
						<option value="delete_selected_transients"><?php _e( 'Delete Selected', 'transients-manager' ); ?></option>
					</optgroup>

					<optgroup label="<?php _e( 'Expirations', 'transients-manager' ); ?>">
						<option value="delete_expired_transients"><?php _e( 'Delete Expired', 'transients-manager' ); ?></option>
						<option value="delete_transients_with_expiration"><?php _e( 'Delete With Expiration', 'transients-manager' ); ?></option>
						<option value="delete_transients_without_expiration"><?php _e( 'Delete Without Expiration', 'transients-manager' ); ?></option>
					</optgroup>

					<optgroup label="<?php _e( 'Flush', 'transients-manager' ); ?>">
						<option value="delete_all_transients"><?php _e( 'Delete All', 'transients-manager' ); ?></option>
					</optgroup>
				</select>
				<input type="submit" class="button secondary" value="<?php _e( 'Apply', 'transients-manager' ); ?>" />
			</div>

			<div class="tablenav-pages <?php echo esc_attr( $one_page ); ?>">
				<span class="displaying-num"><?php printf( _n( '%s Transient', '%s Transients', $count, 'transients-manager' ), number_format_i18n( $count ) ); ?></span>
				<span class="pagination-links"><?php echo $pagination; // HTML OK  ?></span>
			</div>
		</div>

		<table class="wp-list-table widefat fixed transients striped">
			<thead>
				<tr>
					<td id="cb" class="manage-column column-cb check-column">
						<label for="cb-select-all-<?php echo (int) $page; ?>" class="screen-reader-text"><?php _e( 'Select All', 'transients-manager' ); ?></label>
						<input type="checkbox" id="cb-select-all-<?php echo (int) $page; ?>">
					</td>
					<th class="column-primary"><?php _e( 'Name', 'transients-manager' ); ?></th>
					<th class="column-value"><?php _e( 'Value', 'transients-manager' ); ?></th>
					<th class="column-expiration"><?php _e( 'Expiration', 'transients-manager' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $transients ) ) :

					foreach ( $transients as $transient ) :

						// Get Transient name
						$name       = $this->get_transient_name( $transient );
						$value      = $this->get_transient_value( $transient );
						$expiration = $this->get_transient_expiration( $transient );

						// Delete
						$delete_url = wp_nonce_url(
							remove_query_arg(
								array( 'deleted', 'updated' ),
								add_query_arg(
									array(
										'action'    => 'delete_transient',
										'transient' => $name,
										'name'      => $transient->option_name
									)
								)
							),
							'transients_manager'
						);

						// Edit
						$edit_url = remove_query_arg(
							array( 'updated', 'deleted' ),
							add_query_arg(
								array(
									'action'   => 'edit_transient',
									'trans_id' => $transient->option_id
								)
							)
						); ?>

						<tr>
							<th id="cb" class="manage-column column-cb check-column">
								<label for="cb-select-<?php echo (int) $page; ?>" class="screen-reader-text"><?php printf( __( 'Select %s', 'transients-manager' ), esc_html( $name ) ); ?></label>
								<input type="checkbox" id="cb-select-<?php echo (int) $transient->option_id; ?>" name="transients[]" value="<?php echo (int) $transient->option_id; ?>">
							</th>

							<td class="column-primary" data-colname="<?php _e( 'Name', 'transients-manager' ); ?>">
								<pre class="truncate">
									<code class="transient-name" title="<?php printf( __( 'Option ID: %d', 'transients-manager' ), (int) $transient->option_id ); ?>"><?php echo esc_html( $name ); ?></code>
								</pre>

								<div class="row-actions">
									<span class="edit"><a href="<?php echo esc_url( $edit_url ); ?>" class="edit"><?php _e( 'Edit', 'transients-manager' ); ?></a></span>
									|
									<span class="delete"><a href="<?php echo esc_url( $delete_url ); ?>" class="delete"><?php _e( 'Delete', 'transients-manager' ); ?></a></span>
								</div>

								<button type="button" class="toggle-row">
									<span class="screen-reader-text"><?php _e( 'Show more details', 'transients-manager' ); ?></span>
								</button>
							</td>

							<td data-colname="<?php _e( 'Value', 'transients-manager' ); ?>">
								<span class="transient-value truncate"><?php
									echo $value; // HTML OK
								?></span>
							</td>

							<td data-colname="<?php _e( 'Expiration', 'transients-manager' ); ?>">
								<span class="transient-expiration"><?php
									echo $expiration; // HTML OK
								?></span>
							</td>
						</tr>

					<?php endforeach;

				else : ?>

					<tr><td colspan="4"><?php _e( 'No transients found.', 'transients-manager' ); ?></td>

				<?php endif; ?>
			</tbody>
			<tfoot>
				<tr>
					<td class="manage-column column-cb check-column">
						<label for="cb-select-all-<?php echo (int) $page; ?>" class="screen-reader-text"><?php _e( 'Select All', 'transients-manager' ); ?></label>
						<input type="checkbox" id="cb-select-all-<?php echo (int) $page; ?>">
					</td>
					<th class="column-primary"><?php _e( 'Name', 'transients-manager' ); ?></th>
					<th><?php _e( 'Value', 'transients-manager' ); ?></th>
					<th><?php _e( 'Expiration', 'transients-manager' ); ?></th>
				</tr>
			</tfoot>
		</table>

		<div class="tablenav bottom">
			<div class="alignleft actions bulkactions">
				<label for="bulk-action-selector-top" class="screen-reader-text"><?php _e( 'Select Bulk action', 'transients-manager' ); ?></label>
				<select name="action" id="bulk-action-selector-bottom">
					<option value="-1"><?php _e( 'Bulk actions', 'transients-manager' ); ?></option>

					<optgroup label="<?php _e( 'Selected', 'transients-manager' ); ?>">
						<option value="delete_selected_transients"><?php _e( 'Delete Selected', 'transients-manager' ); ?></option>
					</optgroup>

					<optgroup label="<?php _e( 'Expirations', 'transients-manager' ); ?>">
						<option value="delete_expired_transients"><?php _e( 'Delete Expired', 'transients-manager' ); ?></option>
						<option value="delete_transients_with_expiration"><?php _e( 'Delete With Expiration', 'transients-manager' ); ?></option>
						<option value="delete_transients_without_expiration"><?php _e( 'Delete Without Expiration', 'transients-manager' ); ?></option>
					</optgroup>

					<optgroup label="<?php _e( 'Flush', 'transients-manager' ); ?>">
						<option value="delete_all_transients"><?php _e( 'Delete All', 'transients-manager' ); ?></option>
					</optgroup>
				</select>
				<input type="submit" class="button secondary" value="<?php _e( 'Apply', 'transients-manager' ); ?>" />
			</div>

			<div class="tablenav-pages <?php echo esc_attr( $one_page ); ?>">
				<span class="displaying-num"><?php printf( _n( '%s Transient', '%s Transients', $count, 'transients-manager' ), number_format_i18n( $count ) ); ?></span>
				<span class="pagination-links"><?php echo $pagination; // HTML OK ?></span>
			</div>
		</div>
	</form>

	<?php $this->site_time(); ?>
</div>

<?php
	}

	/**
	 * Output the page HTML for editing a Transient
	 *
	 * @since 2.0
	 */
	public function page_edit_transient() {
		$transient_id = ! empty( $_GET['trans_id'] ) ? absint( $_GET['trans_id'] ) : 0;
		$transient    = $this->get_transient_by_id( $transient_id );

		$name = $this->get_transient_name( $transient );
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php _e( 'Edit Transient', 'transients-manager' ); ?></h1>
	<hr class="wp-header-end">

	<form method="post">
		<input type="hidden" name="transient" value="<?php echo esc_attr( $name ); ?>" />
		<input type="hidden" name="action" value="update_transient" />
		<?php wp_nonce_field( 'transients_manager' ); ?>

		<table class="form-table">
			<tbody>
				<tr>
					<th><?php _e( 'Option ID', 'transients-manager' ); ?></th>
					<td><input type="text" disabled class="large-text code" name="name" value="<?php echo esc_attr( $transient->option_id ); ?>" /></td>
				</tr>
				<tr>
					<th><?php _e( 'Name', 'transients-manager' ); ?></th>
					<td><input type="text" class="large-text code" name="name" value="<?php echo esc_attr( $transient->option_name ); ?>" /></td>
				</tr>
				<tr>
					<th><?php _e( 'Expiration', 'transients-manager' ); ?></th>
					<td><input type="text" class="large-text" name="expires" value="<?php echo $this->get_transient_expiration_time( $transient ); ?>" />
				</tr>
				<tr>
					<th><?php _e( 'Value', 'transients-manager' ); ?></th>
					<td>
						<textarea class="large-text code" name="value" id="transient-editor" style="height: 302px; padding-left: 35px;"><?php
							echo esc_textarea( $transient->option_value );
						?></textarea>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<?php submit_button( '', 'primary', '', false ); ?>
		</p>
	</form>
</div>

<?php
	}

	/**
	 * Output the time for the site
	 *
	 * (Props to WP Crontrol for this approach)
	 *
	 * @since 2.0
	 */
	public function site_time() {

		// Get options
		$timezone_string = get_option( 'timezone_string', '' );
		$gmt_offset      = get_option( 'gmt_offset', 0 );
		$timezone_name   = 'UTC';

		// UTC
		if ( ( 'UTC' !== $timezone_string ) || ! empty( $gmt_offset ) ) {

			$formatted_offset = ( 0 <= $gmt_offset )
				? '+' . (string) $gmt_offset
				: (string) $gmt_offset;

			$formatted_offset = str_replace(
				array( '.25', '.5',  '.75' ),
				array( ':15', ':30', ':45' ),
				$formatted_offset
			);
		}

		// Format the timezone name
		$timezone_name = ! empty( $formatted_offset )
			? 'UTC' . $formatted_offset
			: str_replace( '_', ' ', $timezone_string );

		// Site time
		$site_time     = date_i18n( 'Y-m-d H:i:s', $this->time_now );
		$site_time_utc = gmdate( 'Y-m-d\TH:i:s+00:00', $this->time_now );
		$st_html       = esc_html( sprintf(
			/* translators: 1: Date and time, 2: Timezone */
			__( 'Site time: %1$s (%2$s)', 'transients-manager' ),
			$site_time,
			$timezone_name
		) );

		// Cron time
		$cron_time     = wp_date( 'Y-m-d H:i:s', $this->next_cron_delete );
		$cron_time_utc = gmdate( 'Y-m-d\TH:i:s+00:00', $this->next_cron_delete );
		$ct_time_since = $this->time_since( $this->next_cron_delete - $this->time_now );
		$nc_time       = esc_html( sprintf(
			/* translators: 1: Date and time, 2: Time since */
			__( 'Auto clean: %1$s (%2$s) - %3$s from now', 'transients-manager' ),
			$cron_time,
			$timezone_name,
			$ct_time_since
		) );
?>

<p class="transients-manager-helpful-times">
	<?php

	echo sprintf( '<time datetime="%1$s" title="%1$s">%2$s</time>',
		$site_time_utc,
		$st_html
	);

	?><br><?php

	echo sprintf( '<time datetime="%1$s" title="%1$s">%2$s</time>',
		$cron_time_utc,
		$nc_time
	);

?></p>

<?php
	}

	/**
	 * Add toolbar node for suspending transients
	 *
	 * @since 1.6
	 * @param object $wp_admin_bar
	 */
	public function suspend_transients_button( $wp_admin_bar ) {

		// Bail if user cannot manage options
		if ( ! current_user_can( 'manage_options' ) ) {
		    return;
		}

		$action = get_option( 'pw_tm_suspend' ) ? 'unsuspend_transients' : 'suspend_transients';
		$label  = get_option( 'pw_tm_suspend' ) ? '<span style="color: #b32d2e;">' . __( 'Unsuspend Transients', 'transients-manager' ) . '</span>' : __( 'Suspend Transients', 'transients-manager' );

		// Suspend
		$wp_admin_bar->add_node( array(
			'id'     => 'tm-suspend',
			'title'  => $label,
			'parent' => 'top-secondary',
			'href'   => wp_nonce_url( add_query_arg( array( 'action' => $action ) ), 'transients_manager' )
		) );

		// View
		$wp_admin_bar->add_node( array(
			'id'     => 'tm-view',
			'title'  => __( 'View Transients', 'transients-manager' ),
			'parent' => 'tm-suspend',
			'href'   => add_query_arg(
				array(
					'page' => $this->page_id
				),
				admin_url( 'tools.php' )
			)
		) );
	}

	/**
	 * Get transients from the database
	 *
	 * These queries are uncached, to prevent race conditions with persistent
	 * object cache setups and the way Transients use them.
	 *
	 * @since  1.0
	 * @param  array $args
	 * @return array
	 */
	private function get_transients( $args = array() ) {
		global $wpdb;

		// Parse arguments
		$r = $this->parse_args( $args );

		// Escape some LIKE parts
		$esc_name = '%' . $wpdb->esc_like( '_transient_'         ) . '%';
		$esc_time = '%' . $wpdb->esc_like( '_transient_timeout_' ) . '%';

		// SELECT
		$sql = array( 'SELECT' );

		// COUNT
		if ( ! empty( $r['count'] ) ) {
			$sql[] = 'count(option_id)';
		} else {
			$sql[] = '*';
		}

		// FROM
		$sql[] = "FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name NOT LIKE %s";

		// Search
		if ( ! empty( $r['search'] ) ) {
			$search = '%' . $wpdb->esc_like( $r['search'] ) . '%';
			$sql[]  = $wpdb->prepare( "AND option_name LIKE %s", $search );
		}

		// Limits
		if ( empty( $r['count'] ) ) {
			$offset = absint( $r['offset'] );
			$number = absint( $r['number'] );
			$sql[]  = $wpdb->prepare( "ORDER BY option_id DESC LIMIT %d, %d", $offset, $number );
		}

		// Combine the SQL parts
		$query = implode( ' ', $sql );

		// Prepare
		$prepared = $wpdb->prepare( $query, $esc_name, $esc_time );

		// Query
		$transients = empty( $r['count'] )
			? $wpdb->get_results( $prepared ) // Rows
			: $wpdb->get_var( $prepared );    // Count

		// Return transients
		return $transients;
	}

	/**
	 * Parse the query arguments
	 *
	 * @since 2.0
	 * @param array $args
	 * @return array
	 */
	private function parse_args( $args = array() ) {

		// Parse
		$r = wp_parse_args( $args, array(
			'offset' => 0,
			'number' => 30,
			'search' => '',
			'count'  => false
		) );

		// Return
		return $r;
	}

	/**
	 * Retrieve the total number transients in the database
	 *
	 * If a search is performed, it returns the number of found results
	 *
	 * @since  1.0
	 * @param  string $search
	 * @return int
	 */
	private function get_total_transients( $search = '' ) {

		// Query
		$count = $this->get_transients( array(
			'count'  => true,
			'search' => $search
		) );

		// Return int
		return absint( $count );
	}

	/**
	 * Retrieve a transient by its ID
	 *
	 * @since  1.0
	 * @param  int $id
	 * @return object
	 */
	private function get_transient_by_id( $id = 0 ) {
		global $wpdb;

		$id = absint( $id );

		// Bail if empty ID
		if ( empty( $id ) ) {
			return false;
		}

		// Prepare
		$prepared = $wpdb->prepare( "SELECT * FROM {$wpdb->options} WHERE option_id = %d", $id );

		// Query
		return $wpdb->get_row( $prepared );
	}

	/**
	 * Retrieve the transient name from the transient object
	 *
	 * @since  1.0
	 * @return string
	 */
	private function get_transient_name( $transient = false ) {

		// Bail if no Transient
		if ( empty( $transient ) ) {
			return '';
		}

		// Position
		$pos = ( false !== strpos( $transient->option_name, '_site_transient' ) )
			? 16
			: 11;

		return substr( $transient->option_name, $pos, strlen( $transient->option_name ) );
	}

	/**
	 * Retrieve the human-friendly transient value from the transient object
	 *
	 * @since  1.0
	 * @param  object $transient
	 * @return string/int
	 */
	private function get_transient_value( $transient ) {

		// Get the value type
		$type = $this->get_transient_value_type( $transient );

		// Maybe unserialize
		$value = maybe_unserialize( $transient->option_value );

		// Trim or dash
		$value = is_scalar( $value )
			? '<code>' . wp_trim_words( $value, 5 ) . '</code>'
			: '&mdash;';

		// Return
		return $value . '<br><span class="transient-type badge">' . esc_html( $type ) . '</span>';
	}

	/**
	 * Try to guess the type of value the Transient is
	 *
	 * @since  2.0
	 * @param  object $transient
	 * @return string
	 */
	private function get_transient_value_type( $transient ) {

		// Default type
		$type = __( 'unknown', 'transients-manager' );

		// Try to unserialize
		$value = maybe_unserialize( $transient->option_value );

		// Array
		if ( is_array( $value ) ) {
			$type = __( 'array', 'transients-manager' );

		// JSON
		} elseif ( is_object( json_decode( $value ) ) ) {
			$type = __( 'json', 'transients-manager' );

		// Object
		} elseif ( is_object( $value ) ) {
			$type = __( 'object', 'transients-manager' );

		// Serialized array
		} elseif ( is_serialized( $value ) ) {
			$type = __( 'serialized', 'transients-manager' );

		// HTML
		} elseif ( strip_tags( $value ) !== $value ) {
			$type = __( 'html', 'transients-manager' );

		// Scalar
		} elseif ( is_scalar( $value ) ) {

			if ( is_numeric( $value ) ) {

				// Likely a timestamp
				if ( 10 === strlen( $value ) ) {
					$type = __( 'timestamp?', 'transients-manager' );

				// Likely a boolean
				} elseif ( in_array( $value, array( '0', '1' ), true ) ) {
					$type = __( 'boolean?', 'transients-manager' );

				// Any number
				} else {
					$type = __( 'numeric', 'transients-manager' );
				}

			} else {
				$type = __( 'scalar', 'transients-manager' );
			}

		// Empty
		} elseif ( empty( $value ) ) {
			$type = __( 'empty', 'transients-manager' );
		}

		// Return type
		return $type;
	}

	/**
	 * Retrieve the expiration timestamp
	 *
	 * @since  1.0
	 * @param  object $transient
	 * @return int
	 */
	private function get_transient_expiration_time( $transient ) {

		$name = $this->get_transient_name( $transient );

		if ( false !== strpos( $transient->option_name, '_site_transient' ) ) {
			$time = get_option( "_site_transient_timeout_{$name}" );

		} else {
			$time = get_option( "_transient_timeout_{$name}" );
		}

		return $time;
	}

	/**
	 * Retrieve the human-friendly expiration time
	 *
	 * @since  1.0
	 * @param  object $transient
	 * @return string
	 */
	private function get_transient_expiration( $transient ) {

		$expiration = $this->get_transient_expiration_time( $transient );

		// Bail if no expiration
		if ( empty( $expiration ) ) {
			return '&mdash;<br><span class="badge">' . __( 'Persistent', 'transients-manager' ) . '</span>';
		}

		// UTC & local dates
		$date_utc   = gmdate( 'Y-m-d\TH:i:s+00:00', $expiration );
		$date_local = get_date_from_gmt( date( 'Y-m-d H:i:s', $expiration ), 'Y-m-d H:i:s' );

		// Create <time> tag
		$time = sprintf(
			'<time datetime="%1$s" title="%1$s">%2$s</time>',
			esc_attr( $date_utc ),
			esc_html( $date_local )
		);

		// Expired
		if ( $this->time_now > $expiration ) {
			return $time . '<br><span class="transient-expired badge">' . __( 'Expired', 'transients-manager' ) . '</span>';
		}

		// Return time since
		return $time . '<br><span class="badge green">' . $this->time_since( $expiration - $this->time_now ) . '</span>';
	}

	/**
	 * Process delete and update actions
	 *
	 * @since 1.0
	 */
	public function process_actions() {

		if ( empty( $_REQUEST['action'] ) ) {
			return;
		}

		// Sanitize action
		$action = sanitize_key( $_REQUEST['action'] );

		// Bail if malformed Transient request
		if ( empty( $_REQUEST['transient'] ) && ! in_array( $action, array( 'suspend_transients', 'unsuspend_transients' ), true ) ) {
			return;
		}

		// Bail if cannot manage options
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Bail if nonce fails
		if ( ! empty( $_REQUEST['_wpnonce'] ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'transients_manager' ) ) {
			return;
		}

		if ( ! in_array( $action, array( 'suspend_transients', 'unsuspend_transients' ), true ) ) {
			$search    = ! empty( $_REQUEST['s'] ) ? urlencode( $_REQUEST['s'] ) : '';
			$transient = sanitize_key( $_REQUEST['transient'] );
			$site_wide = ! empty( $_REQUEST['name'] ) && ( false !== strpos( $_REQUEST['name'], '_site_transient' ) );
		}

		switch ( $action ) {

			case 'suspend_transients' :
				update_option( 'pw_tm_suspend', 1 );
				wp_safe_redirect( remove_query_arg( array( 'updated', 'deleted', 'action', '_wpnonce' ) ) );
				exit;

			case 'unsuspend_transients' :
				delete_option( 'pw_tm_suspend', 1 );
				wp_safe_redirect( remove_query_arg( array( 'updated', 'deleted', 'action', '_wpnonce' ) ) );
				exit;

			case 'delete_transient' :
				$this->delete_transient( $transient, $site_wide );
				wp_safe_redirect(
					remove_query_arg(
						array( 'updated' ),
						add_query_arg(
							array(
								'page'    => $this->page_id,
								's'       => $search,
								'deleted' => true
							),
							admin_url( 'tools.php' )
						)
					)
				);
				exit;

			case 'update_transient' :
				$this->update_transient( $transient, $site_wide );
				wp_safe_redirect(
					remove_query_arg(
						array( 'deleted' ),
						add_query_arg(
							array(
								'page'    => $this->page_id,
								's'       => $search,
								'updated' => true
							),
							admin_url( 'tools.php' )
						)
					)
				);
				exit;

			case 'delete_selected_transients' :
				$this->delete_selected_transients();
				wp_safe_redirect(
					remove_query_arg(
						array( 'updated' ),
						add_query_arg(
							array(
								'page'    => $this->page_id,
								'deleted' => true
							),
							admin_url( 'tools.php' )
						)
					)
				);
				exit;

			case 'delete_expired_transients' :
				$this->delete_expired_transients();
				wp_safe_redirect(
					remove_query_arg(
						array( 'updated' ),
						add_query_arg(
							array(
								'page'    => $this->page_id,
								'deleted' => true
							),
							admin_url( 'tools.php' )
						)
					)
				);
				exit;

			case 'delete_transients_with_expiration' :
				$this->delete_transients_with_expirations();
				wp_safe_redirect(
					remove_query_arg(
						array( 'updated' ),
						add_query_arg(
							array(
								'page'    => $this->page_id,
								'deleted' => true
							),
							admin_url( 'tools.php' )
						)
					)
				);
				exit;

			case 'delete_transients_without_expiration' :
				$this->delete_transients_without_expirations();
				wp_safe_redirect(
					remove_query_arg(
						array( 'updated' ),
						add_query_arg(
							array(
								'page'    => $this->page_id,
								'deleted' => true
							),
							admin_url( 'tools.php' )
						)
					)
				);
				exit;

			case 'delete_all_transients' :
				$this->delete_all_transients();
				wp_safe_redirect(
					remove_query_arg(
						array( 'updated' ),
						add_query_arg(
							array(
								'page'    => $this->page_id,
								'deleted' => true
							),
							admin_url( 'tools.php' )
						)
					)
				);
				exit;
		}
	}

	/**
	 * Delete a transient by name
	 *
	 * @since  1.0
	 * @param  object $transient
	 * @param  boolean $site_wide
	 * @return boolean
	 */
	private function delete_transient( $transient = '', $site_wide = false ) {

		// Bail if no Transient
		if ( empty( $transient ) ) {
			return false;
		}

		// Transient type
		$retval = ( false !== $site_wide )
			? delete_site_transient( $transient )
			: delete_transient( $transient );

		// Return
		return $retval;
	}

	/**
	 * Bulk delete function
	 *
	 * @since  1.5
	 * @param  array $transients
	 * @return boolean
	 */
	private function bulk_delete_transients( $transients = array() ) {

		// Bail if empty or error
		if ( empty( $transients ) || is_wp_error( $transients ) ) {
			return false;
		}

		// Loop through Transients, and delete them
		foreach ( $transients as $transient ) {
			$site_wide = ( false !== strpos( $transient, '_site_transient' ) );
			$prefix    = ! empty( $site_wide ) ? '_site_transient_timeout_' : '_transient_timeout_';
			$name      = str_replace( $prefix, '', $transient );

			// Delete
			$this->delete_transient( $name, $site_wide );
		}

		// No errors
		return true;
	}

	/**
	 * Delete Selected transients.
	 *
	 * @since  1.8
	 * @return false|int
	 */
	public function delete_selected_transients() {
		global $wpdb;

		// Bail if no Transients
		if ( empty( $_REQUEST['transients'] ) || ! is_array( $_REQUEST['transients'] ) ) {
			return 0;
		}

		// Filter
		$transients_ids_filtered = wp_parse_id_list( $_REQUEST['transients'] );

		// Bail if no IDs
		if ( empty( $transients_ids_filtered ) ) {
			return 0;
		}

		// Query
		$placeholders = array_fill( 0, count( $transients_ids_filtered ), '%d' );
		$format       = implode( ', ', $placeholders );
		$count        = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_id IN ({$format})", $transients_ids_filtered ) );

		// Return count of deleted
		return $count;
	}

	/**
	 * Delete all expired transients
	 *
	 * @since  1.1
	 * @return boolean
	 */
	public function delete_expired_transients() {
		global $wpdb;

		// Query
		$esc_time = '%' . $wpdb->esc_like( '_transient_timeout_' ) . '%';
		$prepared = $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} where option_name LIKE %s AND option_value+0 < %d", $esc_time, $this->time_now );
		$expired  = $wpdb->get_col( $prepared );

		// Bulk delete
		return $this->bulk_delete_transients( $expired );
	}

	/**
	 * Delete all transients with expiration
	 *
	 * @since  1.2
	 * @return boolean
	 */
	public function delete_transients_with_expirations() {
		global $wpdb;

		// Query
		$esc_time    = '%' . $wpdb->esc_like( '_transient_timeout_' ) . '%';
		$prepared    = $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} where option_name LIKE %s", $esc_time );
		$will_expire = $wpdb->get_col( $prepared );

		// Bulk delete
		return $this->bulk_delete_transients( $will_expire );
	}

	/**
	 * Delete all transients without expiration
	 *
	 * @since  2.0
	 * @return boolean
	 */
	public function delete_transients_without_expirations() {
		global $wpdb;

		// Escape likes
		$esc_time = '%' . $wpdb->esc_like( '_transient_timeout_' ) . '%';
		$esc_name = '%' . $wpdb->esc_like( '_transient_'         ) . '%';

		// Queries
		$timeouts = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} where option_name LIKE %s", $esc_time ) );
		$names    = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} where option_name LIKE %s", $esc_name ) );

		// Diff to remove timeouts from names
		$items = array_diff( $names, $timeouts );

		// Remove "%_transient_timeout_" from left of timeouts
		foreach ( $timeouts as $index => $timeout ) {
			$pos = strrpos( $timeout, '_transient_timeout_' ) + strlen( '_transient_timeout_' );
			$timeouts[ $index ] = substr( $timeout, $pos );
		}

		// Remove "%_transient_" from left of items
		foreach ( $items as $index => $item ) {
			$pos = strrpos( $item, '_transient_' ) + strlen( '_transient_' );
			$items[ $index ] = substr( $item, $pos );
		}

		// Remove timeouts from items
		$bulk = array_diff( $items, $timeouts );

		// Bulk delete
		return $this->bulk_delete_transients( $bulk );
	}

	/**
	 * Delete all transients
	 *
	 * @return false|int
	 */
	public function delete_all_transients() {
		global $wpdb;

		// Escape like
		$esc_name = '%' . $wpdb->esc_like( '_transient_' ) . '%';

		// Query
		$count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options}
				WHERE option_name LIKE %s"
			),
			$esc_name
		);

		// Return count
		return $count;
	}

	/**
	 * Update an existing transient
	 *
	 * @since  1.0
	 * @param  object  $transient
	 * @param  boolean $site_wide
	 * @return boolean
	 */
	private function update_transient( $transient = '', $site_wide = false ) {

		// Bail if no Transient
		if ( empty( $transient ) ) {
			return false;
		}

		// Values
		$value      = stripslashes( $_POST['value'] );
		$expiration = absint( stripslashes( $_POST['expires'] ) );

		// Subtract now
		$expiration = ( $expiration - $this->time_now );

		// Transient type
		$retval = ( false !== $site_wide )
			? set_site_transient( $transient, $value, $expiration )
			: set_transient( $transient, $value, $expiration );

		// Return
		return $retval;
	}

	/**
	 * Prevent transient from being updated if transients are suspended
	 *
	 * @since  1.6
	 * @param  mixed  $value
	 * @param  string $option
	 * @return mixed
	 */
	public function maybe_block_update_transient( $value = '', $option = '' ) {

		// Bail if not Suspended
		if ( ! get_option( 'pw_tm_suspend' ) ) {
			return $value;
		}

		// Bail if not a Transient
		if ( false === strpos( $option, '_transient' ) ) {
			return $value;
		}

		// Return false
		return false;
	}

	/**
	 * Prevent transient from being updated if transients are suspended
	 *
	 * @since 1.6
	 */
	public function maybe_block_set_transient( $option = '' ) {

		// Bail if not Suspended
		if ( ! get_option( 'pw_tm_suspend' ) ) {
			return;
		}

		// Bail if not a Transient
		if ( false === strpos( $option, '_transient' ) ) {
			return;
		}

		// Delete the Transient
		delete_option( $option );
	}

	/**
	 * Converts a period of time in seconds into a human-readable format
	 * representing the interval.
	 *
	 * @since  2.0
	 * @param  int $since A period of time in seconds.
	 * @return string An interval represented as a string.
	 */
	public function time_since( $since = 0 ) {

		// Array of time period chunks.
		$chunks = array(
			/* translators: 1: The number of years in an interval of time. */
			array( 60 * 60 * 24 * 365, _n_noop( '%s year', '%s years', 'transients-manager' ) ),
			/* translators: 1: The number of months in an interval of time. */
			array( 60 * 60 * 24 * 30, _n_noop( '%s month', '%s months', 'transients-manager' ) ),
			/* translators: 1: The number of weeks in an interval of time. */
			array( 60 * 60 * 24 * 7, _n_noop( '%s week', '%s weeks', 'transients-manager' ) ),
			/* translators: 1: The number of days in an interval of time. */
			array( 60 * 60 * 24, _n_noop( '%s day', '%s days', 'transients-manager' ) ),
			/* translators: 1: The number of hours in an interval of time. */
			array( 60 * 60, _n_noop( '%s hour', '%s hours', 'transients-manager' ) ),
			/* translators: 1: The number of minutes in an interval of time. */
			array( 60, _n_noop( '%s minute', '%s minutes', 'transients-manager' ) ),
			/* translators: 1: The number of seconds in an interval of time. */
			array( 1, _n_noop( '%s second', '%s seconds', 'transients-manager' ) ),
		);

		if ( $since <= 0 ) {
			return __( 'now', 'transients-manager' );
		}

		/**
		 * We only want to output two chunks of time here, eg:
		 * x years, xx months
		 * x days, xx hours
		 * so there's only two bits of calculation below:
		 */
		$j = count( $chunks );

		// Step one: the first chunk.
		for ( $i = 0; $i < $j; $i++ ) {
			$seconds = $chunks[ $i ][ 0 ];
			$name    = $chunks[ $i ][ 1 ];

			// Finding the biggest chunk (if the chunk fits, break).
			$count = floor( $since / $seconds );

			if ( ! empty( $count ) ) {
				break;
			}
		}

		// Set output var.
		$output = sprintf( translate_nooped_plural( $name, $count, 'transients-manager' ), $count );

		// Step two: the second chunk.
		if ( $i + 1 < $j ) {
			$seconds2 = $chunks[ $i + 1 ][ 0 ];
			$name2    = $chunks[ $i + 1 ][ 1 ];
			$count2   = floor( ( $since - ( $seconds * $count ) ) / $seconds2 );

			// Add to output var.
			if ( ! empty( $count2 ) ) {
				$output .= ' ' . sprintf( translate_nooped_plural( $name2, $count2, 'transients-manager' ), $count2 );
			}
		}

		return $output;
	}

	/**
	 * Add <style> tag to "admin_print_styles-{$this->page_id}" hook
	 *
	 * @since 2.0
	 */
	public function print_styles() {

		// Get the page type
		$type = $this->page_type();

		// Editing...
		if ( 'edit' === $type ) {

			// Try to enqueue the code editor
			$settings = wp_enqueue_code_editor(
				array(
					'type'       => 'text/plain',
					'codemirror' => array(
						'indentUnit' => 4,
						'tabSize'    => 4,
					),
				)
			);

			// Bail if user disabled CodeMirror.
			if ( false === $settings ) {
				return;
			}

			// Target the textarea
			wp_add_inline_script(
				'code-editor',
				sprintf(
					'jQuery( function() { wp.codeEditor.initialize( "transient-editor", %s ); } );',
					wp_json_encode( $settings )
				)
			);

			// Custom styling
			wp_add_inline_style(
				'code-editor',
				'.CodeMirror-wrap {
					width: 99%;
					border: 1px solid #8c8f94;
					border-radius: 3px;
					overflow: hidden;
				}
				.CodeMirror-gutters {
					background: transparent;
				}'
			);

		// Showing list-table...
		} else {

			// Escape once
			$esc = esc_attr( $this->page_id );

?>

<style type="text/css" id="transients-manager">
	body.tools_page_<?php echo $esc; // Escaped ?> table.transients .column-value {
		width: 38%;
	}

	body.tools_page_<?php echo $esc; // Escaped ?> table.transients .column-expiration {
		width: 170px;
	}

	body.tools_page_<?php echo $esc; // Escaped ?> table.transients .truncate {
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}

	body.tools_page_<?php echo $esc; // Escaped ?> table.transients .column-primary pre {
		margin: 0;
	}

	body.tools_page_<?php echo $esc; // Escaped ?> table.transients span.transient-value {
		display: block;
	}

	body.tools_page_<?php echo $esc; // Escaped ?> table.transients code {
		background: transparent;
		margin: 0;
		padding: 0;
	}

	body.tools_page_<?php echo $esc; // Escaped ?> table.transients span.transient-value,
	body.tools_page_<?php echo $esc; // Escaped ?> table.transients span.transient-expiration {
		cursor: default;
	}

	body.tools_page_<?php echo $esc; // Escaped ?> table.transients span.badge,
	body.tools_page_<?php echo $esc; // Escaped ?> table.transients div.row-actions {
		margin-top: 5px;
	}

	body.tools_page_<?php echo $esc; // Escaped ?> table.transients span.badge {
		padding: 2px 7px;
		border-radius: 4px;
		display: inline-flex;
		align-items: center;
		background: rgba(0, 0, 0, 0.07);
		color: #50575e;
	}

	body.tools_page_<?php echo $esc; // Escaped ?> table.transients span.badge.green {
		color: #017d5c;
		background: #e5f5f0;
	}

	body.tools_page_<?php echo $esc; // Escaped ?> table.transients span.transient-expired {
		color: #b32d2e;
		background: #ffd6d6;
	}

	body.tools_page_<?php echo $esc; // Escaped ?> .tablenav .info {
		display: inline-block;
		margin: 2px 0;
		padding: 2px 7px;
	}

	body.tools_page_<?php echo $esc; // Escaped ?> .tablenav-pages span.displaying-num {
		display: inline-block;
		margin: 5px 0;
	}

	body.tools_page_<?php echo $esc; // Escaped ?> span.pagination-links .page-numbers {
		border-color: #7e8993;
		color: #32373c;
		display: inline-block;
		vertical-align: baseline;
		min-width: 30px;
		min-height: 30px;
		text-decoration: none;
		text-align: center;
		font-size: 13px;
		line-height: 2.15384615;
		margin: 0;
		padding: 0 10px;
		cursor: pointer;
		border-width: 1px;
		border-style: solid;
		border-radius: 3px;
		-webkit-appearance: none;
		white-space: nowrap;
		box-sizing: border-box;
	}

	body.tools_page_<?php echo $esc; // Escaped ?> span.pagination-links .page-numbers.next,
	body.tools_page_<?php echo $esc; // Escaped ?> span.pagination-links .page-numbers.prev {
		font-size: 16px;
		line-height: 1.625;
		padding: 0 4px;
	}

	body.tools_page_<?php echo $esc; // Escaped ?> span.pagination-links a.page-numbers:hover {
		background-color: #f0f0f1;
		border-color: #717c87;
		color: #262a2e;
	}

	body.tools_page_<?php echo $esc; // Escaped ?> span.pagination-links span.page-numbers {
		color: #a7aaad;
		border-color: #dcdcde;
		background: #f6f7f7;
		box-shadow: none;
		cursor: default;
		transform: none;
	}
</style>

<?php
		}
	}
}

new AM_Transients_Manager();
