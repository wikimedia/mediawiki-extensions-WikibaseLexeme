<?php

namespace Wikibase\Lexeme\PropertyType;

use DataValues\StringValue;
use ValueFormatters\ValueFormatter;

class SenseIdFormatter implements ValueFormatter {

	/**
	 * @var array[]
	 */
	private $senses;

	public function __construct() {
		//Basically, copy-paste of resources/experts/Sense.js:8-28
		//If you change something here, change also there
		$this->addSense( 'L13', 'hard', 'English adjective', 'S1', 'presenting difficulty' );
		$this->addSense( 'L13', 'hard', 'English adjective', 'S2', 'resisting deformation' );
		$this->addSense(
			'L3627',
			'difficult',
			'English adjective',
			'S4',
			'not easy, requiring skill'
		);
		$this->addSense( 'L283', 'schwierig', 'German adjective', 'S2', 'complicated' );
		$this->addSense( 'L465', 'dur', 'French adjective', 'S1', 'hard' );
		$this->addSense( 'L801', 'easy', 'English adjective', 'S1', 'not difficult' );
		$this->addSense( 'L802', 'simple', 'English adjective', 'S1', 'not difficult' );
		$this->addSense( 'L803', 'soft', 'English adjective', 'S1', 'easy to deform' );
		$this->addSense( 'L15', 'Leiter', 'German noun', 'S1', 'leader' );
		$this->addSense( 'L15', 'Leiter', 'German noun', 'S1', 'electrical conductor' );
		$this->addSense(
			'L17',
			'ask',
			'English verb',
			'S5',
			"'To ask somebody out': To request a romantic date"
		);
		$this->addSense( 'L18', 'ask', 'English verb', 'S5', 'To request a romantic date' );
		$this->addSense(
			'L19',
			'ask out',
			'English verbal phrase',
			'S1',
			'To request a romantic date'
		);
	}

	/**
	 * @param \DataValues\StringValue $value
	 * @return string
	 */
	public function format( $value ) {
		$s = $this->findSenseInfo( $value );

		if ( !$s ) {
			return $value->serialize();
		}

		$title = "({$s['lexemeId']}-{$s['senseId']})";
		$url = "./Lexeme:{$s['lexemeId']}#{$s['senseId']}";
		$label = "{$s['lemma']} ({$s['lexemeId']}-{$s['senseId']}) " .
			"{$s['lexemeDescription']}: {$s['gloss']}";

		return <<<HTML
<a href="$url" title="$title">$label</a>
HTML;
	}

	private function addSense( $lexemeId, $lemma, $lexemeDescription, $senseId, $gloss ) {
		$this->senses[] = [
			'lexemeId' => $lexemeId,
			'lemma' => $lemma,
			'lexemeDescription' => $lexemeDescription,
			'senseId' => $senseId,
			'gloss' => $gloss,
		];
	}

	private function findSenseInfo( StringValue $value ) {
		list( $lexemeId, $senseId ) = explode( '-', $value->serialize() );

		foreach ( $this->senses as $sense ) {
			if ( $lexemeId === $sense['lexemeId'] && $senseId === $sense['senseId'] ) {
				return $sense;
			}
		}

		return null;
	}

}
