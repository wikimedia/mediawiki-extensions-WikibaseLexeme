<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\DataModel\SenseId;

/**
 * Sense builder to use in tests
 */
class NewSense {

	/**
	 * @var SenseId
	 */
	private $senseId;

	/**
	 * @var Term[] Indexed by language
	 */
	private $glosses = [];

	/**
	 * @var Statement[]
	 */
	private $statements = [];

	/**
	 * @param SenseId|string $senseId
	 * @return NewSense
	 */
	public static function havingId( $senseId ) {
		$senseBuilder = new self();
		$senseBuilder->senseId = $senseId instanceof SenseId ? $senseId : new SenseId( $senseId );
		return $senseBuilder;
	}

	/**
	 * @param Statement|Snak|PropertyId $arg
	 * @return NewSense
	 */
	public static function havingStatement( $arg ) {
		return ( new self() )->withStatement( $arg );
	}

	private function __construct() {
		$this->senseId = $this->generateSenseId();
	}

	public function __clone() {
		// Statements are mutable, so clone them individually
		$statements = [];
		foreach ( $this->statements as $statement ) {
			$statements[] = clone $statement;
		}
		$this->statements = $statements;
	}

	/**
	 * @param string $language
	 * @param string $gloss
	 *
	 * @return NewSense
	 */
	public function withGloss( $language, $gloss ) {
		$result = clone $this;
		if ( isset( $result->glosses[$language] ) ) {
			throw new \LogicException(
				"Gloss with language '{$language}' is already set. "
				. "You're not allowed overwriting it."
			);
		}
		$result->glosses[$language] = new Term( $language, $gloss );
		return $result;
	}

	/**
	 * @param Statement|Snak|PropertyId $arg
	 *
	 * @return NewSense
	 */
	public function withStatement( $arg ) {
		$result = clone $this;
		$statement = $arg;
		if ( $arg instanceof PropertyId ) {
			$statement = new Statement( new PropertyNoValueSnak( $arg ) );
		}
		if ( $arg instanceof Snak ) {
			$statement = new Statement( $arg );
		}
		$result->statements[] = clone $statement;
		return $result;
	}

	public function build() {
		return new Sense(
			$this->senseId,
			new TermList( $this->glosses ),
			new StatementList( $this->statements )
		);
	}

	private function generateSenseId() {
		return new SenseId( 'S' . mt_rand( 1, 4e9 ) );
	}

}
