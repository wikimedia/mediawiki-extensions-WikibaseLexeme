<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\Generator;
use Eris\Generator\GeneratedValue;
use Eris\Generator\GeneratedValueOptions;
use Eris\Generator\GeneratedValueSingle;
use Eris\Random\RandomRange;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormSet;

/**
 * @license GPL-2.0-or-later
 */
class FormSetGenerator implements Generator {

	/**
	 * @var FormGenerator
	 */
	private $formGenerator;

	public function __construct() {
		$this->formGenerator = new FormGenerator();
	}

	public function __invoke( $size, RandomRange $rand ) {
		$generateForm = $this->formGenerator;

		$listSize = $rand->rand( 0, $size );

		$result = new FormSet();

		$trials = 0;
		$maxTrials = 2 * $listSize;
		while ( $result->count() < $listSize && $trials < $maxTrials ) {
			$trials++;
			/** @var Form $form */
			$form = $generateForm( $size, $rand )->unbox();
			if ( $result->getById( $form->getId() ) ) {
				continue;
			}

			$result->add( $form );
		}

		return GeneratedValueSingle::fromJustValue( $result, 'FormSet' );
	}

	public function shrink( GeneratedValue $element ) {
		/** @var FormSet $formSet */
		$formSet = $element->unbox();

		if ( $formSet->count() === 0 ) {
			return $element;
		} elseif ( $formSet->count() === 1 ) {
			return GeneratedValueSingle::fromValueAndInput(
				new FormSet(),
				$element,
				'FormSet'
			);
		} elseif ( $formSet->count() === 2 ) {
			$shrunk1 = new FormSet( [ $formSet->toArray()[0] ] );
			$shrunk2 = new FormSet( [ $formSet->toArray()[1] ] );
			return new GeneratedValueOptions( [
				GeneratedValueSingle::fromValueAndInput( $shrunk1, $element, 'FormSet' ),
				GeneratedValueSingle::fromValueAndInput( $shrunk2, $element, 'FormSet' ),
			] );
		} else {
			$forms = $formSet->toArray();
			$chunkSize = round( count( $forms ) / 3 );

			$chunk1 = array_slice( $forms, 0, $chunkSize );
			$chunk2 = array_slice( $forms, $chunkSize, $chunkSize );
			$chunk3 = array_slice( $forms, $chunkSize * 2 );

			$shrunk1 = new FormSet( array_merge( $chunk1, $chunk2 ) );
			$shrunk2 = new FormSet( array_merge( $chunk1, $chunk3 ) );
			$shrunk3 = new FormSet( array_merge( $chunk2, $chunk3 ) );

			array_pop( $forms );
			$shrunkOneLess = new FormSet( $forms );

			return new GeneratedValueOptions( [
				 GeneratedValueSingle::fromValueAndInput( $shrunk1, $element, 'FormSet' ),
				 GeneratedValueSingle::fromValueAndInput( $shrunk2, $element, 'FormSet' ),
				 GeneratedValueSingle::fromValueAndInput( $shrunk3, $element, 'FormSet' ),
				 GeneratedValueSingle::fromValueAndInput( $shrunkOneLess, $element, 'FormSet' ),
			] );
		}
	}

}
