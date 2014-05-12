<?php
/**
 * Plugin Name: Transients Manager
 * Plugin URL: http://pippinsplugins.com/transients-manager
 * Description: Provides a UI to manage your site's transients. You can view, search, edit, and delete transients at will.
 * Version: 1.0.1
 * Author: Pippin Williamson
 * Author URI: http://pippinsplugins.com
 * Contributors: mordauk
*/

class PW_Transients_Manager {

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function __construct() {

		add_action( 'admin_init', array( $this, 'text_domain' ) );
		add_action( 'admin_menu', array( $this, 'tools_link' ) );
		add_action( 'admin_init', array( $this, 'process_actions' ) );

	}

	/**
	 * Load our plugin's text domain
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function text_domain() {

		// Set filter for plugin's languages directory
		$lang_dir      = dirname( plugin_basename( __FILE__ ) ) . '/languages/';

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale',  get_locale(), 'pw-transients-manager' );
		$mofile        = sprintf( '%1$s-%2$s.mo', 'pw-transients-manager', $locale );

		// Setup paths to current locale file
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/pw-transients-manager/' . $mofile;

		if ( file_exists( $mofile_global ) ) {

			// Look in global /wp-content/languages/pw-transients-manager folder
			load_textdomain( 'pw-transients-manager', $mofile_global );

		} elseif ( file_exists( $mofile_local ) ) {

			// Look in local /wp-content/plugins/transients-manager/languages/ folder
			load_textdomain( 'pw-transients-manager', $mofile_local );

		} else {

			// Load the default language files
			load_plugin_textdomain( 'pw-transients-manager', false, $lang_dir );

		}

	}

	/**
	 * Register our menu link under Tools
	 *
	 * @access  public
	 * @since   1.0
	*/
	public function tools_link() {

		add_management_page(
			__( 'Transients Manager', 'pw-transients-manager' ),
			__( 'Transients', 'pw-transients-manager' ),
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
		$page        = isset( $_GET['p'] )   ? absint( $_GET['p'] )              : 1;
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
			'base'   => 'admin.php?' . remove_query_arg( 'p', $_SERVER['QUERY_STRING'] ) . '%_%',
			'format' => '&p=%#%',
			'total'  => $pages,
			'current'=> $page
		));

		$transients = $this->get_transients( $args );

?>
		<div class="wrap">

			<?php if( ! empty( $_GET['action'] ) && 'edit_transient' == $_GET['action'] ) : ?>

				<h2><?php _e( 'Edit Transient', 'pw-transients-manager' ); ?></h2>

				<?php $transient = $this->get_transient_by_id( absint( $_GET['trans_id'] ) ); ?>

				<form method="post">
					<table class="form-table">
						<tbody>
							<tr>
								<th><?php _e( 'Name', 'pw-transients-manager' ); ?></th>
								<td><input type="text" class="large-text" name="name" value="<?php echo esc_attr( $transient->option_name ); ?>" /></td>
							</tr>
							<tr>
								<th><?php _e( 'Expires In', 'pw-transients-manager' ); ?></th>
								<td><input type="text" class="large-text" name="expires" value="<?php echo $this->get_transient_expiration_time( $transient ); ?>"/>
							</tr>
							<tr>
								<th><?php _e( 'Value', 'pw-transients-manager' ); ?></th>
								<td><textarea class="large-text" name="value" rows="10" cols="50"><?php echo esc_textarea( $transient->option_value ); ?></textarea></td>
							</tr>
					</table>
					<input type="hidden" name="transient" value="<?php echo esc_attr( $this->get_transient_name( $transient ) ); ?>"/>
					<input type="hidden" name="action" value="update_transient"/>
					<?php wp_nonce_field( 'transient_manager' ); ?>
					<?php submit_button(); ?>
				</form>

			<?php else : ?>

				<h2><?php _e( 'Transients', 'pw-transients-manager' ); ?></h2>

				<form method="get">
					<p class="search-box">
						<input type="hidden" name="page" value="pw-transients-manager"/>
						<label class="screen-reader-text" for="transient-search-input"><?php _e( 'Search', 'pw-transients-manager' ); ?></label>
						<input type="search" id="transient-search-input" name="s" value="<?php echo esc_attr( $search ); ?>"/>
						<input type="submit" class="button-secondary" value="<?php _e( 'Search Transients', 'pw-transients-manager' ); ?>"/>
					</p>
				</form>

				<div class="tablenav top">
					<div class="tablenav-pages">
						<span class="displaying-num"><?php printf( _n( '%d Transient', '%d Transients', $count, 'pw-transients-manager' ), $count ); ?></span>
						<span class="pagination-links"><?php echo $pagination; ?></span>
					</div>
				</div>

				<table class="wp-list-table widefat fixed posts">
					<thead>
						<tr>
							<th style="width:40px;"><?php _e( 'ID', 'pw-transients-manager' ); ?></th>
							<th><?php _e( 'Name', 'pw-transients-manager' ); ?></th>
							<th><?php _e( 'Value', 'pw-transients-manager' ); ?></th>
							<th><?php _e( 'Expires In', 'pw-transients-manager' ); ?></th>
							<th><?php _e( 'Actions', 'pw-transients-manager' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if( $transients ) : ?>
							<?php foreach( $transients as $transient ) :

								$delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'delete_transient', 'transient' => $this->get_transient_name( $transient ) ) ), 'transient_manager' );
								$edit_url   = add_query_arg( array( 'action' => 'edit_transient', 'trans_id' => $transient->option_id ) );
								?>

								<tr>
									<td><?php echo $transient->option_id; ?></td>
									<td><?php echo $this->get_transient_name( $transient ); ?></td>
									<td><?php echo $this->get_transient_value( $transient ); ?></td>
									<td><?php echo $this->get_transient_expiration( $transient ); ?></td>
									<td>
										<a href="<?php echo esc_url( $edit_url ); ?>" class="edit"><?php _e( 'Edit', 'pw-transients-manager' ); ?></a>
										<span> | </span> 
										<a href="<?php echo esc_url( $delete_url ); ?>" class="delete" style="color:#a00;"><?php _e( 'Delete', 'pw-transients-manager' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr><td colspan="5"><?php _e( 'No transients found', 'pw-transients-manager' ); ?></td>
						<?php endif; ?>
					</tbody>
				</table>
				<?php if ( $pages > 1 ) : ?>
					<div class="tablenav bottom">
						<div class="tablenav-pages">
							<span class="displaying-num"><?php printf( _n( '%d Transient', '%d Transients', $count, 'pw-transients-manager' ), $count ); ?></span>
							<span class="pagination-links"><?php echo $pagination; ?></span>
						</div>
					</div><!--end .tablenav-->
				<?php endif; ?>

			<?php endif; ?>

		</div>
<?php

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

			$sql = "SELECT * FROM $wpdb->options WHERE option_name LIKE '_transient_%' AND option_name NOT LIKE '_transient_timeout%'";

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
				$count = $wpdb->get_var( "SELECT count(option_id) FROM $wpdb->options WHERE option_name LIKE '_transient_%' AND option_name NOT LIKE '_transient_timeout%' AND option_name LIKE '%{$search}%'" );
				wp_cache_set( 'pw_transients_' . sanitize_key( $search ), $count, '', 3600 );
			}

		} else {

			$count = wp_cache_get( 'pw_transients_count' );

			if( false === $count ) {

				$count = $wpdb->get_var( "SELECT count(option_id) FROM $wpdb->options WHERE option_name LIKE '_transient_%' AND option_name NOT LIKE '_transient_timeout%'" );
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
		return substr( $transient->option_name, 11, strlen( $transient->option_name ) );
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

		} elseif( is_object( $value ) ) {

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

		return get_option( '_transient_timeout_' . $this->get_transient_name( $transient ) );

	}

	/**
	 * Retrieve the human-friendly expiration time
	 *
	 * @access  private
	 * @return  string
	 * @since   1.0
	*/
	private function get_transient_expiration( $transient ) {

		$time_now   = current_time( 'timestamp' );
		$expiration = $this->get_transient_expiration_time( $transient );
		if( $time_now > $expiration ) {
			return __( 'Expired', 'pw-transients-manager' );
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

		if( empty( $_REQUEST['transient'] ) ) {
			return;
		}

		if( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if( ! wp_verify_nonce( $_REQUEST['_wpnonce'] , 'transient_manager' ) ) {
			return;
		}

		$search = ! empty( $_REQUEST['s'] ) ? urlencode( $_REQUEST['s'] ) : '';

		switch( $_REQUEST['action'] ) {

			case 'delete_transient' :
				$this->delete_transient( $_REQUEST['transient'] );
				wp_safe_redirect( admin_url( 'tools.php?page=pw-transients-manager&s=' . $search ) ); exit;
				break;

			case 'update_transient' :
				$this->update_transient( $_REQUEST['transient'] );
				wp_safe_redirect( admin_url( 'tools.php?page=pw-transients-manager&s=' . $search ) ); exit;
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
	private function delete_transient( $transient = '' ) {

		if( empty( $transient ) ) {
			return false;
		}

		return delete_transient( $transient );

	}

	/**
	 * Update an existing transient
	 *
	 * @access  private
	 * @return  bool
	 * @since   1.0
	*/
	private function update_transient( $transient = '' ) {

		if( empty( $transient ) ) {
			return false;
		}

		$value      = sanitize_text_field( $_POST['value'] );
		$expiration = sanitize_text_field( $_POST['expires'] );
		$expiration = $expiration - current_time( 'timestamp' );

		return set_transient( $transient, $value, $expiration );

	}

}
new PW_Transients_Manager;