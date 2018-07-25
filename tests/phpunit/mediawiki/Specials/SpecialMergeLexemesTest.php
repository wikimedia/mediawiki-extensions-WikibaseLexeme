<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Specials;

use FauxRequest;
use HamcrestPHPUnitIntegration;
use PermissionsError;
use SpecialPageTestBase;
use Wikibase\Lexeme\Specials\SpecialMergeLexemes;

/**
 * @covers \Wikibase\Lexeme\Specials\SpecialMergeLexemes
 *
 * @license GPL-2.0-or-later
 */
class SpecialMergeLexemesTest extends SpecialPageTestBase {

	use HamcrestPHPUnitIntegration;

	public function testSpecialMergeLexemesContainsInputFields() {
		list( $output, $reponse ) = $this->executeSpecialPage(
			'',
			new FauxRequest()
		);

		$this->assertThatHamcrest( $output, is( htmlPiece( both(
			havingChild( tagMatchingOutline( '<input name="from-id">' ) )
		)->andAlso( havingChild( tagMatchingOutline( '<input name="to-id">' ) ) )
		) ) );
	}

	public function testRequestByUserWithoutPermission_accessIsDenied() {
		$this->setMwGlobals( [
			'wgGroupPermissions' => [
				'*' => [
					'item-merge' => false
				]
			]
		] );

		try {
			$this->executeSpecialPage();
			$this->fail();
		} catch ( PermissionsError $exception ) {
			$this->assertSame( 'badaccess-group0', $exception->errors[0][0] );
		}
	}

	protected function newSpecialPage() {
		return new SpecialMergeLexemes();
	}

}
