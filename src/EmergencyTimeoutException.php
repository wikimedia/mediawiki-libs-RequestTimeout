<?php

namespace Wikimedia\RequestTimeout;

/**
 * An exception which is thrown if a critical section is open for too long.
 */
class EmergencyTimeoutException extends TimeoutException {
	public function __construct( $name, $limit ) {
		parent::__construct( "The critical section \"$name\" timed out after $limit seconds" );
	}
}
