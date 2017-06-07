<?php

namespace Wikibase\Lexeme\View;

use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\View\Template\LexemeTemplateFactory;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\StatementSectionsView;

/**
 * @license GPL-2.0+
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
	 * @var StatementSectionsView
	 */
	private $statementSectionsView;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @param LocalizedTextProvider $textProvider
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 * @param LexemeTemplateFactory $templateFactory
	 * @param StatementSectionsView $statementSectionsView
	 * @param string $languageCode
	 */
	public function __construct(
		LocalizedTextProvider $textProvider,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		LexemeTemplateFactory $templateFactory,
		StatementSectionsView $statementSectionsView,
		$languageCode
	) {
		$this->textProvider = $textProvider;
		$this->languageDirectionalityLookup = $languageDirectionalityLookup;
		$this->templateFactory = $templateFactory;
		$this->statementSectionsView = $statementSectionsView;
		$this->languageCode = $languageCode;
	}

	/**
	 * @param Sense[] $senses
	 *
	 * @return string HTML
	 */
	public function getHtml( array $senses ) {
		$html = '<div class="wikibase-lexeme-senses-section">';
		$html .= '<h2 class="wb-section-heading section-heading">'
			. '<span class="mw-headline" id="senses">'
			. htmlspecialchars( $this->textProvider->get( 'wikibase-lexeme-view-senses' ) )
			. '</span>'
			. '</h2>';

		$html .= '<div class="wikibase-lexeme-senses">';
		foreach ( $senses as $sense ) {
			$html .= $this->getSenseHtml( $sense );
		}
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * @param Sense $sense
	 *
	 * @return string HTML
	 */
	private function getSenseHtml( Sense $sense ) {
		$hasGloss = $sense->getGlosses()->hasTermForLanguage( $this->languageCode );
		$emptyTextKey = 'wikibase-lexeme-gloss-empty';
		$effectiveLanguage = $hasGloss
			? $this->languageCode
			: $this->textProvider->getLanguageOf( $emptyTextKey );
		return $this->templateFactory->render(
			'wikibase-lexeme-sense',
			[
				$this->languageDirectionalityLookup->getDirectionality( $effectiveLanguage ) ?: 'auto',
				$effectiveLanguage,
				$hasGloss ? '' : 'wb-empty',
				$hasGloss
					? $sense->getGlosses()->getByLanguage( $this->languageCode )->getText()
					// TODO: should it rather fallback to gloss in language that exists?
					: $this->textProvider->get( $emptyTextKey ),
				wfMessage( 'parentheses' )->rawParams( htmlspecialchars( $sense->getId()->getSerialization() ) )
					->text(),
				$this->statementSectionsView->getHtml( $sense->getStatements() )
			]
		);
	}

}
