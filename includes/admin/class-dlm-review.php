<?php

class DLM_Review {

	private $value;
	private $messages;
	private $link = 'https://wordpress.org/support/plugin/%s/reviews/#new-post';
	private $slug = 'download-monitor';

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {

		if ( ! is_admin() ) {
			return;
		}

		$this->messages = array(
			'notice'  => esc_html__( "Hi there! Stoked to see you're using Download Monitor for a few days now - hope you like it! And if you do, please consider rating it. It would mean the world to us.  Keep on rocking!", 'download-monitor' ),
			'rate'    => esc_html__( 'Rate the plugin', 'download-monitor' ),
			'rated'   => esc_html__( 'Remind me later', 'download-monitor' ),
			'no_rate' => __( 'Don\'t show again', 'download-monitor' ),
		);

		if ( isset( $args['messages'] ) ) {
			$this->messages = wp_parse_args( $args['messages'], $this->messages );
		}

		$this->value = $this->value();

		if ( $this->check() ) {
			add_action( 'admin_notices', array( $this, 'five_star_wp_rate_notice' ), 8 );
			add_action( 'wp_ajax_download-monitor_review', array( $this, 'ajax' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'ajax_script' ) );
		}

		add_filter( 'dlm_uninstall_db_options', array( $this, 'uninstall_options' ) );
	}

	private function check() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		return( time() > $this->value );
	}

	private function value() {

		$value = get_option( 'download-monitor-rate-time', false );
		if ( $value ) {
			return $value;
		}

		$value = time() + DAY_IN_SECONDS;
		update_option( 'download-monitor-rate-time', $value );

		return $value;
	}

	public function five_star_wp_rate_notice() {

		$url            = sprintf( $this->link, $this->slug );
		$url            = apply_filters( 'dlm_review_link', $url );
		$this->messages = apply_filters( 'dlm_review_messages', $this->messages );

		$notice = array(
			'title'       => 'Rate Us',
			'message'     => sprintf( esc_html( $this->messages['notice'] ), esc_html( $this->value ) ),
			'status'      => 'success',
			'dismissible' => false,
			'actions'     => array(
				array(
					'label'   => esc_html( $this->messages['rated'] ),
					'id'      => 'download-monitor-later',
					'class'   => 'download-monitor-review-button',
					'dismiss' => true,
					'callback' => 'handleDlmButtonClick',
				),
				array(
					'label'   => esc_html( $this->messages['no_rate'] ),
					'id'      => 'download-monitor-no-rate',
					'class'   => 'download-monitor-review-button',
					'dismiss' => true,
					'callback' => 'handleDlmButtonClick',
				),
				array(
					'label'   => esc_html( $this->messages['rate'] ),
					'id'      => 'download-monitor-rate',
					'url'     => esc_url( $url ),
					'class'   => 'download-monitor-review-button',
					'variant' => 'primary',
					'target'  => '_BLANK',
					'dismiss' => true,
					'callback' => 'handleDlmButtonClick',
				),
			),
			'source'      => array(
				'slug' => 'download-monitor',
				'name' => 'Download Monitor',
			),
		);

		WPChill_Notifications::add_notification( 'dlm-five-star-rate', $notice );
	}

	public function ajax() {

		check_ajax_referer( 'download-monitor-review', 'security' );
		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to do this', 'download-monitor' ) );
		}

		if ( ! isset( $_POST['check'] ) ) {
			wp_die( 'ok' );
		}

		$time = get_option( 'download-monitor-rate-time' );

		if ( 'download-monitor-rate' === $_POST['check'] || 'download-monitor-no-rate' === $_POST['check'] ) {
			$time = time() + YEAR_IN_SECONDS * 5;
		} else {
			$time = time() + WEEK_IN_SECONDS;
		}

		update_option( 'download-monitor-rate-time', $time );
		wp_die( 'ok' );
	}

	public function enqueue() {
		wp_enqueue_script( 'jquery' );
	}

	public function ajax_script() {

		$ajax_nonce = wp_create_nonce( 'download-monitor-review' );

		?>
		<script type="text/javascript">

		function handleDlmButtonClick( element ) {
			console.error('clicky');
			var data = {
				action: 'download-monitor_review',
				security: '<?php echo esc_js( $ajax_nonce ); ?>',
				check: element.url ? 'download-monitor-rate' : element.id
			};

			jQuery.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', data );
		}
		</script>
		<?php
	}

	/**
	 * @param $options
	 *
	 * @return mixed
	 *
	 * @since 2.51.6
	 */
	public function uninstall_options( $options ) {

		$options[] = 'download-monitor-rate-time';

		return $options;
	}
}
