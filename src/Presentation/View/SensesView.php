<?php

namespace Wikibase\Lexeme\Presentation\View;

use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseSet;
use Wikibase\Lexeme\MediaWiki\Content\LexemeLanguageNameLookup;
use Wikibase\Lexeme\Presentation\View\Template\LexemeTemplateFactory;
use Wikibase\Lexeme\Presentation\View\Template\VueTemplates;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\StatementGroupListView;
use WMDE\VueJsTemplating\Templating;

/**
 * @license GPL-2.0-or-later
 */
class SensesView {

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	/**
	 * @var LanguageDirectionalityLookup
	 */
	private $languageDirectionalityLookup;

	/**
	 * @var LexemeTemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var StatementGroupListView
	 */
	private $statementGroupListView;

	/**
	 * @var LexemeLanguageNameLookup
	 */
	private $languageNameLookup;

	/**
	 * @param LocalizedTextProvider $textProvider
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 * @param LexemeTemplateFactory $templateFactory
	 * @param StatementGroupListView $statementGroupListView
	 * @param LexemeLanguageNameLookup $languageNameLookup
	 */
	public function __construct(
		LocalizedTextProvider $textProvider,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		LexemeTemplateFactory $templateFactory,
		StatementGroupListView $statementGroupListView,
		LexemeLanguageNameLookup $languageNameLookup
	) {
		$this->textProvider = $textProvider;
		$this->languageDirectionalityLookup = $languageDirectionalityLookup;
		$this->templateFactory = $templateFactory;
		$this->statementGroupListView = $statementGroupListView;
		$this->languageNameLookup = $languageNameLookup;
	}

	/**
	 * @param SenseSet $senses
	 *
	 * @return string HTML
	 */
	public function getHtml( SenseSet $senses ) {
		$html = '<div class="wikibase-lexeme-senses-section">';
		$html .= '<h2 class="wb-section-heading section-heading" id="senses">'
			. htmlspecialchars( $this->textProvider->get( 'wikibaselexeme-header-senses' ) )
			. '</h2>';

		$html .= '<div class="wikibase-lexeme-senses">';
		foreach ( $senses->toArray() as $sense ) {
			$html .= $this->getSenseHtml( $sense );
		}
		$html .= '</div>'; // wikibase-lexeme-senses
		// @phan-suppress-next-line PhanPluginDuplicateAdjacentStatement
		$html .= '</div>'; // wikibase-lexeme-senses-section

		return $html;
	}

	/**
	 * @param Sense $sense
	 *
	 * @return string HTML
	 */
	private function getSenseHtml( Sense $sense ) {
		$templating = new Templating();
		$template = file_get_contents( __DIR__ . VueTemplates::GLOSS_WIDGET );

		$glosses = array_map(
			static function ( Term $gloss ) {
				return [ 'value' => $gloss->getText(), 'language' => $gloss->getLanguageCode() ];
			},
			iterator_to_array( $sense->getGlosses() )
		);
		ksort( $glosses );

		$glossWidget = $templating->render(
			$template,
			[
				'senseId' => $sense->getId()->getSerialization(),
				'inEditMode' => false,
				'isSaving' => false,
				'glosses' => $glosses,
				'isUnsaveable' => true
			],
			[
				'message' => function ( $key ) {
					return $this->textProvider->get( $key );
				},
				'directionality' => function ( $languageCode ) {
					return $this->languageDirectionalityLookup->getDirectionality( $languageCode );
				},
				'languageName' => function ( $languageCode ) {
					return $this->languageNameLookup->getName( $languageCode );
				}

			]
		);

		return $this->templateFactory->render(
			'wikibase-lexeme-sense',
			[
				htmlspecialchars( $sense->getId()->getSerialization() ),
				$glossWidget,
				$this->getStatementSectionHtml( $sense ),
				htmlspecialchars( $sense->getId()->getIdSuffix() ),
				htmlspecialchars( $sense->getId()->getSerialization() )
			]
		);
	}

	/**
	 * @param Sense $sense
	 *
	 * @return string HTML
	 */
	private function getStatementSectionHtml( Sense $sense ) {
		$headerText = htmlspecialchars(
			$this->textProvider->get(
				'wikibaselexeme-statementsection-statements-about-sense',
				[ $sense->getId()->getSerialization() ]
			)
		);

		$statementHeader = <<<HTML
<h2 class="wb-section-heading section-heading wikibase-statements" dir="auto">
	$headerText
</h2>
HTML;

		$statementSection = $this->statementGroupListView->getHtml(
			$sense->getStatements()->toArray(), $sense->getId()->getIdSuffix()
		);
		return $statementHeader . $statementSection;
	}
}
