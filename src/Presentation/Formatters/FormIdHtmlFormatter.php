<?php

namespace Wikibase\Lexeme\Presentation\Formatters;

use Html;
use MediaWiki\Languages\LanguageFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\UnresolvedEntityRedirectException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lib\Formatters\NonExistingEntityIdHtmlFormatter;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\View\LocalizedTextProvider;

/**
 * @license GPL-2.0-or-later
 */
class FormIdHtmlFormatter implements EntityIdFormatter {

	private const REPRESENTATION_SEPARATOR_I18N =
		'wikibaselexeme-formidformatter-separator-multiple-representation';
	private const GRAMMATICAL_FEATURES_SEPARATOR_I18N =
		'wikibaselexeme-formidformatter-separator-grammatical-features';

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

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var LanguageFactory
	 */
	private $languageFactory;

	public function __construct(
		EntityRevisionLookup $revisionLookup,
		LabelDescriptionLookup $labelDescriptionLookup,
		EntityTitleLookup $titleLookup,
		LocalizedTextProvider $localizedTextProvider,
		RedirectedLexemeSubEntityIdHtmlFormatter $redirectedLexemeSubEntityIdHtmlFormatter,
		LanguageFactory $languageFactory
	) {
		$this->revisionLookup = $revisionLookup;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->titleLookup = $titleLookup;
		$this->localizedTextProvider = $localizedTextProvider;
		$this->redirectedLexemeSubEntityIdHtmlFormatter = $redirectedLexemeSubEntityIdHtmlFormatter;
		$this->nonExistingIdFormatter = new NonExistingEntityIdHtmlFormatter(
			'wikibaselexeme-deletedentity-'
		);
		$this->languageFactory = $languageFactory;
	}

	/**
	 * @param EntityId|FormId $formId
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
		'@phan-var Form $form';

		$representationMarkup = implode(
			$this->localizedTextProvider->getEscaped(
				self::REPRESENTATION_SEPARATOR_I18N
			),
			$this->buildRepresentationMarkupElements( $form->getRepresentations() )
		);

		return Html::rawElement(
			'a',
			[
				'href'  => $title->isLocal() ? $title->getLinkURL() : $title->getFullURL(),
				'title' => $this->getLinkTitle( $form )
			],
			$representationMarkup
		);
	}

	/**
	 * @param Form $form
	 * @return string
	 */
	private function getLinkTitle( $form ) {
		$serializedId = $form->getId()->getSerialization();
		$labels = implode(
			$this->localizedTextProvider->get( self::GRAMMATICAL_FEATURES_SEPARATOR_I18N ),
			$this->getLabels( $form )
		);

		if ( empty( $labels ) ) {
			$title = $serializedId;
		} else {
			$title = $this->localizedTextProvider->get(
				'wikibaselexeme-formidformatter-link-title',
				[ $serializedId, $labels ]
			);
		}

		return $title;
	}

	/**
	 * @param Form $form
	 * @return array
	 */
	private function getLabels( $form ) {
		$labels = [];

		foreach ( $form->getGrammaticalFeatures() as $grammaticalFeaturesId ) {
			$grammaticalFeatureLabel = $this->labelDescriptionLookup->getLabel( $grammaticalFeaturesId );

			if ( $grammaticalFeatureLabel !== null ) {
				$labels[] = $grammaticalFeatureLabel->getText();
			}
		}

		return $labels;
	}

	private function buildRepresentationMarkupElements( TermList $representations ): array {
		return array_map( function ( Term $representation ) {
			$language = $this->languageFactory->getLanguage( $representation->getLanguageCode() );
			return Html::element(
				'span',
				[
					'lang' => $language->getHtmlCode(),
					'dir' => $language->getDir(),
				],
				$representation->getText()
			);
		}, iterator_to_array( $representations->getIterator() ) );
	}

}
