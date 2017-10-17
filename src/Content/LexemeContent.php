<?php

namespace Wikibase\Lexeme\Content;

use InvalidArgumentException;
use LogicException;
use Wikibase\Content\EntityHolder;
use Wikibase\EntityContent;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
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

}
