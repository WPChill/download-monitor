<?php

class DLM_Review {

	private $value;
	private $messages;
	private $link = 'https://wordpress.org/support/plugin/%s/reviews/#new-post';
	private $slug = 'download-monitor';
	
	function __construct() {

		$this->messages = array(
			'notice'  => esc_html__( "Hi there! Stoked to see you're using Download Monitor for a few days now - hope you like it! And if you do, please consider rating it. It would mean the world to us.  Keep on rocking!", 'download-monitor' ),
			'rate'    => esc_html__( 'Rate the plugin', 'download-monitor' ),
			'rated'   => esc_html__( 'Remind me later', 'download-monitor' ),
			'no_rate' => __( 'Don\'t show again', 'download-monitor' ),
		);

		if ( isset( $args['messages'] ) ) {
			$this->messages = wp_parse_args( $args['messages'], $this->messages );
		}

		add_action( 'init', array( $this, 'init' ) );

	}

	public function init() {
		if ( ! is_admin() ) {
			return;
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

		if ( ! current_user_can('manage_options') ) {
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

		$url = sprintf( $this->link, $this->slug );

		?>
		<div id="<?php echo esc_attr( $this->slug ) ?>-download-monitor-review-notice" class="notice notice-success is-dismissible" style="margin-top:30px;">
			<p><?php echo sprintf( esc_html( $this->messages['notice'] ), esc_attr( $this->value ) ) ; ?></p>
			<p class="actions">
				<a id="download-monitor-rate" href="<?php echo esc_url( $url ) ?>" target="_blank" class="button button-primary download-monitor-review-button">
					<?php echo esc_html( $this->messages['rate'] ); ?>
				</a>
				<a id="download-monitor-later" href="#" style="margin-left:10px" class="download-monitor-review-button"><?php echo esc_html( $this->messages['rated'] ); ?></a>
				<a id="download-monitor-no-rate" href="#" style="margin-left:10px" class="download-monitor-review-button"><?php echo esc_html( $this->messages['no_rate'] ); ?></a>
			</p>
		</div>
		<?php
	}

	public function ajax() {

		check_ajax_referer( 'download-monitor-review', 'security' );

		if ( ! isset( $_POST['check'] ) ) {
			wp_die( 'ok' );
		}

		$time = get_option( 'download-monitor-rate-time' );

		if ( 'download-monitor-rate' == $_POST['check'] ) {
			$time = time() + YEAR_IN_SECONDS * 1;
		}elseif ( 'download-monitor-later' == ['check'] ) {
			$time = time() + WEEK_IN_SECONDS;
		}elseif ( 'download-monitor-no-rate' == $_POST['check'] ) {
			$time = time() + YEAR_IN_SECONDS * 1;
		}
		
		update_option( 'download-monitor-rate-time', $time );
		wp_die( 'ok' );

	}

	public function enqueue() {
		wp_enqueue_script( 'jquery' );
	}

	public function ajax_script() {

		$ajax_nonce = wp_create_nonce( "download-monitor-review" );

		?>

		<script type="text/javascript">
			jQuery( document ).ready( function( $ ){

				$( '.download-monitor-review-button' ).on('click', function( evt ){
					var href = $(this).attr('href'),
						id = $(this).attr('id');

					if ( 'download-monitor-rate' != id ) {
						evt.preventDefault();
					}

					var data = {
						action: 'download-monitor_review',
						security: '<?php echo $ajax_nonce; ?>',
						check: id
					};

					if ( 'download-monitor-rated' === id ) {
						data['download-monitor-review'] = 1;
					}

					$.post( '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', data, function( response ) {
						$( '#<?php echo esc_attr( $this->slug ); ?>-download-monitor-review-notice' ).slideUp( 'fast', function() {
							$( this ).remove();
						} );
					});

				} );

			});
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