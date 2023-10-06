<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class DLM_Constants {

	const OPTION_CURRENT_VERSION = 'dlm_current_version';

	const LU_OPTION_NEEDS_UPGRADING      = 'dlm_lu_needs_upgrading';
	const LU_OPTION_UPGRADED             = 'dlm_lu_upgraded';
	const LU_OPTION_DOWNLOAD_QUEUE_BUILD = 'dlm_lu_download_queue_build';
	const LU_OPTION_CONTENT_QUEUE_BUILD  = 'dlm_lu_content_queue_build';
	// The No Access Modal template constant. Used to display the no access modal and to check if the template exists
	// in the DLM add-ons repository.
	const DLM_MODAL_TEMPLATE = 'no-access-modal';
}
