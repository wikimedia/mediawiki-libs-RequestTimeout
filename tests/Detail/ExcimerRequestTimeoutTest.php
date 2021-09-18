<?php

namespace Wikimedia\RequestTimeout\Tests\Detail;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Wikimedia\RequestTimeout\Detail\ExcimerRequestTimeout;
use Wikimedia\RequestTimeout\EmergencyTimeoutException;
use Wikimedia\RequestTimeout\RequestTimeoutException;

/**
 * @covers \Wikimedia\RequestTimeout\Detail\ExcimerRequestTimeout
 * @covers \Wikimedia\RequestTimeout\Detail\ExcimerTimerWrapper
 * @covers \Wikimedia\RequestTimeout\CriticalSectionScope
 * @covers \Wikimedia\RequestTimeout\CriticalSectionProvider
 * @requires extension excimer
 */
class ExcimerRequestTimeoutTest extends TestCase {
	/**
	 * Increase this if tests fail due to slow VMs
	 */
	private const ROBUSTNESS_FACTOR = 1;

	public function testGetWallTimeRemaining() {
		$rt = new ExcimerRequestTimeout;
		$rt->setWallTimeLimit( 10 );
		$this->assertGreaterThan( 8, $rt->getWallTimeRemaining() );

		usleep( 100000 );

		$remaining = $rt->getWallTimeRemaining();
		$this->assertIsFloat( $remaining );
		$this->assertLessThan( 10, $remaining );
		$this->assertGreaterThan( 0, $remaining );
	}

	public function testTimeout() {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		$this->expectException( RequestTimeoutException::class );
		$rt = new ExcimerRequestTimeout;
		$rt->setWallTimeLimit( 0.1 );
		// @phan-suppress-next-line PhanInfiniteLoop
		while ( true );
	}

	public function testWatchdog() {
		$rt = new ExcimerRequestTimeout;
		for ( $i = 0; $i < 8; $i++ ) {
			$rt->setWallTimeLimit( 0.1 * self::ROBUSTNESS_FACTOR );
			usleep( 50000 * self::ROBUSTNESS_FACTOR );
		}
		$this->assertTrue( true );
	}

	public function testDestructStops() {
		$rt = new ExcimerRequestTimeout;
		$rt->setWallTimeLimit( 0.1 * self::ROBUSTNESS_FACTOR );
		$rt = null;
		for ( $i = 0; $i < 4; $i++ ) {
			usleep( 50000 * self::ROBUSTNESS_FACTOR );
		}
		$this->assertTrue( true );
	}

	public function testCreateCriticalSectionProvider() {
		$rt = new ExcimerRequestTimeout;
		$csp = $rt->createCriticalSectionProvider( 10 );
		$rt->setWallTimeLimit( 0.1 * self::ROBUSTNESS_FACTOR );
		$csp->enter( __METHOD__ );
		for ( $i = 0; $i < 4; $i++ ) {
			usleep( 50000 * self::ROBUSTNESS_FACTOR );
		}
		$e = null;
		try {
			$csp->exit( __METHOD__ );
		} catch ( \Exception $e ) {
		}
		$this->assertInstanceOf( RequestTimeoutException::class, $e );
	}

	public function testScopedCriticalSection() {
		$rt = new ExcimerRequestTimeout;
		$csp = $rt->createCriticalSectionProvider( 10 );
		$rt->setWallTimeLimit( 0.1 * self::ROBUSTNESS_FACTOR );
		$scope = $csp->scopedEnter( __METHOD__ );
		for ( $i = 0; $i < 4; $i++ ) {
			usleep( 50000 * self::ROBUSTNESS_FACTOR );
		}
		$e = null;
		try {
			$scope = null;
		} catch ( \Exception $e ) {
		}
		$this->assertInstanceOf( RequestTimeoutException::class, $e );
	}

	public function testScopedCriticalSectionImplicitCallbackConfigured() {
		$rt = new ExcimerRequestTimeout;
		$i = 0;
		$func = static function ( $id ) use ( &$i ) {
			$i++;
		};
		$csp = $rt->createCriticalSectionProvider( 10, null, $func );
		$rt->setWallTimeLimit( 10 );
		$scope = $csp->scopedEnter( __METHOD__ );
		$scope = null;
		$this->assertSame( 1, $i );
	}

	public function testScopedCriticalSectionImplicitCallbackOverride() {
		$rt = new ExcimerRequestTimeout;
		$i = 0;
		$noop = static function () {
		};
		$func = static function ( $id ) use ( &$i ) {
			$i++;
		};
		$csp = $rt->createCriticalSectionProvider( 10, null, $noop );
		$rt->setWallTimeLimit( 10 );
		$scope = $csp->scopedEnter( __METHOD__, null, null, $func );
		$scope = null;
		$this->assertSame( 1, $i );
	}

	public function testScopedCriticalSectionExplicit() {
		$rt = new ExcimerRequestTimeout;
		$csp = $rt->createCriticalSectionProvider( 10 );
		$rt->setWallTimeLimit( 0.1 * self::ROBUSTNESS_FACTOR );
		$scope = $csp->scopedEnter( __METHOD__ );
		for ( $i = 0; $i < 4; $i++ ) {
			usleep( 50000 * self::ROBUSTNESS_FACTOR );
		}
		$e = null;
		try {
			$scope->exit();
		} catch ( \Exception $e ) {
		}
		$this->assertInstanceOf( RequestTimeoutException::class, $e );
	}

	public function testEmergencyTimeout() {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		$rt = new ExcimerRequestTimeout;
		$csp = $rt->createCriticalSectionProvider( 0.1 );
		$csp->enter( __METHOD__ );
		$this->expectException( EmergencyTimeoutException::class );
		// @phan-suppress-next-line PhanInfiniteLoop
		while ( true );
	}

	public function testOverrideEmergencyTimeout() {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		$rt = new ExcimerRequestTimeout;
		$csp = $rt->createCriticalSectionProvider(
			INF,
			static function () {
			}
		);
		$csp->enter(
			__METHOD__,
			0.1,
			/** @return never */
			static function () {
				throw new RuntimeException;
			}
		);
		$this->expectException( RuntimeException::class );
		// @phan-suppress-next-line PhanInfiniteLoop
		while ( true );
	}

	public function testEmergencyTimeoutInfinite() {
		$rt = new ExcimerRequestTimeout;
		$rt->setWallTimeLimit( 10 );
		$csp = $rt->createCriticalSectionProvider( INF );
		$csp->enter( __METHOD__ );
		$csp->exit( __METHOD__ );
		$this->assertTrue( true );
	}

	public static function provideGetWallTimeLimit() {
		return [
			[ 10, 10.0 ],
			[ INF, INF ],
			[ 0, INF ]
		];
	}

	/**
	 * @dataProvider provideGetWallTimeLimit
	 */
	public function testGetWallTimeLimit( $input, $expected ) {
		$rt = new ExcimerRequestTimeout();
		$rt->setWallTimeLimit( $input );
		$this->assertSame( $expected, $rt->getWallTimeLimit() );
	}
}
