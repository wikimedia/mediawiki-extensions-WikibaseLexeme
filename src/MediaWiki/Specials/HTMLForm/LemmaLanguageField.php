<?php

namespace Wikibase\Lexeme\MediaWiki\Specials\HTMLForm;

use InvalidArgumentException;
use MediaWiki\Context\RequestContext;
use MediaWiki\HTMLForm\Field\HTMLComboboxField;
use Wikibase\Lexeme\WikibaseLexemeServices;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\WikibaseRepo;

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
			WikibaseRepo::getLanguageNameLookupFactory()
				->getForLanguage( RequestContext::getMain()->getLanguage() )
		);

		parent::__construct( $params );
	}

	private function constructOptions(
		ContentLanguages $contentLanguages,
		LanguageNameLookup $lookup
	): array {
		$languageOptions = [];

		foreach ( $contentLanguages->getLanguages() as $code ) {
			$option = $this->msg(
				'wikibase-lexeme-lemma-language-option',
				[
					$lookup->getName( $code ),
					$code,
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
