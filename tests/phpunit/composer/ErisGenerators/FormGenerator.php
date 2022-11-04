<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\Generator;
use Eris\Generator\ChooseGenerator;
use Eris\Generator\ConstantGenerator;
use Eris\Generator\GeneratedValue;
use Eris\Generator\GeneratedValueSingle;
use Eris\Generator\MapGenerator;
use Eris\Generator\SetGenerator;
use Eris\Random\RandomRange;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;

/**
 * @license GPL-2.0-or-later
 */
class FormGenerator implements Generator {

	private const MAX_FORM_ID = 100;

	/**
	 * @var TermListGenerator
	 */
	private $representationGenerator;

	/**
	 * @var SetGenerator
	 */
	private $grammaticalFeaturesGenerator;

	/**
	 * @var Generator
	 */
	private $formIdGenerator;

	public function __construct( FormId $formId = null ) {
		$this->representationGenerator = new TermListGenerator( 1 );
		$this->grammaticalFeaturesGenerator = new SetGenerator( new ItemIdGenerator( 50 ) );
		if ( $formId ) {
			$this->formIdGenerator = ConstantGenerator::box( $formId );
		} else {
			$this->formIdGenerator = new MapGenerator(
				static function ( $number ) {
					// FIXME: This hard coded parent ID will result in inconsistent test data!
					return new FormId( 'L1-F' . $number );
				},
				new ChooseGenerator( 1, self::MAX_FORM_ID )
			);
		}
	}

	public function __invoke( $size, RandomRange $rand ) {
		$generateRepresentations = $this->representationGenerator;
		$generateGrammaticalFeatures = $this->grammaticalFeaturesGenerator;
		$generateFormId = $this->formIdGenerator;

		$formId = $generateFormId( $size, $rand )->unbox();
		$representations = $generateRepresentations( $size, $rand )->unbox();
		$grammaticalFeatures = $generateGrammaticalFeatures( $size, $rand )->unbox();
		$statementList = null;

		$form = new Form( $formId, $representations, $grammaticalFeatures, $statementList );
		return GeneratedValueSingle::fromJustValue( $form, 'form' );
	}

	public function shrink( GeneratedValue $element ) {
		return $element;
	}

}
