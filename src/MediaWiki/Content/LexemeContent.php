<?php

namespace Wikibase\Lexeme\MediaWiki\Content;

use InvalidArgumentException;
use LogicException;
use MediaWiki\MediaWikiServices;
use Title;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Presentation\Content\LemmaTextSummaryFormatter;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\Content\EntityHolder;
use Wikimedia\Assert\Assert;

/**
 * TODO: Can this be split into two classes? (LexemeRedirectContent, LexemeContent)
 *
 * @license GPL-2.0-or-later
 */
class LexemeContent extends EntityContent {

	public const CONTENT_MODEL_ID = 'wikibase-lexeme';

	/**
	 * @var EntityHolder|null
	 */
	private $lexemeHolder;

	/**
	 * @var EntityRedirect
	 */
	private $redirect;

	/**
	 * @var Title
	 */
	private $redirectTitle;

	/**
	 * @var LemmaTextSummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @param EntityHolder|null $lexemeHolder
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityHolder $lexemeHolder = null,
		EntityRedirect $redirect = null,
		Title $redirectTitle = null
	) {
		parent::__construct( self::CONTENT_MODEL_ID );

		if ( $lexemeHolder !== null && $redirect !== null ) {
			throw new InvalidArgumentException(
				'Cannot contain lexeme and be a redirect at the same time'
			);
		}

		if ( $lexemeHolder !== null ) {
			$this->constructAsLexemeContent( $lexemeHolder );
		} elseif ( $redirect !== null ) {
			$this->constructAsRedirect( $redirect, $redirectTitle );
		}

		$this->summaryFormatter = new LemmaTextSummaryFormatter(
			MediaWikiServices::getInstance()->getContentLanguage()
		);
	}

	public static function newFromRedirect( $redirect, $title ) {
		return new self( null, $redirect, $title );
	}

	protected function getIgnoreKeysForFilters() {
		// FIXME: This was the default list of keys as extracted form EntityContent
		// Lexemes should probably have different keys set here but we need to know what
		// is already being used in AbuseFilter on wikidata.org
		// https://phabricator.wikimedia.org/T205254
		return [
			'language',
			'site',
			'type',
			'hash'
		];
	}

	/**
	 * @see EntityContent::getEntity
	 *
	 * @return Lexeme
	 */
	public function getEntity() {
		if ( !$this->lexemeHolder ) {
			throw new LogicException( 'This content object is empty!' );
		}

		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->lexemeHolder->getEntity( Lexeme::class );
	}

	/**
	 * @see EntityContent::isCountable
	 *
	 * @param bool|null $hasLinks
	 *
	 * @return bool
	 */
	public function isCountable( $hasLinks = null ) {
		return !$this->isRedirect() && !$this->getEntity()->isEmpty();
	}

	/**
	 * @see EntityContent::getEntityHolder
	 *
	 * @return EntityHolder|null
	 */
	public function getEntityHolder() {
		return $this->lexemeHolder;
	}

	public function getEntityRedirect() {
		return $this->redirect;
	}

	public function getRedirectTarget() {
		return $this->redirectTitle;
	}

	/**
	 * @see EntityContent::isValid
	 *
	 * @return bool
	 */
	public function isValid() {
		return parent::isValid()
			&& ( $this->isRedirect()
			|| $this->getEntity()->isSufficientlyInitialized() );
	}

	/**
	 * @see EntityContent::getEntityPageProperties
	 *
	 * Records the number of statements in the 'wb-claims' key.
	 * Counts all statements on the page, including statements of forms and senses.
	 *
	 * @return array A map from property names to property values.
	 */
	public function getEntityPageProperties() {
		$properties = parent::getEntityPageProperties();
		$lexeme = $this->getEntity();

		$count = $lexeme->getStatements()->count();

		foreach ( $lexeme->getForms()->toArrayUnordered() as $form ) {
			$count += $form->getStatements()->count();
		}

		foreach ( $lexeme->getSenses()->toArrayUnordered() as $sense ) {
			$count += $sense->getStatements()->count();
		}

		$properties['wb-claims'] = (string)$count;

		$properties['wbl-senses'] = (string)$lexeme->getSenses()->count();
		$properties['wbl-forms'] = (string)$lexeme->getForms()->count();

		return $properties;
	}

	/**
	 * Make text representation of the Lexeme as list of all lemmas and form representations.
	 * @see EntityContent::getTextForSearchIndex()
	 */
	public function getTextForSearchIndex() {
		if ( $this->isRedirect() ) {
			return '';
		}

		$lexeme = $this->getEntity();
		// Note: this assumes that only one lemma per language exists
		$terms = array_values( $lexeme->getLemmas()->toTextArray() );

		foreach ( $lexeme->getForms()->toArray() as $form ) {
			$terms = array_merge( $terms,
				array_values( $form->getRepresentations()->toTextArray() ) );
		}

		return implode( ' ', $terms );
	}

	private function constructAsLexemeContent( EntityHolder $lexemeHolder ) {
		Assert::parameter(
			$lexemeHolder->getEntityType() === Lexeme::ENTITY_TYPE,
			'$lexemeHolder',
			'$lexemeHolder must contain a Lexeme entity'
		);

		$this->lexemeHolder = $lexemeHolder;
	}

	private function constructAsRedirect( EntityRedirect $redirect, Title $redirectTitle = null ) {
		if ( $redirectTitle === null ) {
			throw new InvalidArgumentException(
				'$redirect and $redirectTitle must both be provided or both be empty.'
			);
		}

		$this->redirect = $redirect;
		$this->redirectTitle = $redirectTitle;
	}

	/**
	 * Returns a textual representation of the content suitable for use in edit summaries and log messages.
	 *
	 * @param int $maxLength maximum length of the summary text
	 * @return string
	 */
	public function getTextForSummary( $maxLength = 250 ) {
		if ( $this->isRedirect() ) {
			return $this->getRedirectText();
		}

		return $this->summaryFormatter->getSummary(
			$this->getEntity()->getLemmas(),
			$maxLength
		);
	}
}
