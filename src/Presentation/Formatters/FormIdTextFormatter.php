<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Presentation\Formatters;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\View\LocalizedTextProvider;

/**
 * @license GPL-2.0-or-later
 */
class FormIdTextFormatter implements EntityIdFormatter {

	private const REPRESENTATION_SEPARATOR_I18N =
		'wikibaselexeme-formidformatter-separator-multiple-representation';

	private EntityRevisionLookup $revisionLookup;
	private LocalizedTextProvider $localizedTextProvider;

	public function __construct(
		EntityRevisionLookup $revisionLookup,
		LocalizedTextProvider $localizedTextProvider
	) {
		$this->revisionLookup = $revisionLookup;
		$this->localizedTextProvider = $localizedTextProvider;
	}

	public function formatEntityId( EntityId $formId ): string {
		if ( !( $formId instanceof FormId ) ) {
			throw new InvalidArgumentException(
				'Attemped to format a non-Form entity as a Form: ' . $formId->getSerialization() );
		}
		try {
			$formRevision = $this->revisionLookup->getEntityRevision( $formId );
		} catch ( UnresolvedEntityRedirectException ) {
			return $formId->getSerialization();
		}

		if ( $formRevision === null ) {
			return $formId->getSerialization();
		}

		/** @var Form $form */
		$form = $formRevision->getEntity();
		'@phan-var Form $form';
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
