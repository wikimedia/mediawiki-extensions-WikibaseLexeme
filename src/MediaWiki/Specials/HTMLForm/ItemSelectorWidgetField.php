<?php

namespace Wikibase\Lexeme\MediaWiki\Specials\HTMLForm;

use Language;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Repo\Specials\HTMLForm\HTMLItemReferenceField;
use Wikibase\Repo\WikibaseRepo;

/**
 * Passes ItemSelectorWidget instead of OOUI\TextInputWidget to the frontend
 *
 * @license GPL-2.0-or-later
 */
class ItemSelectorWidgetField extends HTMLItemReferenceField {

	const NATIVE_AGENT_AUTOCOMPLETE = false;

	/**
	 * @var string|null
	 */
	private $labelFieldName = null;

	/**
	 * @var EntityIdParser|null
	 */
	private $idParser = null;

	/**
	 * @var LabelDescriptionLookup|null
	 */
	private $labelLookup = null;

	public function __construct(
		array $params,
		EntityIdParser $idParser = null,
		LabelDescriptionLookup $lookup = null
	) {
		$params['autocomplete'] = self::NATIVE_AGENT_AUTOCOMPLETE;

		parent::__construct( $params );

		$this->idParser = $idParser ?: WikibaseRepo::getEntityIdParser();
		$this->labelLookup = $lookup ?:
			WikibaseRepo::getDefaultInstance()->getLanguageFallbackLabelDescriptionLookupFactory()
				->newLabelDescriptionLookup( Language::factory( 'en' ) );

		if ( isset( $params['labelFieldName'] ) ) {
			$this->labelFieldName = $params['labelFieldName'];
		}
	}

	protected function getInputWidget( $params ) {
		if ( $this->labelFieldName !== null ) {
			$params['labelFieldName'] = $this->labelFieldName;
		}

		if ( isset( $params['value'] ) ) {
			try {
				$id = $this->idParser->parse( $params['value'] );
				$label = $this->labelLookup->getLabel( $id );
				if ( $label !== null ) {
					// TODO: i18n this. Note this must reflect what JS code does!
					$params['labelFieldValue'] = $label->getText() . ' (' . $id->getSerialization() . ')';
				}
			} catch ( EntityIdParsingException $e ) {
			}
		}

		return new ItemSelectorWidget( $params );
	}

}
