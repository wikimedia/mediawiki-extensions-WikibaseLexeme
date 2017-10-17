<?php

namespace Wikibase\Lexeme\View;

use InvalidArgumentException;
use Language;
use Message;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\View\EntityTermsView;
use Wikibase\View\EntityView;
use Wikibase\View\HtmlTermRenderer;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\StatementSectionsView;
use Wikibase\View\Template\TemplateFactory;
use Wikimedia\Assert\Assert;
use WMDE\VueJsTemplating\Templating;

/**
 * Class for creating HTML views for Lexeme instances.
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeView extends EntityView {

	/**
	 * @var FormsView
	 */
	private $formsView;

	/**
	 * @var SensesView
	 */
	private $sensesView;

	/**
	 * @var StatementSectionsView
	 */
	private $statementSectionsView;

	/**
	 * @var HtmlTermRenderer
	 */
	private $htmlTermRenderer;

	/**
	 * @var LabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param EntityTermsView $entityTermsView
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 * @param string $languageCode
	 * @param FormsView $formsView
	 * @param SensesView $sensesView
	 * @param StatementSectionsView $statementSectionsView
	 * @param HtmlTermRenderer $htmlTermRenderer
	 * @param LabelDescriptionLookup $labelDescriptionLookup
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EntityTermsView $entityTermsView,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		$languageCode,
		FormsView $formsView,
		SensesView $sensesView,
		StatementSectionsView $statementSectionsView,
		HtmlTermRenderer $htmlTermRenderer,
		LabelDescriptionLookup $labelDescriptionLookup
	) {
		parent::__construct(
			$templateFactory,
			$entityTermsView,
			$languageDirectionalityLookup,
			$languageCode
		);

		$this->formsView = $formsView;
		$this->sensesView = $sensesView;
		$this->statementSectionsView = $statementSectionsView;
		$this->htmlTermRenderer = $htmlTermRenderer;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
	}

	/**
	 * @see EntityView::getMainHtml
	 *
	 * @param EntityDocument $entity
	 *
	 * @throws InvalidArgumentException if the entity type does not match.
	 * @return string HTML
	 */
	protected function getMainHtml( EntityDocument $entity ) {
		/** @var Lexeme $entity */
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		$id = '';
		if ( $entity->getId() ) {
			$id = htmlspecialchars(
				$this->getLocalizedMessage( 'parentheses', [ $entity->getId()->getSerialization() ] )
			);
		}

		$lemmaWidget = $this->renderLemmaWidget( $entity ) . $this->getLemmaVueTemplate();
		$languageAndCategory = $this->renderLanguageAndLexicalCategoryWidget( $entity );

		$lexemeHeader = <<<HTML
			<div id="wb-lexeme-header" class="wb-lexeme-header">
				<h1 id="wb-lexeme-header-lemmas">
					<div class="wb-lexeme-header_id">$id</div>
					<div class="wb-lexeme-header_lemma-widget">
						$lemmaWidget
					</div>
				</h1>
				$languageAndCategory
			</div>
HTML;

		return $lexemeHeader
			. $this->getLexemeHeaderVueTemplate()
			. $this->getLanguageAndLexicalCategoryVueTemplate()
			. $this->templateFactory->render( 'wikibase-toc' )
			. $this->statementSectionsView->getHtml( $entity->getStatements() )
			. $this->formsView->getHtml( $entity->getForms() )
			. $this->sensesView->getHtml( $entity->getSenses() );
	}

	/**
	 * @see EntityView::getSideHtml
	 *
	 * @param EntityDocument $entity
	 *
	 * @return string HTML
	 */
	protected function getSideHtml( EntityDocument $entity ) {
		return '';
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return string
	 */
	public function getTitleHtml( EntityDocument $entity ) {
		/** @var Lexeme $entity */
		Assert::parameterType( Lexeme::class, $entity, '$entity' );
		$isEmpty = true;
		$idInParenthesesHtml = '';
		$labelHtml = '';

		if ( $entity->getId() !== null ) {
			$id = $entity->getId()->getSerialization();
			$idInParenthesesHtml = htmlspecialchars(
				$this->getLocalizedMessage( 'parentheses', [ $id ] )
			);

			$label = $this->getMainTerm( $entity->getLemmas() );
			if ( $label !== null ) {
				$labelHtml = $this->htmlTermRenderer->renderTerm( $label );
				$isEmpty = false;
			}
		}

		$title = $isEmpty ? htmlspecialchars(
			$this->getLocalizedMessage( 'wikibase-label-empty' ) ) : $labelHtml;

		return $this->templateFactory->render(
			'wikibase-title',
			$isEmpty ? 'wb-empty' : '',
			$title,
			$idInParenthesesHtml
		);
	}

	/**
	 * @param Lexeme $lexeme
	 *
	 * @return string HTML
	 */
	private function getHtmlForLexicalCategoryAndLanguage( Lexeme $lexeme ) {
		$lexicalCategory = $this->getItemIdHtml( $lexeme->getLexicalCategory() );
		$language = $this->getItemIdHtml( $lexeme->getLanguage() );

		// TODO: Rethink way to present 'noun in English' - it will not look correct in Russian
		//       because language word should be in the genitive which currently is not possible
		return $this->getLocalizedMessage(
			'wikibase-lexeme-view-language-lexical-category',
			[ $lexicalCategory, $language ]
		);
	}

	/**
	 * @param TermList|null $lemmas
	 *
	 * @return Term|null
	 */
	private function getMainTerm( TermList $lemmas = null ) {
		if ( $lemmas === null || $lemmas->isEmpty() ) {
			return null;
		}

		// Return the first term, until we build a proper UI
		foreach ( $lemmas->getIterator() as $term ) {
			return $term;
		}

		return null;
	}

	/**
	 * @param string $key
	 * @param array $params
	 *
	 * @return string Plain text
	 */
	private function getLocalizedMessage( $key, array $params = [] ) {
		return ( new Message( $key, $params, Language::factory( $this->languageCode ) ) )->text();
	}

	/**
	 * @param ItemId $itemId
	 *
	 * @return string HTML
	 */
	private function getItemIdHtml( ItemId $itemId ) {
		try {
			$label = $this->labelDescriptionLookup->getLabel( $itemId );
		} catch ( LabelDescriptionLookupException $e ) {
			$label = null;
		}

		if ( $label === null ) {
			return $itemId->getSerialization();
		}

		return $this->htmlTermRenderer->renderTerm( $label );
	}

	private function getLemmaVueTemplate() {
		return <<<HTML
<script id="lemma-widget-vue-template" type="x-template">
	{$this->getRawLemmaVueTemplate()}
</script>
HTML;
	}

	private function getLexemeHeaderVueTemplate() {
		return <<<HTML
<script id="lexeme-header-widget-vue-template" type="x-template">
	{$this->getRawLexemeHeaderVueTemplate()}
</script>
HTML;
	}

	private function getLanguageAndLexicalCategoryVueTemplate() {
		return <<<HTML
<script id="language-and-lexical-category-widget-vue-template" type="x-template">
	{$this->getRawLanguageAndLexicalCategoryWidgetVueTemplate()}
</script>
HTML;
	}

	private function getRawLexemeHeaderVueTemplate() {
		return <<<'HTML'
<div id="wb-lexeme-header" class="wb-lexeme-header">
	<h1 id="wb-lexeme-header-lemmas">
		<div class="wb-lexeme-header_id">({{id}})</div><!-- TODO: i18n parentheses -->
		<div class="wb-lexeme-header_lemma-widget">
			<lemma-widget :lemmas="lemmas" :inEditMode="inEditMode" :isSaving="isSaving"></lemma-widget>
		</div>
		<div class="lemma-widget_controls" v-if="isInitialized" >
			<button type="button" class="lemma-widget_edit" v-if="!inEditMode" 
				:disabled="isSaving" v-on:click="edit">{{'wikibase-edit'|message}}</button>
			<button type="button" class="lemma-widget_save" v-if="inEditMode" 
				:disabled="isSaving" v-on:click="save">{{'wikibase-save'|message}}</button>
			<button type="button" class="lemma-widget_cancel" v-if="inEditMode" 
				:disabled="isSaving"  v-on:click="cancel">{{'wikibase-cancel'|message}}</button>
		</div>
	</h1>
	<language-and-category-widget
		:language.sync="language"
		:lexicalCategory.sync="lexicalCategory"
		:inEditMode="inEditMode"
		:isSaving="isSaving">
	</language-and-category-widget>
</div>
HTML;
	}

	private function getRawLanguageAndLexicalCategoryWidgetVueTemplate() {
		return <<<'HTML'
<div class="language-lexical-category-widget">
	<div v-if="!inEditMode">
		<div>
			<span>{{'wikibase-lexeme-language'|message}}</span>
			<span class="language-lexical-category-widget_language">{{language}}</span>
		</div>
		<div>
			<span>{{'wikibase-lexeme-lexical-category'|message}}</span>
			<span class="language-lexical-category-widget_lexical-category">{{lexicalCategory}}</span>
		</div>
	</div>
	<div v-else>
		<div>
			<label for="lexeme-language">{{'wikibase-lexeme-language'|message}}</label>
			<input
				id="lexeme-language"
				v-bind:value="language"
				@input="$emit('update:language', $event.target.value)">
		</div>
		<div>
			<label for="lexeme-lexical-category">{{'wikibase-lexeme-lexical-category'|message}}</label>
			<input
				id="lexeme-lexical-category"
				v-bind:value="lexicalCategory"
				@input="$emit('update:lexicalCategory', $event.target.value)">
		</div>
	</div>
</div>
HTML;
	}

	private function getRawLemmaVueTemplate() {
		return <<<'HTML'
<div class="lemma-widget">
	<ul v-if="!inEditMode" class="lemma-widget_lemma-list">
		<li v-for="lemma in lemmas" class="lemma-widget_lemma">
			<span class="lemma-widget_lemma-value">{{lemma.value}}</span>
			<span class="lemma-widget_lemma-language">{{lemma.language}}</span>
		</li>
	</ul>
	<div v-else class="lemma-widget_edit-area">
		<ul class="lemma-widget_lemma-list">
			<li v-for="lemma in lemmas" class="lemma-widget_lemma-edit-box">
				<span class="lemma-widget_lemma-value-label">
					{{'wikibase-lemma-field-lemma-label'|message}}
				</span>
				<input size="1" class="lemma-widget_lemma-value-input" 
					v-model="lemma.value" :disabled="isSaving">
				<span class="lemma-widget_lemma-language-label">
					{{'wikibase-lemma-field-language-label'|message}}
				</span>
				<input size="1" class="lemma-widget_lemma-language-input" 
					v-model="lemma.language" :disabled="isSaving">
				<button class="lemma-widget_lemma-remove" v-on:click="remove(lemma)" 
					:disabled="isSaving" :title="'wikibase-remove'|message">
					&times;
				</button>
			</li>
			<li>
				<button type="button" class="lemma-widget_add" v-on:click="add" 
					:disabled="isSaving" :title="'wikibase-add'|message">+</button>
			</li>
		</ul>
	</div>
</div>
HTML;
	}

	/**
	 * @return string
	 */
	protected function renderLemmaWidget( Lexeme $lexeme ) {
		$templating = new Templating();

		$lemmas = array_map(
			function ( Term $lemma ) {
				return [ 'value' => $lemma->getText(), 'language' => $lemma->getLanguageCode() ];
			},
			iterator_to_array( $lexeme->getLemmas() )
		);

		$result = $templating->render(
			$this->getRawLemmaVueTemplate(),
			[
				'isInitialized' => false,
				'inEditMode' => false,
				'isSaving' => false,
				'lemmas' => $lemmas
			],
			[
				'message' => function ( $key ) {
					return $this->getLocalizedMessage( $key );
				}
			]
		);

		return '<div id="lemmas-widget">'
			. $result
			. '</div>';
	}

	/**
	 * @param Lexeme $lexeme
	 * @return string
	 */
	protected function renderLanguageAndLexicalCategoryWidget( Lexeme $lexeme ) {
		$templating = new Templating();

		$result = $templating->render(
			$this->getRawLanguageAndLexicalCategoryWidgetVueTemplate(),
			[
				'isInitialized' => false,
				'inEditMode' => false,
				'isSaving' => false,
				'language' => $lexeme->getLanguage()->getSerialization(),
				'lexicalCategory' => $lexeme->getLexicalCategory()->getSerialization()
			],
			[
				'message' => function ( $key ) {
					return $this->getLocalizedMessage( $key );
				}
			]
		);

		return '<div>' . $result . '</div>';
	}

}
