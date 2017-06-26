<?php

namespace Wikibase\Lexeme\View;

use Language;
use Message;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\View\Template\LexemeTemplateFactory;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\StatementSectionsView;
use WMDE\VueJsTemplating\Templating;

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
				$sense->getId()->getSerialization(),
				$glossWidget,
				$this->statementSectionsView->getHtml( $sense->getStatements() )
			]
		);
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
	<div class="wikibase-lexeme-sense-glosses-list">
		<table class="wikibase-lexeme-sense-glosses-table">
			<tbody>
				<tr v-for="gloss in glosses" class="wikibase-lexeme-sense-gloss">
					<td class="wikibase-lexeme-sense-gloss-language">
						<span v-if="!inEditMode">{{gloss.language}}</span>
						<input v-else class="wikibase-lexeme-sense-gloss-language-input"
							v-model="gloss.language" :disabled="isSaving">
					</td>
					<td class="wikibase-lexeme-sense-gloss-value-cell">
						<span v-if="!inEditMode" class="wikibase-lexeme-sense-gloss-value"
							:dir="gloss.language|directionality" :lang="gloss.language">
							{{gloss.value}}
						</span>
						<span v-if="!inEditMode" class="wikibase-lexeme-sense-glosses-sense-id">
						({{senseId}})
						</span>
						<input v-if="inEditMode" class="wikibase-lexeme-sense-gloss-value-input"
							v-model="gloss.value" :disabled="isSaving">
					</td>
					<td>
						<button v-if="inEditMode"
						class="wikibase-lexeme-sense-glosses-control
							wikibase-lexeme-sense-glosses-remove"
						v-on:click="remove(gloss)" :disabled="isSaving" type="button">
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
							v-on:click="add" :disabled="isSaving">+ {{'wikibase-add'|message}}
						</button>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
	<div class="wikibase-lexeme-sense-glosses-controls">
		<button v-if="!inEditMode" type="button"
			class="wikibase-lexeme-sense-glosses-control wikibase-lexeme-sense-glosses-edit"
			v-on:click="edit" :disabled="isSaving">{{'wikibase-edit'|message}}</button>
		<button v-if="inEditMode" type="button"
			class="wikibase-lexeme-sense-glosses-control wikibase-lexeme-sense-glosses-save"
			v-on:click="save" :disabled="isSaving">{{'wikibase-save'|message}}</button>
		<button v-if="inEditMode" type="button"
			class="wikibase-lexeme-sense-glosses-control wikibase-lexeme-sense-glosses-cancel"
			v-on:click="cancel" :disabled="isSaving">{{'wikibase-cancel'|message}}</button>
	</div>
</div>
HTML;
	}

}
