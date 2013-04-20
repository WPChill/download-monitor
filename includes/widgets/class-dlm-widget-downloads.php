<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
	 * @return void
	 */
	function DLM_Widget_Downloads() {

		/* Widget variable settings. */
		$this->widget_cssclass    = 'dlm_widget_downloads';
		$this->widget_description = __( 'Display a list of your downloads.', 'download_monitor' );
		$this->widget_idbase      = 'dlm_widget_downloads';
		$this->widget_name        = __( 'Downloads List', 'download_monitor' );

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->widget_cssclass, 'description' => $this->widget_description );

		/* Create the widget. */
		$this->WP_Widget( 'dlm_widget_downloads', $this->widget_name, $widget_ops );
	}

	/**
	 * widget function.
	 *
	 * @see WP_Widget
	 * @access public
	 * @param array $args
	 * @param array $instance
	 * @return void
	 */
	function widget( $args, $instance ) {
		global $download_monitor;

		extract( $args );

		$title          = isset( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) : __( 'Featured Downloads', 'download_monitor' );
		$posts_per_page = isset( $instance['posts_per_page'] ) ? absint( $instance['posts_per_page'] ) : 10;
		$format         = isset( $instance['format'] ) ? sanitize_title( $instance['format'] ) : '';
		$orderby        = isset( $instance['orderby'] ) ? $instance['orderby'] : 'title';
		$order          = isset( $instance['order'] ) ? $instance['order'] : 'ASC';
		$featured       = isset( $instance['featured'] ) ? $instance['featured'] : 'no';
		$members_only   = isset( $instance['members_only'] ) ? $instance['members_only'] : 'no';

    	$args = array(
    		'post_status' 	 => 'publish',
    		'post_type'      => 'dlm_download',
    		'no_found_rows'  => 1,
    		'posts_per_page' => $posts_per_page,
    		'orderby' 		 => $orderby,
    		'order'          => $order,
    		'meta_query'     => array(),
    		'tax_query'      => array()
    	);

    	if ( $orderby == 'download_count' ) {
	    	$args['orderby']  = 'meta_value';
			$args['meta_key'] = '_download_count';
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

		$r = new WP_Query( $args );

		if ( $r->have_posts() ) {

			echo $before_widget;

			if ( $title )
				echo $before_title . $title . $after_title;

			echo apply_filters( 'dlm_widget_downloads_list_start', '<ul class="dlm-downloads">' );

			while ( $r->have_posts()) {
				$r->the_post();

				echo apply_filters( 'dlm_widget_downloads_list_item_start', '<li>' );

				$download_monitor->get_template_part( 'content-download', $format );

				echo apply_filters( 'dlm_widget_downloads_list_item_end', '</li>' );
			}

			echo apply_filters( 'dlm_widget_downloads_list_end', '</ul>' );

			echo $after_widget;
		}
	}

	/**
	 * update function.
	 *
	 * @see WP_Widget->update
	 * @access public
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	function update( $new_instance, $old_instance ) {
		$instance                   = $old_instance;
		$instance['title']          = strip_tags( $new_instance['title'] );
		$instance['posts_per_page'] = absint( $new_instance['posts_per_page'] );
		$instance['format']         = sanitize_title( $new_instance['format'] );
		$instance['orderby']        = sanitize_text_field( $new_instance['orderby'] );
		$instance['order']          = sanitize_text_field( $new_instance['order'] );
		$instance['featured']       = isset( $new_instance['featured'] ) ? 'yes' : 'no';
		$instance['members_only']   = isset( $new_instance['members_only'] ) ? 'yes' : 'no';

		return $instance;
	}

	/**
	 * form function.
	 *
	 * @see WP_Widget->form
	 * @access public
	 * @param array $instance
	 * @return void
	 */
	function form( $instance ) {
		$title          = isset( $instance['title'] ) ? $instance['title'] : __( 'Featured Downloads', 'download_monitor' );
		$posts_per_page = isset( $instance['posts_per_page'] ) ? absint( $instance['posts_per_page']  ) : 10;
		$format         = isset( $instance['format'] ) ? sanitize_title( $instance['format'] ) : '';
		$orderby        = isset( $instance['orderby'] ) ? $instance['orderby'] : 'title';
		$order          = isset( $instance['order'] ) ? $instance['order'] : 'ASC';
		$featured       = isset( $instance['featured'] ) ? $instance['featured'] : 'no';
		$members_only   = isset( $instance['members_only'] ) ? $instance['members_only'] : 'no';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'download_monitor' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'posts_per_page' ); ?>"><?php _e( 'Limit:', 'download_monitor' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'posts_per_page' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'posts_per_page' ) ); ?>" type="text" value="<?php echo esc_attr( $posts_per_page ); ?>" size="3" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'format' ); ?>"><?php _e( 'Output template:', 'download_monitor' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'format' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'format' ) ); ?>" type="text" value="<?php echo esc_attr( $format ); ?>" placeholder="<?php _e( 'Default template', 'download_monitor' ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'orderby' ); ?>"><?php _e( 'Order by:', 'download_monitor' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'orderby' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'orderby' ) ); ?>" type="text">
				<option value="title" <?php selected( $orderby, 'title' ); ?>><?php _e( 'Title', 'download_monitor' ); ?></option>
				<option value="rand" <?php selected( $orderby, 'rand' ); ?>><?php _e( 'Random', 'download_monitor' ); ?></option>
				<option value="ID" <?php selected( $orderby, 'ID' ); ?>><?php _e( 'ID', 'download_monitor' ); ?></option>
				<option value="date" <?php selected( $orderby, 'date' ); ?>><?php _e( 'Date added', 'download_monitor' ); ?></option>
				<option value="modified" <?php selected( $orderby, 'modified' ); ?>><?php _e( 'Date modified', 'download_monitor' ); ?></option>
				<option value="download_count" <?php selected( $orderby, 'download_count' ); ?>><?php _e( 'Download count', 'download_monitor' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'order' ); ?>"><?php _e( 'Order:', 'download_monitor' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>" type="text">
				<option value="ASC" <?php selected( $order, 'ASC' ); ?>><?php _e( 'ASC', 'download_monitor' ); ?></option>
				<option value="DESC" <?php selected( $order, 'DESC' ); ?>><?php _e( 'DESC', 'download_monitor' ); ?></option>
			</select>
		</p>
		<p>
			<input id="<?php echo esc_attr( $this->get_field_id( 'featured' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'featured' ) ); ?>" type="checkbox" <?php checked( $featured, 'yes' ); ?> />
			<label for="<?php echo $this->get_field_id( 'featured' ); ?>"><?php _e( 'Show only featured downloads', 'download_monitor' ); ?></label>
		</p>
		<p>
			<input id="<?php echo esc_attr( $this->get_field_id( 'members_only' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'members_only' ) ); ?>" type="checkbox" <?php checked( $members_only, 'yes' ); ?> />
			<label for="<?php echo $this->get_field_id( 'members_only' ); ?>"><?php _e( 'Show only members only downloads', 'download_monitor' ); ?></label>
		</p>
		<?php
	}
}