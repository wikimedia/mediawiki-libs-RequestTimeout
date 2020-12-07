<?php

namespace Wikimedia\RequestTimeout\Tests\Detail;

use PHPUnit\Framework\TestCase;
use Wikimedia\RequestTimeout\Detail\BasicRequestTimeout;

/**
 * @covers \Wikimedia\RequestTimeout\Detail\BasicRequestTimeout
 */
class BasicRequestTimeoutTest extends TestCase {
	public function testSetTimeLimit() {
		$rt = new BasicRequestTimeout();
		$rt->setWallTimeLimit( 10 );
		$this->assertSame( '10', ini_get( 'max_execution_time' ) );
		$rt->setWallTimeLimit( 0 );
	}

	public function testGetWallTimeRemaining() {
		$rt = new BasicRequestTimeout();
		$rt->setWallTimeLimit( 10 );
		$this->assertGreaterThan( 8, $rt->getWallTimeRemaining() );

		$t = microtime( true );
		while ( microtime( true ) < $t + 0.1 );

		$remaining = $rt->getWallTimeRemaining();
		$this->assertIsFloat( $remaining );
		$this->assertLessThan( 10, $remaining );
		$this->assertGreaterThan( 0, $remaining );

		$rt->setWallTimeLimit( 0 );
	}

	public function testCreateCriticalSectionProvider() {
		$rt = new BasicRequestTimeout();
		$csp = $rt->createCriticalSectionProvider( 10 );
		$csp->enter( __METHOD__ );
		$csp->exit( __METHOD__ );
		$this->assertTrue( true );
	}
}
