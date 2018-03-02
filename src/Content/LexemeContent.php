<?php

namespace Wikibase\Lexeme\Content;

use InvalidArgumentException;
use LogicException;
use Wikibase\Content\EntityHolder;
use Wikibase\EntityContent;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DemoData\Id as DemoDataId;
use Wikibase\Lexeme\DemoData\AskOut1Populator;
use Wikibase\Lexeme\DemoData\AskOut2Populator;
use Wikibase\Lexeme\DemoData\AskOut3Populator;
use Wikibase\Lexeme\DemoData\HardLexemePopulator;
use Wikibase\Lexeme\DemoData\LeiterLexemePopulator;
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

		/** @var Lexeme $lexeme */
		$lexeme = $this->lexemeHolder->getEntity( Lexeme::class );

		// TODO: This is a test dummy that must be removed later
		$id = $lexeme->getId()->getSerialization();
		if ( $id === DemoDataId::L_HARD ) {
			( new HardLexemePopulator() )->populate( $lexeme );
		} elseif ( $id === DemoDataId::L_LEITER ) {
			( new LeiterLexemePopulator() )->populate( $lexeme );
		} elseif ( $id === DemoDataId::L_ASK_1 ) {
			( new AskOut1Populator() )->populate( $lexeme );
		} elseif ( $id === DemoDataId::L_ASK_2 ) {
			( new AskOut2Populator() )->populate( $lexeme );
		} elseif ( $id === DemoDataId::L_ASK_OUT ) {
			( new AskOut3Populator() )->populate( $lexeme );
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
