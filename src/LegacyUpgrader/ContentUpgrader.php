<?php

class DLM_LU_Content_Upgrader {

	/**
	 * Upgrade item
	 *
	 * @param $item_id int
	 *
	 * @return bool
	 */
	public function upgrade_item( $item_id ) {

		// make sure item id is int
		$item_id = absint( $item_id );

		// queue item
		$queue = new DLM_LU_Content_Queue();

		// mark content item as upgrading
//		$queue->mark_upgrading( $item_id );

		// get 'post'
		$post = get_post( $item_id );

		// content
		$content = $post->post_content;

		$regex = "`\[download(?:[^\]]*)(?:id=(?:[\"|']{0,1})([0-9]+)(?:[\"|']{0,1}))(?:[^\]]*)\]`";
		preg_match_all( $regex, $content, $matches );

		error_log( print_r( $matches, 1 ), 0 );

		// mark content item as upgraded
//		$queue->mark_upgraded( $item_id );

		return true;
	}

}