<?php

namespace Wikimedia\RequestTimeout\Tests;

use PHPUnit\Framework\TestCase;
use Wikimedia\RequestTimeout\RequestTimeout;

/**
 * @covers \Wikimedia\RequestTimeout\RequestTimeout
 */
class RequestTimeoutTest extends TestCase {
	public function testSingleton() {
		RequestTimeout::setInstance( null );
		$this->assertInstanceOf( RequestTimeout::class, RequestTimeout::singleton() );
		RequestTimeout::setInstance( null );
	}
}
