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

		$this->link    = '<a target="_BLANK" href="https://downloads.wordpress.org/plugin/download-monitor.zip">' . esc_html( 'here', 'download-monitor' ) . '</a>';
		$this->contact = '<a target="_BLANK" href="https://www.download-monitor.com/contact/">' . esc_html( 'contact us form', 'download-monitor' ) . '</a>';

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
		?>
		<div data-dismissible="download-monitor-beta-notice" id="download-monitor-beta-notice" class="notice notice-success is-dismissible" style="margin-top:30px;">
			<h1><?php echo $this->messages['headling']; ?></h1>
			<p><?php echo sprintf( wp_kses_post( $this->messages['notice'] ), wp_kses_post( $this->link ), wp_kses_post( $this->contact ) ); ?></p>
			<?php
			if ( ! empty( $this->messages['changelog'] ) ) {
				echo '<h3>' . $this->messages['changelog_title'] . '</h3>';
				echo '<ul>';
				foreach ( $this->messages['changelog'] as $item ) {
					echo '<li><span class="dashicons dashicons-yes"></span> ' . $item . '</li>';
				}
				echo '</ul>';

			}
			?>
		</div>
		<?php
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
			jQuery( document ).ready( function( $ ){

				$(document).on('click','#download-monitor-beta-notice .notice-dismiss', function( ){
					var data = {
						action: 'download-monitor_beta_test_notice_dismiss',
						security: '<?php echo $ajax_nonce; ?>',
					};

					$.post( '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', data, function( response ) {
						$( '#download-monitor-beta-notice' ).slideUp( 'fast', function() {
							$( this ).remove();
						} );
					});

				} );

			});
		</script>

		<?php
	}
}
