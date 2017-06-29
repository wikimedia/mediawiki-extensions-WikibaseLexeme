<?php

namespace Wikibase\Lexeme\Content;

use InvalidArgumentException;
use LogicException;
use Wikibase\Content\EntityHolder;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\EntityContent;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\DataModel\SenseId;
use Wikimedia\Assert\Assert;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;

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
			new Form( new FormId( 'F1' ), 'A', [] ),
			new Form( new FormId( 'F2' ), 'B', $grammaticalFeatures1, $statements1 ),
			new Form( new FormId( 'F3' ), 'C', $grammaticalFeatures2, $statements2 ),
		];

		$lexeme->setForms( $forms );

		$senses = [
			new Sense(
				new SenseId( 'S1' ),
				new TermList( [
					new Term(
						'en',
						'A mammal, Capra aegagrus hircus, and similar species of the genus Capra.'
					),
					new Term(
						'fr',
						'Un mammale, Capra aegagruse hircuse, et similare species de un genuse Capra.'
					),
				] ),
				new StatementList()
			),
			new Sense(
				new SenseId( 'S2' ),
				new TermList( [ new Term( 'en', 'A scapegoat.' ) ] ),
				new StatementList( [
					new Statement(
						new PropertyValueSnak(
							new PropertyId( 'P900' ),
							new StringValue( 'informal' )
						),
						null,
						null,
						'guid900'
					),
				] )
			)
		];

		$lexeme->setSenses( $senses );

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
