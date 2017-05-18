<?php

namespace Wikibase\Lexeme\Content;

use InvalidArgumentException;
use Wikibase\Content\EntityHolder;
use Wikibase\EntityContent;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikimedia\Assert\Assert;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lexeme\DataModel\LexemeForm;
use Wikibase\Lexeme\DataModel\LexemeFormId;

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
		/** @var Lexeme $lexeme */
		$lexeme = $this->lexemeHolder->getEntity( Lexeme::class );

		// TODO: This obviously is a dummy that must be removed
		$grammaticalFeatures1 = [ new ItemId( 'Q2' ) ];
		$grammaticalFeatures2 = [ new ItemId( 'Q2' ), new ItemId( 'Q3' ) ];
		$statements1 = new StatementList(
			[
				new Statement( new PropertyNoValueSnak( new PropertyId( 'P2' ) ), null, null, 'guid1' )
			]
		);
		$statements2 = new StatementList(
			[
				new Statement( new PropertyNoValueSnak( new PropertyId( 'P2' ) ), null, null, 'guid2' ),
				new Statement(
					new PropertyValueSnak(
						new PropertyId( 'P3' ),
						new StringValue( 'asd' )
					),
					null,
					null,
					'guid3'
				),
			]
		);

		$forms = [
			new LexemeForm( new LexemeFormId( 'F1' ), 'A', [] ),
			new LexemeForm( new LexemeFormId( 'F2' ), 'B', $grammaticalFeatures1, $statements1 ),
			new LexemeForm( new LexemeFormId( 'F3' ), 'C', $grammaticalFeatures2, $statements2 ),
		];

		$lexeme->setForms( $forms );

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
	 * @return EntityHolder
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
