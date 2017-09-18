<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\Generator;
use Eris\Generator\GeneratedValueOptions;
use Eris\Generator\GeneratedValueSingle;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormSet;

class FormSetGenerator implements Generator {

	/**
	 * @var FormGenerator
	 */
	private $formGenerator;

	public function __construct() {
		$this->formGenerator = new FormGenerator();
	}

	public function __invoke( $size, $rand ) {
		$generateForm = $this->formGenerator;

		$listSize = $rand( 0, $size );

		$result = new FormSet( [] );

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

	public function shrink( GeneratedValueSingle $element ) {
		return $element;
	}

}
