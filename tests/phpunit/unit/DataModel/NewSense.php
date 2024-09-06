<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\DataModel;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * Sense builder to use in tests
 *
 * @license GPL-2.0-or-later
 */
class NewSense {

	/**
	 * @var string the ID of the lexeme to which the sense belongs (not yet modifiable)
	 */
	private string $lexemeId = 'L1';

	/**
	 * @var string|null the sense-specific part of the sense ID, excluding the lexeme ID
	 */
	private ?string $senseId = null;

	/**
	 * @var Term[] Indexed by language
	 */
	private array $glosses = [];

	/**
	 * @var Statement[]
	 */
	private array $statements = [];

	/**
	 * @param SenseId|string $senseId
	 */
	public static function havingId( $senseId ): self {
		$senseBuilder = new self();
		if ( $senseId instanceof SenseId ) {
			$senseId = explode( '-', $senseId->getSerialization(), 2 )[1];
		}
		$senseBuilder->senseId = $senseId;
		return $senseBuilder;
	}

	public static function havingGloss( string $lang, string $gloss ): self {
		return ( new self() )->withGloss( $lang, $gloss );
	}

	/**
	 * @param Statement|Snak|PropertyId $arg
	 */
	public static function havingStatement( $arg ): self {
		return ( new self() )->withStatement( $arg );
	}

	public function __clone() {
		// Statements are mutable, so clone them individually
		$statements = [];
		foreach ( $this->statements as $statement ) {
			$statements[] = clone $statement;
		}
		$this->statements = $statements;
	}

	public function withGloss( string $language, string $gloss ): self {
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
	 * @param Statement|Snak|PropertyId|NewStatement $arg
	 */
	public function withStatement( $arg ): self {
		$result = clone $this;
		$statement = $arg;
		if ( $arg instanceof NewStatement ) {
			$statement = $arg->build();
		} elseif ( $arg instanceof PropertyId ) {
			$statement = new Statement( new PropertyNoValueSnak( $arg ) );
		} elseif ( $arg instanceof Snak ) {
			$statement = new Statement( $arg );
		}
		$result->statements[] = clone $statement;
		return $result;
	}

	/**
	 * @param Lexeme|LexemeId|string $lexeme
	 */
	public function andLexeme( $lexeme ): self {
		$result = clone $this;

		if ( $lexeme instanceof Lexeme ) {
			$lexeme = $lexeme->getId();
		}

		if ( $lexeme instanceof LexemeId ) {
			$lexeme = $lexeme->getSerialization();
		}

		$result->lexemeId = $lexeme;

		return $result;
	}

	public function build(): Sense {
		$senseId = $this->senseId ?: $this->newRandomSenseIdSensePart();

		return new Sense(
			new SenseId( $this->lexemeId . '-' . $senseId ),
			new TermList( $this->glosses ),
			new StatementList( ...$this->statements )
		);
	}

	private function newRandomSenseIdSensePart(): string {
		return 'S' . mt_rand( 1, mt_getrandmax() );
	}

}
