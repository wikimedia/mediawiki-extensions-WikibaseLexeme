<?php

namespace Wikibase\Lexeme\PropertyType;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\View\LocalizedTextProvider;

/**
 * @license GPL-2.0-or-later
 */
class FormIdTextFormatter implements EntityIdFormatter {

	const REPRESENTATION_SEPARATOR_I18N =
		'wikibaselexeme-formidformatter-separator-multiple-representation';

	/**
	 * @var EntityRevisionLookup
	 */
	private $revisionLookup;

	/**
	 * @var LocalizedTextProvider
	 */
	private $localizedTextProvider;

	public function __construct(
		EntityRevisionLookup $revisionLookup,
		LocalizedTextProvider $localizedTextProvider
	) {
		$this->revisionLookup = $revisionLookup;
		$this->localizedTextProvider = $localizedTextProvider;
	}

	/**
	 * @param EntityId|FormId $value
	 *
	 * @return string plain text
	 */
	public function formatEntityId( EntityId $formId ) {
		try {
			$formRevision = $this->revisionLookup->getEntityRevision( $formId );
		} catch ( UnresolvedEntityRedirectException $exception ) {
			return $formId->getSerialization();
		}

		if ( $formRevision === null ) {
			return $formId->getSerialization();
		}

		/** @var Form $form */
		$form = $formRevision->getEntity();
		$representations = $form->getRepresentations();
		$representationSeparator = $this->localizedTextProvider->get(
			self::REPRESENTATION_SEPARATOR_I18N
		);

		$representationString = implode(
			$representationSeparator,
			$representations->toTextArray()
		);

		return $representationString;
	}

}
