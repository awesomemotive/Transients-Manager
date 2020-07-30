<?php
/**
 * Plugin Name: Transients Manager
 * Plugin URI: http://pippinsplugins.com/transients-manager
 * Description: Provides a UI to manage your site's transients. You can view, search, edit, and delete transients at will.
 * Version: 1.8.1
 * Author: Pippin Williamson
 * Author URI: http://pippinsplugins.com
 * Contributors: mordauk
 * Text Domain: transients-manager
 * Domain Path: /languages
*/

class PW_Transients_Manager {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function __construct() {

		add_action( 'plugins_loaded', array( $this, 'text_domain' ) );
		add_action( 'admin_menu', array( $this, 'tools_link' ) );
		add_action( 'admin_init', array( $this, 'process_actions' ) );
		add_action( 'admin_bar_menu', array( $this, 'suspend_transients_button' ), 999 );
		add_filter( 'pre_update_option', array( $this, 'maybe_block_update_transient' ), -1, 3 );
		add_filter( 'pre_get_option', array( $this, 'maybe_block_update_transient' ), -1, 3 );
		add_action( 'added_option', array( $this, 'maybe_block_set_transient' ), -1, 2 );

	}

	/**
	 * Load our plugin's text domain
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function text_domain() {

		load_plugin_textdomain('transients-manager', false, dirname(plugin_basename( __FILE__)) . '/languages/');

	}

	/**
	 * Register our menu link under Tools
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function tools_link() {

		add_management_page(
			__( 'Transients Manager', 'transients-manager' ),
			__( 'Transients', 'transients-manager' ),
			'manage_options',
			'pw-transients-manager',
			array( $this, 'admin' )
		);

	}

	/**
	 * Render the admin UI
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function admin() {

		$search      = ! empty( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
		$page        = isset( $_GET['paged'] )   ? absint( $_GET['paged'] )      : 1;
		$per_page    = 30;
		$offset      = $per_page * ( $page - 1 );
		$count       = $this->get_total_transients( $search );
		$pages       = ceil( $count / $per_page );
		$args        = array(
			'search' => $search,
			'offset' => $offset,
			'number' => $per_page
		);
		$pagination  = paginate_links( array(
			'base'   => 'tools.php?%_%',
			'format' => '&paged=%#%',
			'total'  => $pages,
			'current'=> $page
		));

		$transients = $this->get_transients( $args );

?>
		<div class="wrap">

			<?php if( ! empty( $_GET['action'] ) && 'edit_transient' == $_GET['action'] ) : ?>

				<h2><?php _e( 'Edit Transient', 'transients-manager' ); ?></h2>

				<?php $transient = $this->get_transient_by_id( absint( $_GET['trans_id'] ) ); ?>

				<form method="post">
					<table class="form-table striped">
						<tbody>
							<tr>
								<th><?php _e( 'Name', 'transients-manager' ); ?></th>
								<td><input type="text" class="large-text" name="name" value="<?php echo esc_attr( $transient->option_name ); ?>" /></td>
							</tr>
							<tr>
								<th><?php _e( 'Expires In', 'transients-manager' ); ?></th>
								<td><input type="text" class="large-text" name="expires" value="<?php echo $this->get_transient_expiration_time( $transient ); ?>"/>
							</tr>
							<tr>
								<th><?php _e( 'Value', 'transients-manager' ); ?></th>
								<td><textarea class="large-text" name="value" rows="10" cols="50"><?php echo esc_textarea( $transient->option_value ); ?></textarea></td>
							</tr>
					</table>
					<input type="hidden" name="transient" value="<?php echo esc_attr( $this->get_transient_name( $transient ) ); ?>"/>
					<input type="hidden" name="action" value="update_transient"/>
					<?php wp_nonce_field( 'transient_manager' ); ?>
					<p class="submit">
						<?php submit_button('','primary', '', false ); ?>
						<?php submit_button( __( 'Cancel', 'pw-transients-manager' ), 'delete', '', false, array( 'onclick' => 'history.back();', ) ); ?>
					</p>
				</form>

			<?php else : ?>

				<h2><?php _e( 'Transients', 'transients-manager' ); ?></h2>

				<form method="post" class="alignleft">
					<input type="hidden" name="action" value="delete_expired_transients" />
					<input type="hidden" name="transient" value="all" />
					<?php wp_nonce_field( 'transient_manager' ); ?>
					<input type="submit" class="button secondary" value="<?php _e( 'Delete Expired Transients', 'transients-manager' ); ?>" />
				</form>

				<form method="post" class="alignleft">&nbsp;
					<input type="hidden" name="action" value="delete_transients_with_expiration" />
					<input type="hidden" name="transient" value="all" />
					<?php wp_nonce_field( 'transient_manager' ); ?>
					<input type="submit" class="button secondary" value="<?php _e( 'Delete Transients with an Expiration', 'transients-manager' ); ?>" />
				</form>

				<form method="post" class="alignleft">&nbsp;
					<input type="hidden" name="action" value="delete_all_transients" />
					<input type="hidden" name="transient" value="all" />
					<?php wp_nonce_field( 'transient_manager' ); ?>
					<input type="submit" class="button secondary" value="<?php _e( 'Delete All Transients', 'transients-manager' ); ?>" />
				</form>

				<form method="get">
					<p class="search-box">
						<button style="margin-left: 6px;" class="alignright button-secondary" onclick="window.location.reload();"><?php _e( 'Refresh', 'transients-manager' ); ?></button>
						<input type="hidden" name="page" value="pw-transients-manager"/>
						<label class="screen-reader-text" for="transient-search-input"><?php _e( 'Search', 'transients-manager' ); ?></label>
						<input type="search" id="transient-search-input" name="s" value="<?php echo esc_attr( $search ); ?>"/>
						<input type="submit" class="button-secondary" value="<?php _e( 'Search Transients', 'transients-manager' ); ?>"/>
					</p>
				</form>

				<form method="post">
					<div class="tablenav top">
						<div class="alignleft actions bulkactions">
							<input type="hidden" name="action" value="delete_selected_transients" />
							<input type="hidden" name="transient" value="all" />
							<label for="bulk-action-selector-top" class="screen-reader-text">Select Bulk action</label>
							<?php wp_nonce_field( 'transient_manager' ); ?>
							<input type="submit" class="button secondary" value="<?php _e( 'Delete Selected Transients', 'transients-manager' ); ?>" />
						</div>
						<div class="tablenav-pages">
							<span class="displaying-num"><?php printf( _n( '%d Transient', '%d Transients', $count, 'transients-manager' ), $count ); ?></span>
							<span class="pagination-links"><?php echo $pagination; ?></span>
						</div>
						<br class="clear">
					</div>
					<table class="wp-list-table widefat fixed posts striped">
						<thead>
							<tr>
								<td id="cb" class="manage-column column-cb check-column">
									<label for="cb-select-all-<?php echo $page; ?>" class="screen-reader-text">Select All</label>
									<input type="checkbox" id="cb-select-all-<?php echo $page; ?>">
								</td>
								<th style="width:40px;"><?php _e( 'ID', 'transients-manager' ); ?></th>
								<th><?php _e( 'Name', 'transients-manager' ); ?></th>
								<th><?php _e( 'Value', 'transients-manager' ); ?></th>
								<th><?php _e( 'Expires In', 'transients-manager' ); ?></th>
								<th><?php _e( 'Actions', 'transients-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if( $transients ) : ?>

								<?php foreach( $transients as $transient ) :

									$delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete_transient', 'transient' => $this->get_transient_name( $transient ), 'name' => $transient->option_name ) ), 'transient_manager' );
									$edit_url   = add_query_arg( array( 'action' => 'edit_transient', 'trans_id' => $transient->option_id ) );
									?>

									<tr>
										<th id="cb" class="manage-column column-cb check-column">
											<label for="cb-select-<?php echo $page; ?>" class="screen-reader-text">Select <?php echo $this->get_transient_name( $transient ); ?></label>
											<input type="checkbox" id="cb-select-<?php echo $transient->option_id; ?>" name="transients[]" value="<?php echo $transient->option_id; ?>">
										</th>
										<td><?php echo $transient->option_id; ?></td>
										<td><?php echo $this->get_transient_name( $transient ); ?></td>
										<td><?php echo $this->get_transient_value( $transient ); ?></td>
										<td><?php echo $this->get_transient_expiration( $transient ); ?></td>
										<td>
											<a href="<?php echo esc_url( $edit_url ); ?>" class="edit"><?php _e( 'Edit', 'transients-manager' ); ?></a>
											<span> | </span>
											<a href="<?php echo esc_url( $delete_url ); ?>" class="delete" style="color:#a00;"><?php _e( 'Delete', 'transients-manager' ); ?></a>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr><td colspan="5"><?php _e( 'No transients found', 'transients-manager' ); ?></td>
							<?php endif; ?>
						</tbody>
					</table>
				<?php if ( $pages > 1 ) : ?>
					<div class="tablenav bottom">
						<div class="tablenav-pages">
							<span class="displaying-num"><?php printf( _n( '%d Transient', '%d Transients', $count, 'transients-manager' ), $count ); ?></span>
							<span class="pagination-links"><?php echo $pagination; ?></span>
						</div>
					</div><!--end .tablenav-->
				<?php endif; ?>
				</form>
			<?php endif; ?>

		</div>
<?php

	}

	/**
	 * Add toolbar node for suspending transients
	 *
	 * @access  public
	 * @return  void
	 * @since   1.6
	*/
	public function suspend_transients_button( $wp_admin_bar ) {

		if ( ! current_user_can( 'manage_options' ) ) {
		    return;
		}

		$action = get_option( 'pw_tm_suspend' ) ? 'unsuspend_transients' : 'suspend_transients';
		$label  = get_option( 'pw_tm_suspend' ) ? '<span style="color: red;">' . __( 'Unsuspend Transients', 'transients-manager' ) . '</span>' : __( 'Suspend Transients', 'transients-manager' );

		$args = array(
			'id'     => 'tm-suspend',
			'title'  => $label,
			'parent' => 'top-secondary',
			'href'   => wp_nonce_url( add_query_arg( array( 'action' => $action ) ), 'transient_manager' )
		);
		$wp_admin_bar->add_node( $args );

	}

	/**
	 * Retrieve transients from the database
	 *
	 * @access  private
	 * @return  array
	 * @since   1.0
	*/
	private function get_transients( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'offset' => 0,
			'number' => 30,
			'search' => ''
		);

		$args       = wp_parse_args( $args, $defaults );
		$cache_key  = md5( serialize( $args ) );
		$transients = wp_cache_get( $cache_key );

		if( false === $transients ) {

			$sql = "SELECT * FROM $wpdb->options WHERE option_name LIKE '%\_transient\_%' AND option_name NOT LIKE '%\_transient\_timeout%'";

			if( ! empty( $args['search'] ) ) {

				$search  = esc_sql( $args['search'] );
				$sql    .= " AND option_name LIKE '%{$search}%'";

			}

			$offset = absint( $args['offset'] );
			$number = absint( $args['number'] );
			$sql .= " ORDER BY option_id DESC LIMIT $offset,$number;";

			$transients = $wpdb->get_results( $sql );

			wp_cache_set( $cache_key, $transients, '', 3600 );

		}

		return $transients;

	}

	/**
	 * Retrieve the total number transients in the database
	 *
	 * If a search is performed, it returns the number of found results
	 *
	 * @access  private
	 * @return  int
	 * @since   1.0
	*/
	private function get_total_transients( $search = '' ) {

		global $wpdb;

		if( ! empty( $search ) ) {

			$count = wp_cache_get( 'pw_transients_count_' . sanitize_key( $search ) );

			if( false === $count ) {
				$search     = esc_sql( $search );
				$count = $wpdb->get_var( "SELECT count(option_id) FROM $wpdb->options WHERE option_name LIKE '%\_transient\_%' AND option_name NOT LIKE '%\_transient\_timeout%' AND option_name LIKE '%{$search}%'" );
				wp_cache_set( 'pw_transients_' . sanitize_key( $search ), $count, '', 3600 );
			}

		} else {

			$count = wp_cache_get( 'pw_transients_count' );

			if( false === $count ) {

				$count = $wpdb->get_var( "SELECT count(option_id) FROM $wpdb->options WHERE option_name LIKE '%\_transient\_%' AND option_name NOT LIKE '%\_transient\_timeout%'" );
				wp_cache_set( 'pw_transients_count', $count, '', 3600 );
			}

		}

		return $count;

	}

	/**
	 * Retrieve a transient by its ID
	 *
	 * @access  private
	 * @return  object
	 * @since   1.0
	*/
	private function get_transient_by_id( $id = 0 ) {

		global $wpdb;

		$id = absint( $id );

		if( empty( $id ) ) {
			return false;
		}

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->options WHERE option_id = %d", $id ) );

	}

	/**
	 * Retrieve the transient name from the transient object
	 *
	 * @access  private
	 * @return  string
	 * @since   1.0
	*/
	private function get_transient_name( $transient ) {
		$length = false !== strpos( $transient->option_name, 'site_transient_' ) ? 16 : 11;
		return substr( $transient->option_name, $length, strlen( $transient->option_name ) );
	}

	/**
	 * Retrieve the human-friendly transient value from the transient object
	 *
	 * @access  private
	 * @return  string/int
	 * @since   1.0
	*/
	private function get_transient_value( $transient ) {

		$value = maybe_unserialize( $transient->option_value );

		if( is_array( $value ) ) {

			$value = 'array';

		} elseif( gettype( $value ) == 'object' ) {

			$value = 'object';

		}

		return wp_trim_words( $value, 5 );

	}

	/**
	 * Retrieve the expiration timestamp
	 *
	 * @access  private
	 * @return  int
	 * @since   1.0
	*/
	private function get_transient_expiration_time( $transient ) {

		if( false !== strpos( $transient->option_name, 'site_transient_' ) ) {

			$time = get_option( '_site_transient_timeout_' . $this->get_transient_name( $transient ) );

		} else {

			$time = get_option( '_transient_timeout_' . $this->get_transient_name( $transient ) );

		}

		return $time;

	}

	/**
	 * Retrieve the human-friendly expiration time
	 *
	 * @access  private
	 * @return  string
	 * @since   1.0
	*/
	private function get_transient_expiration( $transient ) {

		$time_now   = time();
		$expiration = $this->get_transient_expiration_time( $transient );

		if( empty( $expiration ) ) {
			return __( 'Does not expire', 'transients-manager' );
		}

		if( $time_now > $expiration ) {
			return __( 'Expired', 'transients-manager' );
		}
		return human_time_diff( $time_now, $expiration );

	}

	/**
	 * Process delete and update actions
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0
	*/
	public function process_actions() {

		if( empty( $_REQUEST['action'] ) ) {
			return;
		}

		if( empty( $_REQUEST['transient'] ) && ( 'suspend_transients' !== $_REQUEST['action'] && 'unsuspend_transients' !== $_REQUEST['action'] ) ) {
			return;
		}

		if( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if( ! wp_verify_nonce( $_REQUEST['_wpnonce'] , 'transient_manager' ) ) {
			return;
		}

		if( 'suspend_transients' !== $_REQUEST['action'] && 'unsuspend_transients' !== $_REQUEST['action'] ) {

			$search    = ! empty( $_REQUEST['s'] ) ? urlencode( $_REQUEST['s'] ) : '';
			$transient = $_REQUEST['transient'];
			$site_wide = isset( $_REQUEST['name'] ) && false !== strpos( $_REQUEST['name'], '_site_transient' );

		}

		switch( $_REQUEST['action'] ) {

			case 'delete_transient' :
				$this->delete_transient( $transient, $site_wide );
				wp_safe_redirect( admin_url( 'tools.php?page=pw-transients-manager&s=' . $search ) ); exit;
				break;

			case 'update_transient' :
				$this->update_transient( $transient, $site_wide );
				wp_safe_redirect( admin_url( 'tools.php?page=pw-transients-manager&s=' . $search ) ); exit;
				break;

			case 'delete_selected_transients' :
				$this->delete_selected_transients();
				wp_safe_redirect( admin_url( 'tools.php?page=pw-transients-manager' ) ); exit;
				break;

			case 'delete_expired_transients' :
				$this->delete_expired_transients();
				wp_safe_redirect( admin_url( 'tools.php?page=pw-transients-manager' ) ); exit;
				break;

			case 'delete_transients_with_expiration' :
				$this->delete_transients_with_expirations();
				wp_safe_redirect( admin_url( 'tools.php?page=pw-transients-manager' ) ); exit;
				break;

			case 'suspend_transients' :

				update_option( 'pw_tm_suspend', 1 );
				wp_safe_redirect( remove_query_arg( array( 'action', '_wpnonce' ) ) ); exit;
				break;

			case 'unsuspend_transients' :

				delete_option( 'pw_tm_suspend', 1 );
				wp_safe_redirect( remove_query_arg( array( 'action', '_wpnonce' ) ) ); exit;
				break;

			case 'delete_all_transients' :

				$this->delete_all_transients();
				wp_safe_redirect( admin_url( 'tools.php?page=pw-transients-manager' ) ); exit;
				break;

		}

	}

	/**
	 * Delete a transient by name
	 *
	 * @access  private
	 * @return  bool
	 * @since   1.0
	*/
	private function delete_transient( $transient = '', $site_wide = false ) {

		if( empty( $transient ) ) {
			return false;
		}

		if( false !== $site_wide ) {

			return delete_site_transient( $transient );

		} else {

			return delete_transient( $transient );

		}

	}

	/**
	 * Bulk delete function
	 *
	 * @access  private
	 * @return  bool
	 * @since   1.5
	*/
	private function bulk_delete_transients( $transients = array() ) {

		if( empty( $transients ) ) {
			return false;
		}

		foreach( $transients as $transient ) {

			$site_wide = ( strpos( $transient, '_site_transient' ) !== false );
			$name = str_replace( $site_wide ? '_site_transient_timeout_' : '_transient_timeout_', '', $transient );
			$this->delete_transient( $name, $site_wide );

		}

		return true;

	}

	/**
	 * Delete Selected transients.
	 *
	 * @access  public
	 * @return  false|int
	 * @since   1.8
	 */
	public function delete_selected_transients() {
		global $wpdb;
		if ( ! empty( $_REQUEST['transients'] ) && is_array( $_REQUEST['transients'] ) ) {

			$transients_ids_filtered = array_map(
				function( $transient_id ) {
					return absint( $transient_id );
				},
				$_REQUEST['transients']
			);

			if ( ! empty( $transients_ids_filtered ) ) {
				$placeholders = array_fill( 0, count( $transients_ids_filtered ), '%d' );
				$format       = implode( ', ', $placeholders );
				$count        = $wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->options WHERE option_id IN ($format)", $transients_ids_filtered ) );
				return $count;
			}
		}
		return 0;
	}

	/**
	 * Delete all expired transients
	 *
	 * @access  public
	 * @return  bool
	 * @since   1.1
	*/
	public function delete_expired_transients() {

		global $wpdb;

		$time_now = time();
		$expired  = $wpdb->get_col( "SELECT option_name FROM $wpdb->options where option_name LIKE '%_transient_timeout_%' AND option_value+0 < $time_now" );


		return $this->bulk_delete_transients( $expired );

	}

	/**
	 * Delete all transients with expiration
	 *
	 * @access  public
	 * @return  bool
	 * @since   1.2
	*/
	public function delete_transients_with_expirations() {

		global $wpdb;

		$will_expire = $wpdb->get_col( "SELECT option_name FROM $wpdb->options where option_name LIKE '%_transient_timeout_%'" );

		return $this->bulk_delete_transients( $will_expire );

	}

	/**
	 * Delete all transients
	 *
	 * @access public
	 * @return false|int
	 */
	public function delete_all_transients() {

		global $wpdb;

		$count = $wpdb->query(
			"DELETE FROM $wpdb->options
			WHERE option_name LIKE '\_transient\_%'"
		);

		return $count;
	}

	/**
	 * Update an existing transient
	 *
	 * @access  private
	 * @return  bool
	 * @since   1.0
	*/
	private function update_transient( $transient = '', $site_wide = false ) {

		if( empty( $transient ) ) {
			return false;
		}

		$value      = sanitize_text_field( $_POST['value'] );
		$expiration = sanitize_text_field( $_POST['expires'] );
		$expiration = $expiration - time();

		if( false !== $site_wide ) {

			return set_site_transient( $transient, $value, $expiration );

		} else {

			return set_transient( $transient, $value, $expiration );

		}

	}

	/**
	 * Prevent transient from being updated if transients are suspended
	 *
	 * @access  public
	 * @return  bool
	 * @since   1.6
	*/
	public function maybe_block_update_transient( $value, $option, $old_value ) {

		if( ! get_option( 'pw_tm_suspend' ) ) {
			return $value;
		}

		if( false === strpos( $option, '_transient' ) ) {
			return $value;
		}

		return false;

	}

	/**
	 * Prevent transient from being updated if transients are suspended
	 *
	 * @access  public
	 * @return  bool
	 * @since   1.6
	*/
	public function maybe_block_set_transient( $option, $value ) {

		if( ! get_option( 'pw_tm_suspend' ) ) {
			return;
		}

		if( false === strpos( $option, '_transient' ) ) {
			return;
		}

		delete_option( $option );

	}

}
$tm = new PW_Transients_Manager;
unset( $tm );