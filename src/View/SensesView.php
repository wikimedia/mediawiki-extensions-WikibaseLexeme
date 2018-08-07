<?php

namespace Wikibase\Lexeme\View;

use Language;
use Message;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\DataModel\SenseSet;
use Wikibase\Lexeme\View\Template\LexemeTemplateFactory;
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
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var StatementGroupListView
	 */
	private $statementGroupListView;

	/**
	 * @param LocalizedTextProvider $textProvider
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 * @param LexemeTemplateFactory $templateFactory
	 * @param StatementGroupListView $statementGroupListView
	 * @param string $languageCode
	 */
	public function __construct(
		LocalizedTextProvider $textProvider,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		LexemeTemplateFactory $templateFactory,
		StatementGroupListView $statementGroupListView,
		$languageCode
	) {
		$this->textProvider = $textProvider;
		$this->languageDirectionalityLookup = $languageDirectionalityLookup;
		$this->templateFactory = $templateFactory;
		$this->statementGroupListView = $statementGroupListView;
		$this->languageCode = $languageCode;
	}

	/**
	 * @param SenseSet $senses
	 *
	 * @return string HTML
	 */
	public function getHtml( SenseSet $senses ) {
		$html = '<div class="wikibase-lexeme-senses-section">';
		$html .= '<h2 class="wb-section-heading section-heading">'
			. '<span class="mw-headline" id="senses">'
			. htmlspecialchars( $this->textProvider->get( 'wikibaselexeme-header-senses' ) )
			. '</span>'
			. '</h2>';

		$html .= '<div class="wikibase-lexeme-senses">';
		foreach ( $senses->toArray() as $sense ) {
			$html .= $this->getSenseHtml( $sense );
		}
		$html .= '</div>';
		$html .= '</div>';
		$html .= $this->getGlossWidgetVueTemplate();

		return $html;
	}

	/**
	 * @param Sense $sense
	 *
	 * @return string HTML
	 */
	private function getSenseHtml( Sense $sense ) {
		$templating = new Templating();

		$glosses = array_map(
			function ( Term $gloss ) {
				return [ 'value' => $gloss->getText(), 'language' => $gloss->getLanguageCode() ];
			},
			iterator_to_array( $sense->getGlosses() )
		);

		$glossWidget = $templating->render(
			$this->getRawGlossWidgetTemplate(),
			[
				'senseId' => $sense->getId()->getSerialization(),
				'inEditMode' => false,
				'isSaving' => false,
				'glosses' => $glosses
			],
			[
				'message' => function ( $key ) {
					return $this->getLocalizedMessage( $key );
				},
				'directionality' => function ( $languageCode ) {
					return $this->languageDirectionalityLookup->getDirectionality( $languageCode );
				}

			]
		);

		return $this->templateFactory->render(
			'wikibase-lexeme-sense',
			[
				htmlspecialchars( $sense->getId()->getSerialization() ),
				$glossWidget,
				$this->getStatementSectionHtml( $sense ),
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
	<span class="mw-headline">{$headerText}</span>
</h2>
HTML;

		$statementSection = $this->statementGroupListView->getHtml(
			$sense->getStatements()->toArray()
		);
		return $statementHeader . $statementSection;
	}

	/**
	 * @param string $key
	 *
	 * @return string Plain text
	 */
	private function getLocalizedMessage( $key ) {
		return ( new Message( $key, [], Language::factory( $this->languageCode ) ) )->text();
	}

	private function getGlossWidgetVueTemplate() {
		return <<<HTML
<script id="gloss-widget-vue-template" type="x-template">
	{$this->getRawGlossWidgetTemplate()}
</script>
HTML;
	}

	private function getRawGlossWidgetTemplate() {
		return <<<'HTML'
<div class="wikibase-lexeme-sense-glosses">
	<table class="wikibase-lexeme-sense-glosses-table">
		<thead v-if="inEditMode">
			<tr class="wikibase-lexeme-sense-gloss-table-header">
				<td class="wikibase-lexeme-sense-gloss-language">
					{{'wikibaselexeme-gloss-field-language-label'|message}}
				</td>
				<td>{{'wikibaselexeme-gloss-field-gloss-label'|message}}</td>
				<td></td>
			</tr>
		</thead>
		<tbody>
			<tr v-for="gloss in glosses" class="wikibase-lexeme-sense-gloss">
				<td class="wikibase-lexeme-sense-gloss-language">
					<span v-if="!inEditMode">{{gloss.language}}</span>
					<input v-else class="wikibase-lexeme-sense-gloss-language-input"
						v-model="gloss.language" >
				</td>
				<td class="wikibase-lexeme-sense-gloss-value-cell">
					<span v-if="!inEditMode" class="wikibase-lexeme-sense-gloss-value"
						:dir="gloss.language|directionality" :lang="gloss.language">
						{{gloss.value}}
					</span>
					<input v-if="inEditMode" class="wikibase-lexeme-sense-gloss-value-input"
						v-model="gloss.value" >
				</td>
				<td class="wikibase-lexeme-sense-gloss-actions-cell">
					<button v-if="inEditMode"
					class="wikibase-lexeme-sense-glosses-control
						wikibase-lexeme-sense-glosses-remove"
					:disabled="glosses.length <= 1"
					v-on:click="remove(gloss)"  type="button">
						{{'wikibase-remove'|message}}
					</button>
				</td>
			</tr>
		</tbody>
		<tfoot v-if="inEditMode">
			<tr>
				<td>
				</td>
				<td>
					<button type="button"
						class="wikibase-lexeme-sense-glosses-control
							wikibase-lexeme-sense-glosses-add"
						v-on:click="add" >+ {{'wikibase-add'|message}}
					</button>
				</td>
			</tr>
		</tfoot>
	</table>
</div>
HTML;
	}

}
