<?php

namespace Wikibase\Lexeme\Content;

use InvalidArgumentException;
use LogicException;
use Wikibase\Content\EntityHolder;
use Wikibase\EntityContent;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DemoData;
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

		/** @var Lexeme $lexeme */
		$lexeme = $this->lexemeHolder->getEntity( Lexeme::class );

		// TODO: This is a test dummy that must be removed later
		$id = $lexeme->getId()->getSerialization();
		if ( $id === DemoData\Id::L_HARD ) {
			( new DemoData\HardLexemePopulator() )->populate( $lexeme );
		} elseif ( $id === DemoData\Id::L_LEITER ) {
			( new DemoData\LeiterLexemePopulator() )->populate( $lexeme );
		} elseif ( $id === DemoData\Id::L_ASK_1 ) {
			( new DemoData\AskOut1Populator() )->populate( $lexeme );
		} elseif ( $id === DemoData\Id::L_ASK_2 ) {
			( new DemoData\AskOut2Populator() )->populate( $lexeme );
		} else {
			( new DemoData\DefaultPopulator() )->populate( $lexeme );
		}

		return $lexeme;
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
