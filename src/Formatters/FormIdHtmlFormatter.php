<?php

namespace Wikibase\Lexeme\Formatters;

use Html;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lib\NonExistingEntityIdHtmlFormatter;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\View\LocalizedTextProvider;

/**
 * @license GPL-2.0-or-later
 */
class FormIdHtmlFormatter implements EntityIdFormatter {

	const REPRESENTATION_SEPARATOR_I18N =
		'wikibaselexeme-formidformatter-separator-multiple-representation';

	/**
	 * @var EntityRevisionLookup
	 */
	private $revisionLookup;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var NonExistingEntityIdHtmlFormatter
	 */
	private $nonExistingIdFormatter;

	/**
	 * @var LocalizedTextProvider
	 */
	private $localizedTextProvider;

	/**
	 * @var RedirectedLexemeSubEntityIdHtmlFormatter
	 */
	private $redirectedLexemeSubEntityIdHtmlFormatter;

	public function __construct(
		EntityRevisionLookup $revisionLookup,
		EntityTitleLookup $titleLookup,
		LocalizedTextProvider $localizedTextProvider,
		RedirectedLexemeSubEntityIdHtmlFormatter $redirectedLexemeSubEntityIdHtmlFormatter
	) {
		$this->revisionLookup = $revisionLookup;
		$this->titleLookup = $titleLookup;
		$this->localizedTextProvider = $localizedTextProvider;
		$this->redirectedLexemeSubEntityIdHtmlFormatter = $redirectedLexemeSubEntityIdHtmlFormatter;
		$this->nonExistingIdFormatter = new NonExistingEntityIdHtmlFormatter(
			'wikibaselexeme-deletedentity-'
		);
	}

	/**
	 * @param EntityId|FormId $value
	 *
	 * @return string Html
	 */
	public function formatEntityId( EntityId $formId ) {
		try {
			$formRevision = $this->revisionLookup->getEntityRevision( $formId );
			$title = $this->titleLookup->getTitleForId( $formId );
		} catch ( UnresolvedEntityRedirectException $exception ) {
			return $this->redirectedLexemeSubEntityIdHtmlFormatter->formatEntityId( $formId );
		}

		if ( $formRevision === null || $title === null ) {
			return $this->nonExistingIdFormatter->formatEntityId( $formId );
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

		return Html::element(
			'a',
			[
				'href' => $title->isLocal() ? $title->getLinkURL() : $title->getFullURL(),
			],
			$representationString
		);
	}

}
