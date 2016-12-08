<?php

namespace Wikibase\Lexeme\Content;

use InvalidArgumentException;
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
	 * @var EntityHolder
	 */
	private $lexemeHolder;

	/**
	 * @param EntityHolder $lexemeHolder
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( EntityHolder $lexemeHolder ) {
		parent::__construct( self::CONTENT_MODEL_ID );

		Assert::parameter(
			$lexemeHolder->getEntityType() === Lexeme::ENTITY_TYPE,
			'$lexemeHolder',
			'$lexemeHolder must contain a Lexeme entity'
		);

		$this->lexemeHolder = $lexemeHolder;
	}

	/**
	 * @see EntityContent::getEntity
	 *
	 * @return Lexeme
	 */
	public function getEntity() {
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
	 * @return EntityHolder
	 */
	protected function getEntityHolder() {
		return $this->lexemeHolder;
	}

	/**
	 * @see EntityContent::isStub
	 *
	 * @return bool
	 */
	public function isStub() {
		return !$this->isRedirect()
			&& !$this->getEntity()->isEmpty()
			&& ( is_null( $this->getEntity()->getLemmas() )
				|| !$this->getEntity()->getLemmas()->isEmpty() )
			&& ( is_null( $this->getEntity()->getLexicalCategory() )
				|| !$this->getEntity()->getLexicalCategory()->isEmpty() )
			&& $this->getEntity()->getStatements()->isEmpty();
	}

}
