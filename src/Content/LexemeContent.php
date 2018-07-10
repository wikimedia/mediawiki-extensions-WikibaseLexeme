<?php

namespace Wikibase\Lexeme\Content;

use InvalidArgumentException;
use LogicException;
use Wikibase\Content\EntityHolder;
use Wikibase\EntityContent;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class LexemeContent extends EntityContent {

	const CONTENT_MODEL_ID = 'wikibase-lexeme';

	/**
	 * @var EntityHolder|null
	 */
	private $lexemeHolder;

	/**
	 * @param EntityHolder|null $lexemeHolder
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( EntityHolder $lexemeHolder = null ) {
		parent::__construct( self::CONTENT_MODEL_ID );

		if ( $lexemeHolder !== null ) {
			Assert::parameter(
				$lexemeHolder->getEntityType() === Lexeme::ENTITY_TYPE,
				'$lexemeHolder',
				'$lexemeHolder must contain a Lexeme entity'
			);
		}

		$this->lexemeHolder = $lexemeHolder;
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
	protected function getEntityHolder() {
		return $this->lexemeHolder;
	}

	/**
	 * @see EntityContent::isValid
	 *
	 * @return bool
	 */
	public function isValid() {
		return parent::isValid()
			&& $this->getEntity()->isSufficientlyInitialized();
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

		foreach ( $lexeme->getForms()->toArray() as $form ) {
			$count += $form->getStatements()->count();
		}

		foreach ( $lexeme->getSenses()->toArray() as $sense ) {
			$count += $sense->getStatements()->count();
		}

		$properties['wb-claims'] = $count;

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

}
