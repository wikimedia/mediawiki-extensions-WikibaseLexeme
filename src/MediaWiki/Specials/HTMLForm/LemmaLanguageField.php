<?php

namespace Wikibase\Lexeme\MediaWiki\Specials\HTMLForm;

use HTMLComboboxField;
use InvalidArgumentException;
use RequestContext;
use Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookup;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\ContentLanguages;

/**
 * Class representing lexeme lemma language selector field
 *
 * @license GPL-2.0-or-later
 */
class LemmaLanguageField extends HTMLComboboxField {

	/**
	 * @inheritDoc
	 *
	 * @see \HTMLForm There is detailed description of the allowed $params (named $info there).
	 */
	public function __construct( array $params ) {
		if ( isset( $params['options'] )
			|| isset( $params['options-message'] )
			|| isset( $params['options-messages'] )
		) {
			throw new InvalidArgumentException(
				"Cannot set options for content language field. It already has it's own options"
			);
		}

		$params['options'] = $this->constructOptions(
			WikibaseLexemeServices::getTermLanguages(),
			WikibaseLexemeServices::getLanguageNameLookupFactory()
				->getForContextSource( RequestContext::getMain() )
		);

		parent::__construct( $params );
	}

	/**
	 * @param ContentLanguages $contentLanguages
	 * @param LexemeLanguageNameLookup $lookup
	 *
	 * @return array
	 */
	private function constructOptions(
		ContentLanguages $contentLanguages,
		LexemeLanguageNameLookup $lookup
	) {
		$languageOptions = [];

		foreach ( $contentLanguages->getLanguages() as $code ) {
			$option = $this->msg(
				'wikibase-lexeme-lemma-language-option',
				[
					$lookup->getName( $code ),
					$code
				]
			)->plain();
			$languageOptions[$option] = $code;
		}

		return $languageOptions;
	}

	public function validate( $value, $alldata ) {
		if ( !in_array( $value, $this->getOptions(), true ) ) {
			return $this->msg( 'wikibase-lexeme-lemma-language-not-recognized' );
		}

		return parent::validate( $value, $alldata );
	}

}
