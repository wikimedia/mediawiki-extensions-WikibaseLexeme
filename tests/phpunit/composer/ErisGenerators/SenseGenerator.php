<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\Generator;
use Eris\Generator\ChooseGenerator;
use Eris\Generator\ConstantGenerator;
use Eris\Generator\GeneratedValue;
use Eris\Generator\GeneratedValueSingle;
use Eris\Generator\MapGenerator;
use Eris\Random\RandomRange;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * @license GPL-2.0-or-later
 */
class SenseGenerator implements Generator {

	private const MAX_SENSE_ID = 100;

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
				static function ( $number ) {
					// FIXME: This hard coded parent ID will result in inconsistent test data!
					return new SenseId( 'L1-S' . $number );
				},
				new ChooseGenerator( 1, self::MAX_SENSE_ID )
			);
		}
	}

	public function __invoke( $size, RandomRange $rand ) {
		$generateGlosses = $this->representationGenerator;
		$generateSenseId = $this->senseIdGenerator;

		$senseId = $generateSenseId( $size, $rand )->unbox();
		$glosses = $generateGlosses( $size, $rand )->unbox();
		$statementList = null;

		$sense = new Sense( $senseId, $glosses, $statementList );
		return GeneratedValueSingle::fromJustValue( $sense, 'sense' );
	}

	public function shrink( GeneratedValue $element ) {
		return $element;
	}

}
