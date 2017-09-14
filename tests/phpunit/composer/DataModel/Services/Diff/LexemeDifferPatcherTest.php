<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff;

use Eris\Facade;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiffer;
use Wikibase\Lexeme\DataModel\Services\Diff\LexemePatcher;

/**
 * @covers \Wikibase\Lexeme\DataModel\Services\Diff\LexemeDiffer
 * @covers \Wikibase\Lexeme\DataModel\Services\Diff\LexemePatcher
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 */
class LexemeDifferPatcherTest extends \PHPUnit_Framework_TestCase {

	public function testProperty_PatchingLexemeWithGeneratedDiffAlwaysRestoresItToTheTargetState() {
		if ( !class_exists( Facade::class ) ) {
			$this->markTestSkipped( 'Package `giorgiosironi/eris` is not installed. Skipping' );
		}

		$differ = new LexemeDiffer();
		$patcher = new LexemePatcher();

		//Lines below is needed to reproduce failures. In case of failure seed will be in the output
		//$seed = 1504876177284329;
		//putenv("ERIS_SEED=$seed");

		$eris = new Facade();

		$eris->forAll(
				ErisGenerators::lexeme( new LexemeId( 'L1' ) ),
				ErisGenerators::lexeme( new LexemeId( 'L1' ) )
			)
			->then( function ( Lexeme $lexeme1, Lexeme $lexeme2 ) use ( $differ, $patcher ) {
				$patch = $differ->diffEntities( $lexeme1, $lexeme2 );
				$patcher->patchEntity( $lexeme1, $patch );

				$this->assertTrue( $lexeme1->equals( $lexeme2 ), 'Lexemes are not equal' );
			} );
	}

}
