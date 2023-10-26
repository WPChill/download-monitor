<?php

namespace WPChill\DownloadMonitor\Shop\Admin\Fields;

class GatewayOverview extends \DLM_Admin_Fields_Field {

	/** @var \WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PaymentGateway[] */
	private $gateways;

	/**
	 * GatewayOverview constructor.
	 *
	 * @param \WPChill\DownloadMonitor\Shop\Checkout\PaymentGateway\PaymentGateway[] $gateways
	 */
	public function __construct( $gateways ) {
		$this->gateways = $gateways;
		parent::__construct( '', '', '' );
	}

	/**
	 * Renders field
	 *
	 */
	public function render() {

		$gateways = $this->gateways;

		if ( ! empty( $gateways ) ) : ?>
            <ul>
				<?php foreach ( $gateways as $gateway ) : ?>
					<?php
					$checkbox_name = "dlm_gateway_" . $gateway->get_id() . "_enabled";
					$is_checked   = ( $gateway->is_enabled() ? ' checked="checked"' : '' );
					?>
                    <li>
	                    <div class='wpchill-toggle'>
		                    <input type='checkbox' class='wpchill-toggle__input' name="<?php echo esc_attr( $checkbox_name ); ?>"
		                           id="<?php echo esc_attr( $checkbox_name ); ?>"
		                           value='1'<?php echo esc_attr( $is_checked ); ?>/>
		                    <div class="wpchill-toggle__items">
			                    <span class="wpchill-toggle__track"></span>
			                    <span class="wpchill-toggle__thumb"></span>
			                    <svg class="wpchill-toggle__off" width="6" height="6" aria-hidden="true" role="img"
			                         focusable="false" viewBox="0 0 6 6">
				                    <path
					                    d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path>
			                    </svg>
			                    <svg class="wpchill-toggle__on" width="2" height="6" aria-hidden="true" role="img"
			                         focusable="false" viewBox="0 0 2 6">
				                    <path d="M0 0h2v6H0z"></path>
			                    </svg>
		                    </div>
	                    </div>
                        <label for="<?php echo esc_attr( $checkbox_name ); ?>"><?php echo esc_html( $gateway->get_title() ); ?></label>
                    </li>
				<?php endforeach; ?>
            </ul>
		<?php endif; ?>
		<?php
	}
}