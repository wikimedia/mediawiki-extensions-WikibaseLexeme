<?php

namespace Wikibase\Lexeme\Presentation\View;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormSet;
use Wikibase\Lexeme\Presentation\View\Template\LexemeTemplateFactory;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\StatementGroupListView;
use WMDE\VueJsTemplating\Templating;

/**
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class FormsView {

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	/**
	 * @var LexemeTemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var EntityIdFormatter
	 */
	private $entityIdFormatter;

	/**
	 * @var StatementGroupListView
	 */
	private $statementGroupListView;

	public function __construct(
		LocalizedTextProvider $textProvider,
		LexemeTemplateFactory $templateFactory,
		EntityIdFormatter $entityIdFormatter,
		StatementGroupListView $statementGroupListView
	) {
		$this->textProvider = $textProvider;
		$this->templateFactory = $templateFactory;
		$this->entityIdFormatter = $entityIdFormatter;
		$this->statementGroupListView = $statementGroupListView;
	}

	/**
	 * @param FormSet $forms
	 *
	 * @return string HTML
	 */
	public function getHtml( FormSet $forms ) {
		$html = '<div class="wikibase-lexeme-forms-section">';
		$html .= '<h2 class="wb-section-heading section-heading">'
			. '<span class="mw-headline" id="forms">'
			. htmlspecialchars( $this->textProvider->get( 'wikibaselexeme-header-forms' ) )
			. '</span>'
			. '</h2>';

		$html .= '<div class="wikibase-lexeme-forms ">';
		foreach ( $forms->toArray() as $form ) {
			$html .= $this->getFormHtml( $form );
		}
		$html .= '</div>';
		$html .= '</div>';
		$html .= $this->getRepresentationsVueTemplate();

		return $html;
	}

	/**
	 * @param Form $form
	 *
	 * @return string HTML
	 */
	private function getFormHtml( Form $form ) {
		$grammaticalFeaturesHtml = $this->templateFactory->render(
			'wikibase-lexeme-form-grammatical-features',
			[
				htmlspecialchars(
					$this->textProvider->get( 'wikibaselexeme-form-grammatical-features' ),
					ENT_QUOTES,
					'UTF-8',
					false
				),
				implode(
					// Escape HTML without double escaping entities, {@see Message::escaped}
					htmlspecialchars( $this->textProvider->get( 'comma-separator' ), ENT_QUOTES, 'UTF-8', false ),
					array_map(
						function ( ItemId $id ) {
							return '<span>' . $this->getGrammaticalFeatureHtml( $id ) . '</span>';
						},
						$form->getGrammaticalFeatures()
					)
				)
			]
		);

		return $this->templateFactory->render( 'wikibase-lexeme-form', [
			htmlspecialchars( $form->getId()->getSerialization() ),
			$this->renderRepresentationWidget( $form ),
			$grammaticalFeaturesHtml,
			$this->getStatementSectionHtml( $form ),
			//Anchor separated from ID to avoid issue with front-end rendering
			htmlspecialchars( $form->getId()->getIdSuffix() )
		] );
	}

	/**
	 * @return string
	 */
	private function renderRepresentationWidget( Form $form ) {
		$templating = new Templating();

		$representations = array_map(
			function ( Term $r ) {
				return [ 'value' => $r->getText(), 'language' => $r->getLanguageCode() ];
			},
			iterator_to_array( $form->getRepresentations() )
		);

		$result = $templating->render(
			$this->getRawRepresentationVueTemplate(),
			[
				'inEditMode' => false,
				'representations' => $representations
			],
			[
				'message' => function ( $key ) {
					return $this->textProvider->get( $key );
				}
			]
		);

		return '<div class="form-representations">'
			. $result
			. '</div>';
	}

	/**
	 * @param ItemId $id
	 *
	 * @return string HTML
	 */
	private function getGrammaticalFeatureHtml( ItemId $id ) {
		return $this->entityIdFormatter->formatEntityId( $id );
	}

	private function getRepresentationsVueTemplate() {
		return <<<HTML
<script id="representation-widget-vue-template" type="x-template">
	{$this->getRawRepresentationVueTemplate()}
</script>
HTML;
	}

	private function getRawRepresentationVueTemplate() {
		return <<<'HTML'
<div class="representation-widget">
	<ul v-if="!inEditMode" class="representation-widget_representation-list">
		<li v-for="representation in representations" class="representation-widget_representation">
			<span class="representation-widget_representation-value" 
				:lang="representation.language">{{representation.value}}</span>
			<span class="representation-widget_representation-language">
				{{representation.language}}
			</span>
		</li>
	</ul>
	<div v-else>
		<div class="representation-widget_edit-area">
			<ul class="representation-widget_representation-list">
				<li v-for="representation in representations" 
					class="representation-widget_representation-edit-box">
					<span class="representation-widget_representation-value-label">
						{{'wikibaselexeme-form-field-representation-label'|message}}
					</span>
					<input size="1" class="representation-widget_representation-value-input" 
						:value="representation.value"
						@input="updateValue(representation, $event)">
					<span class="representation-widget_representation-language-label">
						{{'wikibaselexeme-form-field-language-label'|message}}
					</span>
					<input size="1" class="representation-widget_representation-language-input" 
						:value="representation.language"
						@input="updateLanguage(representation, $event)" 
						:class="{ 
							'representation-widget_representation-language-input_redundant-language': 
								isRedundantLanguage(representation.language)
						}" 
						:aria-invalid="isRedundantLanguage(representation.language)">
					<button class="representation-widget_representation-remove" 
						v-on:click="remove(representation)" 
						:disabled="representations.length <= 1"
						:title="'wikibase-remove'|message">
						&times;
					</button>
				</li>
				<li class="representation-widget_edit-area-controls">
					<button type="button" class="representation-widget_add" v-on:click="add" 
						:title="'wikibase-add'|message">+</button>
				</li>
			</ul>
		</div>
		<div v-if="hasRedundantLanguage" class="representation-widget_redundant-language-warning">
			<p>{{'wikibaselexeme-form-representation-redundant-language'|message}}</p>
		</div>
	</div>
</div>
HTML;
	}

	/**
	 * @param Form $form
	 *
	 * @return string HTML
	 */
	private function getStatementSectionHtml( Form $form ) {
		$headerText = htmlspecialchars(
			$this->textProvider->get(
				'wikibaselexeme-statementsection-statements-about-form',
				[ $form->getId()->getSerialization() ]
			)
		);

		$statementHeader = <<<HTML
<h2 class="wb-section-heading section-heading wikibase-statements" dir="auto">
	<span class="mw-headline">{$headerText}</span>
</h2>
HTML;

		$statementSection = $this->statementGroupListView->getHtml(
			$form->getStatements()->toArray(), $form->getId()->getIdSuffix()
		);
		return $statementHeader . $statementSection;
	}

}
