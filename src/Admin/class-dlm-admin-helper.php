<?php


class DLM_Admin_Helper {

	/**
	 * Holds the class object.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Admin_Helper object.
	 * @since 1.0.0
	 *
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Admin_Helper ) ) {
			self::$instance = new DLM_Admin_Helper();
		}

		return self::$instance;

	}

	/**
	 * Tab navigation display
	 *
	 * @param $tabs
	 * @param $active_tab
	 */
	public static function dlm_tab_navigation( $tabs, $active_tab ) {

		if ( $tabs ) {

			$i = count( $tabs );
			$j = 1;

			foreach ( $tabs as $tab_id => $tab ) {

				$last_tab = ( $i == $j ) ? ' last_tab' : '';
				$active   = ( $active_tab == $tab_id ? ' nav-tab-active' : '' );
				$j ++;

				if ( isset( $tab['url'] ) ) {
					// For Extensions and Gallery list tabs
					$url = $tab['url'];
				} else {
					// For Settings tabs
					$url = admin_url( 'edit.php?post_type=dlm_download&page=download-monitor-settings&tab=' . $tab_id );
				}

				echo '<a href="' . esc_url( $url ) . '" class="nav-tab' . esc_attr( $active ) . esc_attr( $last_tab ) . '" ' . ( isset( $tab['target'] ) ? 'target="' . esc_attr( $tab['target'] ) . '"' : '' ) . '>';

				if ( isset( $tab['icon'] ) ) {
					echo '<span class="dashicons ' . esc_attr( $tab['icon'] ) . '"></span>';
				}

				// For Extensions and Gallery list tabs
				if ( isset( $tab['name'] ) ) {
					echo esc_html( $tab['name'] );
				}

				// For Settings tabs
				if ( isset( $tab['label'] ) ) {
					echo esc_html( $tab['label'] );
				}

				if ( isset( $tab['badge'] ) ) {
					echo '<span class="dlm-badge">' . esc_html( $tab['badge'] ) . '</span>';
				}

				echo '</a>';
			}
		}
	}

	/**
	 * Callback to sort tabs/fields on priority.
	 *
	 * @return bool
	 *
	 */
	public static function sort_data_by_priority( $a, $b ) {
		if ( ! isset( $a['priority'], $b['priority'] ) ) {
			return - 1;
		}
		if ( $a['priority'] == $b['priority'] ) {
			return 0;
		}

		return $a['priority'] < $b['priority'] ? - 1 : 1;
	}

	/**
	 * Checks if this is one of Download Monitor's page or not
	 *
	 * @return bool
	 * 
	 * @since 4.5.4
	 */
	public static function check_if_dlm_page() {

		if ( ! isset( $_GET['post_type'] ) || ( 'dlm_download' !== $_GET['post_type'] && 'dlm_product' !== $_GET['post_type'] ) ) {
			return false;
		}

		return true;
	}

}