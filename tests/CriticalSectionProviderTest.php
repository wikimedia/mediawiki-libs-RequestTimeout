<?php

namespace Wikimedia\RequestTimeout\Tests;

use PHPUnit\Framework\TestCase;
use Wikimedia\RequestTimeout\CriticalSectionMismatchException;
use Wikimedia\RequestTimeout\CriticalSectionProvider;
use Wikimedia\RequestTimeout\Detail\BasicRequestTimeout;

/**
 * @covers \Wikimedia\RequestTimeout\CriticalSectionProvider
 */
class CriticalSectionProviderTest extends TestCase {
	private function createProvider() {
		return new CriticalSectionProvider(
			new BasicRequestTimeout,
			10,
			null,
			null
		);
	}

	public function testEnterExit() {
		$csp = $this->createProvider();
		$csp->enter( __METHOD__ );
		$csp->exit( __METHOD__ );
		$this->assertTrue( true );
	}

	public function testNestedEnter() {
		$csp = $this->createProvider();
		$csp->enter( 'a' );
		$csp->enter( 'b' );
		$csp->exit( 'b' );
		$csp->exit( 'a' );
		$this->assertTrue( true );
	}

	public function testSerialEnter() {
		$csp = $this->createProvider();
		$csp->enter( 'a' );
		$csp->exit( 'a' );
		$csp->enter( 'b' );
		$csp->exit( 'b' );
		$this->assertTrue( true );
	}

	public function testMismatchedExit() {
		$this->expectException( CriticalSectionMismatchException::class );
		$csp = $this->createProvider();
		$csp->enter( 'a' );
		$csp->exit( 'b' );
	}

	public function testUnmatchedExit() {
		$this->expectException( CriticalSectionMismatchException::class );
		$csp = $this->createProvider();
		$csp->exit( 'b' );
	}

	public function testScopeId() {
		$csp = $this->createProvider();
		$scope = $csp->scopedEnter( __METHOD__ );
		$id1 = $scope->getId();
		$this->assertIsInt( $id1 );
		$scope = $csp->scopedEnter( __METHOD__ );
		$id2 = $scope->getId();
		$this->assertIsInt( $id2 );
		$this->assertNotEquals( $id1, $id2 );
	}
}
