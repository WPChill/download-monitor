<?php

class DLM_Debug_Logger {

	public static function deprecated( $method ) {

		// Don't log if WP_DEBUG is off
		if ( ! WP_DEBUG ) {
			return;
		}

		// Debug message
		$message = 'Deprecated method called: ' . $method . PHP_EOL;

		/*
		// Get stack trace
		$stack_trace = debug_backtrace();

		// Remove this method
		array_shift( $stack_trace );

		if ( count( $stack_trace ) > 0 ) {
			foreach ( $stack_trace as $item ) {

				// Add Class
				if ( isset( $item['class'] ) ) {
					$message .= $item['class'] . '::';
				}

				// Add Method / Functions
				if ( isset( $item['function'] ) ) {
					$message .= $item['function'] . ' - ';
				}

				// Add File
				if ( isset( $item['file'] ) ) {
					$message .= $item['file'];
				}

				// Add Line #
				if ( isset( $item['line'] ) ) {
					$message .= '#' . $item['line'];
				}

				// EOL
				$message .= PHP_EOL;
			}
		}
		*/

		error_log( $message, 0 );
	}

}