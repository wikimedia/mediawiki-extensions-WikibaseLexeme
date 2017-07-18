<?php

namespace Wikibase\Lexeme\PropertyType;

use DataValues\StringValue;
use ValueFormatters\ValueFormatter;

class FormIdFormatter implements ValueFormatter {

	/**
	 * @var array[]
	 */
	private $forms;

	public function __construct() {
		//Basically, copy-paste of resources/experts/Form.js:5-39
		//If you change something here, change also there
		$this->addForm( 'L13', 'hard English adjective', 'F1', 'hard', [ 'normative' ] );
		$this->addForm( 'L13', 'hard English adjective', 'F2', 'harder', [ 'comparative' ] );
		// @see \Wikibase\Lexeme\DemoData\Id::LF_CARD
		$this->addForm( 'L456', 'card English noun', 'F4', 'card', [ 'normative' ] );
		// @see \Wikibase\Lexeme\DemoData\Id::LF_BARD
		$this->addForm( 'L888', 'bard English noun', 'F1', 'bard', [ 'normative' ] );
		$this->addForm(
			'L14',
			'Leiter German noun',
			'F1',
			'Leiter',
			[ 'nominative', 'singular' ]
		);
		$this->addForm(
			'L14',
			'Leiter German noun',
			'F2',
			'Leiters',
			[ 'genitive', 'singular' ]
		);
		$this->addForm(
			'L14',
			'Leiter German noun',
			'F2',
			'Leiterin',
			[ 'nominative', 'singular', 'female' ]
		);
		$this->addForm(
			'L17',
			'ask English noun',
			'F1',
			'ask',
			[]
		);
	}

	/**
	 * @param StringValue $value
	 *
	 * @return string HTML
	 */
	public function format( $value ) {
		$f = $this->findFormInfo( $value );

		if ( !$f ) {
			return $value->serialize();
		}

		$title = "({$f['lexemeId']}-{$f['formId']}) {$f['grammaticalFeatures']}";
		$url = "./Lexeme:{$f['lexemeId']}#{$f['formId']}";
		$label = "{$f['representataion']} ({$f['lexemeDescription']})";

		return <<<HTML
<a href="$url" title="$title">$label</a>
HTML;
	}

	private function addForm(
		$lexemeId,
		$lexemeDescription,
		$formId,
		$representataion,
		array $grammaticalFeatures
	) {
		$this->forms[] = [
			'lexemeId' => $lexemeId,
			'lexemeDescription' => $lexemeDescription,
			'formId' => $formId,
			'representataion' => $representataion,
			'grammaticalFeatures' => implode( ', ', $grammaticalFeatures ),

		];
	}

	private function findFormInfo( StringValue $value ) {
		list( $lexemeId, $formId ) = explode( '-', $value->serialize() );

		foreach ( $this->forms as $form ) {
			if ( $lexemeId === $form['lexemeId'] && $formId === $form['formId'] ) {
				return $form;
			}
		}

		return null;
	}

}
