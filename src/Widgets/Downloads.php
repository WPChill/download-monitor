<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Widget_Downloads class.
 *
 * @extends WP_Widget
 */
class DLM_Widget_Downloads extends WP_Widget {

	var $widget_cssclass;
	var $widget_description;
	var $widget_idbase;
	var $widget_name;

	/**
	 * constructor
	 *
	 * @access public
	 */
	public function __construct() {

		/* Widget variable settings. */
		$this->widget_cssclass    = 'dlm_widget_downloads';
		$this->widget_description = __( 'Display a list of your downloads.', 'download-monitor' );
		$this->widget_idbase      = 'dlm_widget_downloads';
		$this->widget_name        = __( 'Downloads List', 'download-monitor' );

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->widget_cssclass, 'description' => $this->widget_description );

		/* Create the widget. */
		parent::__construct( 'dlm_widget_downloads', $this->widget_name, $widget_ops );
	}

	/**
	 * widget function.
	 *
	 * @param array $args
	 * @param array $instance
	 *
	 * @return void
	 * @see WP_Widget
	 * @access public
	 *
	 */
	public function widget( $args, $instance ) {

		// Extract the arguments
		extract( $args );

		$title          = isset( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) : __( 'Featured Downloads', 'download-monitor' );
		$posts_per_page = isset( $instance['posts_per_page'] ) ? absint( $instance['posts_per_page'] ) : 10;
		$format         = ! empty( $instance['format'] ) ? sanitize_title( $instance['format'] ) : dlm_get_default_download_template();
		$orderby        = isset( $instance['orderby'] ) ? $instance['orderby'] : 'title';
		$order          = isset( $instance['order'] ) ? $instance['order'] : 'ASC';
		$featured       = isset( $instance['featured'] ) ? $instance['featured'] : 'no';
		$members_only   = isset( $instance['members_only'] ) ? $instance['members_only'] : 'no';
		$category       = isset( $instance['category'] ) ? $instance['category'] : 'all';

		$args = array(
			'post_status'   => 'publish',
			'post_type'     => 'dlm_download',
			'no_found_rows' => 1,
			'orderby'       => $orderby,
			'order'         => $order,
			'meta_query'    => array(),
			'tax_query'     => array()
		);

		if ( $orderby == 'download_count' ) {
			$args['order_by_count'] = '1';
		}

		if ( $featured == 'yes' ) {
			$args['meta_query'][] = array(
				'key'   => '_featured',
				'value' => 'yes'
			);
		}

		if ( $members_only == 'yes' ) {
			$args['meta_query'][] = array(
				'key'   => '_members_only',
				'value' => 'yes'
			);
		}

		if ( 'all' !== $category ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'dlm_download_category',
				'field'    => 'slug',
				'terms'    => $category
			);
		}

		// fetch downloads
		$downloads = download_monitor()->service( 'download_repository' )->retrieve( $args, $posts_per_page );

		if ( count( $downloads ) > 0 ) {

			echo wp_kses_post( $before_widget );

			if ( $title ) {
				echo wp_kses_post( $before_title ) . wp_kses_post( $title ) . wp_kses_post( $after_title );
			}

			echo wp_kses_post( apply_filters( 'dlm_widget_downloads_list_start', '<ul class="dlm-downloads">' ) );

			// Template handler
			$template_handler = new DLM_Template_Handler();

			foreach ( $downloads as $download ) {

				echo wp_kses_post( apply_filters( 'dlm_widget_downloads_list_item_start', '<li>' ) );

				if ( $download->has_version() ) {
					$template_handler->get_template_part( 'content-download', $format, '', array( 'dlm_download' => $download ) );
				} else {
					$template_handler->get_template_part( 'content-download', 'no-version', '', array( 'dlm_download' => $download ) );
				}

				echo wp_kses_post( apply_filters( 'dlm_widget_downloads_list_item_end', '</li>' ) );
			}

			echo wp_kses_post( apply_filters( 'dlm_widget_downloads_list_end', '</ul>' ) );

			echo wp_kses_post( $after_widget );
		}
	}

	/**
	 * update function.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @return array
	 * @see WP_Widget->update
	 * @access public
	 *
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                   = $old_instance;
		$instance['title']          = strip_tags( $new_instance['title'] );
		$instance['posts_per_page'] = absint( $new_instance['posts_per_page'] );
		$instance['format']         = sanitize_title( $new_instance['format'] );
		$instance['orderby']        = sanitize_text_field( $new_instance['orderby'] );
		$instance['order']          = sanitize_text_field( $new_instance['order'] );
		$instance['featured']       = isset( $new_instance['featured'] ) ? $new_instance['featured'] : 'no';
		$instance['members_only']   = isset( $new_instance['members_only'] ) ? $new_instance['members_only'] : 'no';
		$instance['category']       = isset( $new_instance['category'] ) ? $new_instance['category'] : 'all';

		return $instance;
	}

	/**
	 * form function.
	 *
	 * @param array $instance
	 *
	 * @return void
	 * @see WP_Widget->form
	 * @access public
	 *
	 */
	public function form( $instance ) {
		$title          = isset( $instance['title'] ) ? $instance['title'] : __( 'Featured Downloads', 'download-monitor' );
		$posts_per_page = isset( $instance['posts_per_page'] ) ? absint( $instance['posts_per_page'] ) : 10;
		$format         = isset( $instance['format'] ) ? sanitize_title( $instance['format'] ) : '';
		$orderby        = isset( $instance['orderby'] ) ? $instance['orderby'] : 'title';
		$order          = isset( $instance['order'] ) ? $instance['order'] : 'ASC';
		$featured       = isset( $instance['featured'] ) ? $instance['featured'] : 'no';
		$members_only   = isset( $instance['members_only'] ) ? $instance['members_only'] : 'no';
		$category       = isset( $instance['category'] ) ? $instance['category'] : 'all';
		$cats           = get_terms( 'dlm_download_category', array( 'hide_empty' => false ) );
		?>
		<p>
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php echo esc_html__( 'Title', 'download-monitor' ); ?>
				:</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
			       value="<?php echo esc_attr( $title ); ?>"/>
		</p>
		<p>
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'posts_per_page' ) ); ?>"><?php echo esc_html__( 'Limit', 'download-monitor' ); ?>
				:</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'posts_per_page' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'posts_per_page' ) ); ?>" type="text"
			       value="<?php echo esc_attr( $posts_per_page ); ?>" size="3"/>
		</p>
		<p>
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'format' ) ); ?>"><?php echo esc_html__( 'Output template', 'download-monitor' ); ?>
				:</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'format' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'format' ) ); ?>" type="text"
			       value="<?php echo esc_attr( $format ); ?>"
			       placeholder="<?php echo esc_attr__( 'Default template', 'download-monitor' ); ?>"/>
		</p>
		<p>
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>"><?php echo esc_html__( 'Order by', 'download-monitor' ); ?>
				:</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>"
			        name="<?php echo esc_attr( $this->get_field_name( 'orderby' ) ); ?>" type="text">
				<option
					value="title" <?php selected( $orderby, 'title' ); ?>><?php echo esc_html__( 'Title', 'download-monitor' ); ?></option>
				<option
					value="rand" <?php selected( $orderby, 'rand' ); ?>><?php echo esc_html__( 'Random', 'download-monitor' ); ?></option>
				<option
					value="ID" <?php selected( $orderby, 'ID' ); ?>><?php esc_html_e( 'ID', 'download-monitor' ); ?></option>
				<option
					value="date" <?php selected( $orderby, 'date' ); ?>><?php echo esc_html__( 'Date added', 'download-monitor' ); ?></option>
				<option
					value="modified" <?php selected( $orderby, 'modified' ); ?>><?php echo esc_html__( 'Date modified', 'download-monitor' ); ?></option>
				<option
					value="download_count" <?php selected( $orderby, 'download_count' ); ?>><?php echo esc_html__( 'Download count', 'download-monitor' ); ?></option>
			</select>
		</p>
		<p>
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>"><?php echo esc_html__( 'Order', 'download-monitor' ); ?>
				:</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>"
			        name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>" type="text">
				<option
					value="ASC" <?php selected( $order, 'ASC' ); ?>><?php echo esc_html__( 'ASC', 'download-monitor' ); ?></option>
				<option
					value="DESC" <?php selected( $order, 'DESC' ); ?>><?php echo esc_html__( 'DESC', 'download-monitor' ); ?></option>
			</select>
		</p>
		<p>
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"><?php echo esc_html__( 'Category', 'download-monitor' ); ?>
				:</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"
			        name="<?php echo esc_attr( $this->get_field_name( 'category' ) ); ?>" type="text">
				<option
					value="all" <?php selected( $category, 'all' ); ?>><?php echo esc_html__( 'All', 'download-monitor' ); ?></option>
				<?php
				if ( ! empty( $cats ) ) {
					foreach ( $cats as $cat ) {
						?>
						<option
							value="<?php echo esc_attr( $cat->slug ); ?>>" <?php selected( $category, $cat->slug ); ?>><?php echo esc_html( $cat->name ); ?></option>
						<?php
					}
				}
				?>
			</select>
		</p>
		<p>
			<input id="<?php echo esc_attr( $this->get_field_id( 'featured' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'featured' ) ); ?>"
			       type="checkbox" <?php checked( $featured, 'yes' ); ?> value="yes"/>
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'featured' ) ); ?>"><?php echo esc_html__( 'Show only featured downloads', 'download-monitor' ); ?></label>
		</p>
		<p>
			<input id="<?php echo esc_attr( $this->get_field_id( 'members_only' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'members_only' ) ); ?>"
			       type="checkbox" <?php checked( $members_only, 'yes' ); ?> value="yes"/>
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'members_only' ) ); ?>"><?php echo esc_html__( 'Show only members only downloads', 'download-monitor' ); ?></label>
		</p>
		<?php
	}
}
