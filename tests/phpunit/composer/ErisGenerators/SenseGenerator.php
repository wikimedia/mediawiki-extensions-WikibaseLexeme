<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\Generator;
use Eris\Generator\ConstantGenerator;
use Eris\Generator\GeneratedValueSingle;
use Eris\Generator\MapGenerator;
use Wikibase\Lexeme\Domain\DataModel\Sense;
use Wikibase\Lexeme\Domain\DataModel\SenseId;

/**
 * @license GPL-2.0-or-later
 */
class SenseGenerator implements Generator {

	const MAX_SENSE_ID = 100;

	/**
	 * @var TermListGenerator
	 */
	private $representationGenerator;

	/**
	 * @var Generator
	 */
	private $senseIdGenerator;

	public function __construct( SenseId $senseId = null ) {
		$this->representationGenerator = new TermListGenerator( 1 );
		if ( $senseId ) {
			$this->senseIdGenerator = ConstantGenerator::box( $senseId );
		} else {
			$this->senseIdGenerator = new MapGenerator(
				function ( $number ) {
					// FIXME: This hard coded parent ID will result in inconsistent test data!
					return new SenseId( 'L1-S' . $number );
				},
				new Generator\ChooseGenerator( 1, self::MAX_SENSE_ID )
			);
		}
	}

	public function __invoke( $size, $rand ) {
		$generateGlosses = $this->representationGenerator;
		$generateSenseId = $this->senseIdGenerator;

		$senseId = $generateSenseId( $size, $rand )->unbox();
		$glosses = $generateGlosses( $size, $rand )->unbox();
		$statementList = null;

		$sense = new Sense( $senseId, $glosses, $statementList );
		return GeneratedValueSingle::fromJustValue( $sense, 'sense' );
	}

	public function shrink( GeneratedValueSingle $element ) {
		return $element;
	}

}
