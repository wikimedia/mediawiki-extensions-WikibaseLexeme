<?php

namespace Wikibase\Lexeme\Presentation\View;

use InvalidArgumentException;
use Message;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Presentation\Formatters\LexemeTermFormatter;
use Wikibase\Lexeme\Presentation\View\Template\VueTemplates;
use Wikibase\View\EntityView;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\StatementSectionsView;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\ViewContent;
use Wikimedia\Assert\Assert;
use WMDE\VueJsTemplating\Templating;

/**
 * Class for creating HTML views for Lexeme instances.
 *
 * @license GPL-2.0-or-later
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
	 * @var EntityIdFormatter
	 */
	private $idFormatter;

	/**
	 * @var LexemeTermFormatter
	 */
	private $lemmaFormatter;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 * @param string $languageCode
	 * @param FormsView $formsView
	 * @param SensesView $sensesView
	 * @param StatementSectionsView $statementSectionsView
	 * @param LexemeTermFormatter $lemmaFormatter
	 * @param EntityIdFormatter $idFormatter
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		$languageCode,
		FormsView $formsView,
		SensesView $sensesView,
		StatementSectionsView $statementSectionsView,
		LexemeTermFormatter $lemmaFormatter,
		EntityIdFormatter $idFormatter
	) {
		parent::__construct(
			$templateFactory,
			$languageDirectionalityLookup,
			$languageCode
		);

		$this->formsView = $formsView;
		$this->sensesView = $sensesView;
		$this->statementSectionsView = $statementSectionsView;
		$this->idFormatter = $idFormatter;
		$this->lemmaFormatter = $lemmaFormatter;
	}

	/**
	 * Builds and returns the main content representing a whole Lexeme
	 *
	 * @param EntityDocument $entity the entity to render
	 * @param int|null $revision the revision of the entity to render
	 *
	 * @return ViewContent
	 */
	public function getContent( EntityDocument $entity, $revision = null ): ViewContent {
		return new ViewContent(
			$this->renderEntityView( $entity )
		);
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
		'@phan-var Lexeme $entity';

		$html = $this->getLexemeHeader( $entity )
			. $this->templateFactory->render( 'wikibase-toc' )
			. $this->statementSectionsView->getHtml( $entity->getStatements() )
			. $this->sensesView->getHtml( $entity->getSenses() )
			. $this->formsView->getHtml( $entity->getForms() );

		return $html;
	}

	/**
	 * @param Lexeme $entity
	 * @return string HTML
	 */
	private function getLexemeHeader( Lexeme $entity ) {
		$id = '';
		if ( $entity->getId() ) {
			$id = htmlspecialchars(
				$this->getLocalizedMessage( 'parentheses', [ $entity->getId()->getSerialization() ] )
			);
		}

		$lemmaWidget = $this->renderLemmaWidget( $entity );
		$languageAndCategory = $this->renderLanguageAndLexicalCategoryWidget( $entity );

		return <<<HTML
			<div id="wb-lexeme-header" class="wb-lexeme-header">
				<div id="wb-lexeme-header-lemmas">
					<div class="wb-lexeme-header_id">$id</div>
					<div class="wb-lexeme-header_lemma-widget">
						$lemmaWidget
					</div>
				</div>
				$languageAndCategory
			</div>
HTML;
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
		'@phan-var Lexeme $entity';
		$isEmpty = true;
		$idInParenthesesHtml = '';
		$labelHtml = '';

		if ( $entity->getId() !== null ) {
			$id = $entity->getId()->getSerialization();
			$isEmpty = false;
			$idInParenthesesHtml = htmlspecialchars(
				$this->getLocalizedMessage( 'parentheses', [ $id ] )
			);

			$labelHtml = $this->lemmaFormatter->format( $entity->getLemmas() );
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
	 * @param string $key
	 * @param array $params
	 *
	 * @return string Plain text
	 */
	private function getLocalizedMessage( $key, array $params = [] ) {
		return ( new Message( $key, $params ) )->inLanguage( $this->languageCode )->text();
	}

	/**
	 * @return string
	 */
	private function renderLemmaWidget( Lexeme $lexeme ) {
		$templating = new Templating();
		$template = file_get_contents( __DIR__ . VueTemplates::LEMMA );

		$lemmas = array_map(
			static function ( Term $lemma ) {
				return [ 'value' => $lemma->getText(), 'language' => $lemma->getLanguageCode() ];
			},
			iterator_to_array( $lexeme->getLemmas() )
		);

		$result = $templating->render(
			$template,
			[
				'isInitialized' => false,
				'inEditMode' => false,
				'isSaving' => false,
				'lemmaList' => $lemmas,
				'isUnsaveable' => true
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
	private function renderLanguageAndLexicalCategoryWidget( Lexeme $lexeme ) {
		$templating = new Templating();
		$categoryTemplate = file_get_contents( __DIR__ . VueTemplates::CATEGORY_WIDGET );

		$languageId = $lexeme->getLanguage();
		$lexicalCategoryId = $lexeme->getLexicalCategory();

		$result = $templating->render(
			$categoryTemplate,
			[
				'isInitialized' => false,
				'inEditMode' => false,
				'isSaving' => false,
				'formattedLanguage' => $this->idFormatter->formatEntityId( $languageId ),
				'language' => $languageId->getSerialization(),
				'formattedLexicalCategory' => $this->idFormatter->formatEntityId(
					$lexicalCategoryId
				),
				'lexicalCategory' => $lexicalCategoryId->getSerialization()
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
