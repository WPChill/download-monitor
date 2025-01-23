<?php
/**
 * DLM_Beta_Testers - Used to display a notice for BETA testers need
 * 
 * @since 4.5.93
 */
class DLM_Beta_Testers {

	private $messages;
	private $link;
	private $contact;

	/**
	 * Class constructor
	 */
	public function __construct() {

		$this->messages = array(
			'headling'        => esc_html__( 'Download Monitor - BETA testers - needed!', 'download-monitor' ),
			'notice'          => __( "<p> We've been working (hard!) on Download Monitor 4.6.0 which comes with a ton of improvements. We need hlep testing it out to make sure we don't break anything.</p><p> Just click on this link %1\$s, download and install Download Monitor 4.6.0 and test for issues. Please report any issue found back to us via: %2\$s.</p>", 'download-monitor' ),
			'changelog_title' => esc_html__( 'New features in this version:', 'download-monitor' ),
			'changelog'       => array(
				'custom tables for Reports (should be blazing fast now)',
				'new way to handle downloads (we\'re using a browser-native way of handling downloads vs doing it via htaccess / nginx rules)',
				'a LOT of smaller bug fixes under the hood',
			),
		);

		$this->link    = '<a target="_BLANK" href="https://downloads.wordpress.org/plugin/download-monitor.zip">' . esc_html__( 'here', 'download-monitor' ) . '</a>';
		$this->contact = '<a target="_BLANK" href="https://www.download-monitor.com/contact/">' . esc_html__( 'contact us form', 'download-monitor' ) . '</a>';

		add_action( 'init', array( $this, 'init' ) );

	}

	/**
	 * Init
	 * 
	 * @since 4.5.93
	 */
	public function init() {
		
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'wp_ajax_download-monitor_beta_test_notice_dismiss', array( $this, 'ajax' ) );
		add_action( 'admin_notices', array( $this, 'beta_testers_needed_notice' ), 8 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'ajax_script' ) );
		add_filter( 'dlm_uninstall_db_options', array( $this, 'uninstall_options' ) );

	}

	/**
	 * BETA testers notice
	 * 
	 * @since 4.5.93
	 */
	public function beta_testers_needed_notice() {
		if ( get_option( 'download-monitor-hide-beta-notice', false ) ) {
			return;
		}
		$notice = array(
			'title'   => $this->messages['headling'],
			'message' => $this->messages['notice'],
			'status'  => 'success',
			'source'  => array(
				'slug' => 'download-monitor',
				'name' => 'Download Monitor',
			),
			'dismiss' => true,
			'callback' => 'dismissBetaTesterNotice',
		);

		WPChill_Notifications::add_notification( 'download-monitor-beta-notice', $notice );
	}

	/**
	 * Add our options to the uninstall list
	 * 
	 * @param $options
	 *
	 * @return mixed
	 *
	 * @since 4.5.93
	 */
	public function uninstall_options( $options ) {

		$options[] = 'download-monitor-hide-beta-notice';

		return $options;
	}


	/**
	 * AJAX functions
	 * 
	 * @since 4.5.93
	 */
	public function ajax() {

		check_ajax_referer( 'download-monitor-beta-notice', 'security' );
		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to do this.', 'download-monitor' ) );
		}
		update_option( 'download-monitor-hide-beta-notice', true );
		wp_die( 'ok' );
	}

	/**
	 * Enqueue scripts
	 * 
	 * @since 4.5.93
	 */
	public function enqueue() {
		wp_enqueue_script( 'jquery' );
	}

	/**
	 * AJAX script
	 * 
	 * @since 4.5.93
	 */
	public function ajax_script() {

		$ajax_nonce = wp_create_nonce( 'download-monitor-beta-notice' );

		?>
		<script type="text/javascript">

		function dismissBetaTesterNotice( element ) {
			var data = {
				action: 'download-monitor_beta_test_notice_dismiss',
				security: '<?php echo esc_js( $ajax_nonce ); ?>',
			};

			jQuery.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', data );
		}
		</script>

		<?php
	}
}
