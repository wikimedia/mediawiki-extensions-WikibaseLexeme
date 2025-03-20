<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\DataModel;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @method static NewForm havingRepresentation (string $language, string $representation)
 * @method static NewForm havingLexeme (Lexeme | LexemeId | string $lexeme)
 * @method static NewForm havingId (FormId | string $formId)
 * @method static NewForm havingStatement (Statement | Snak | NewStatement $statement)
 * @method static NewForm havingGrammaticalFeature (ItemId | string $itemId)
 *
 * @license GPL-2.0-or-later
 */
class NewForm {

	private string $lexemeId = 'L1';

	private ?string $formId = null;

	/**
	 * @var string[] Indexed by language
	 */
	private array $representations = [];

	/**
	 * @var Statement[]
	 */
	private array $statements = [];

	/**
	 * @var ItemId[]
	 */
	private array $grammaticalFeatures = [];

	public static function any(): self {
		return new self();
	}

	private function __construct() {
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

	/**
	 * @param FormId|string $formId
	 */
	public function andId( $formId ): self {
		if ( $this->formId ) {
			throw new \LogicException( 'Form ID is already set. You are not allowed to change it' );
		}

		$result = clone $this;

		if ( $formId instanceof FormId ) {
			[ , $formId ] = explode( '-', $formId->getSerialization(), 2 );
		}

		$result->formId = $formId;

		return $result;
	}

	public function andRepresentation( string $language, string $representation ): self {
		if ( array_key_exists( $language, $this->representations ) ) {
			throw new \LogicException(
				"Representation for '{$language}' is already set. You are not allowed to change it"
			);
		}
		$result = clone $this;
		$result->representations[$language] = $representation;
		return $result;
	}

	/**
	 * @param ItemId|string $itemId
	 */
	public function andGrammaticalFeature( $itemId ): self {
		$result = clone $this;
		if ( is_string( $itemId ) ) {
			$itemId = new ItemId( $itemId );
		}
		$result->grammaticalFeatures[] = $itemId;

		return $result;
	}

	/**
	 * @param Statement|Snak|NewStatement $statement
	 */
	public function andStatement( $statement ): self {
		$result = clone $this;
		if ( $statement instanceof NewStatement ) {
			$statement = $statement->build();
		} elseif ( $statement instanceof Snak ) {
			$statement = new Statement( $statement );
		}
		$result->statements[] = $statement;

		return $result;
	}

	public function build(): Form {
		$formId = $this->formId ?: $this->newRandomFormIdFormPart();

		if ( empty( $this->representations ) ) {
			$representations = new TermList( [
				new Term( 'qqq', 'representation' . mt_rand( 0, mt_getrandmax() ) ),
			] );
		} else {
			$representations = new TermList();
			foreach ( $this->representations as $language => $representation ) {
				$representations->setTextForLanguage( $language, $representation );
			}
		}

		return new Form(
			new FormId( $this->lexemeId . '-' . $formId ),
			$representations,
			$this->grammaticalFeatures,
			new StatementList( ...$this->statements )
		);
	}

	public static function __callStatic( string $name, array $arguments ): self {
		$result = new self();
		$methodName = str_replace( 'having', 'and', $name );
		return $result->$methodName( ...$arguments );
	}

	public function __clone() {
		$this->statements = $this->cloneArrayOfObjects( $this->statements );
	}

	private function newRandomFormIdFormPart(): string {
		return 'F' . mt_rand( 1, mt_getrandmax() );
	}

	/**
	 * @phpcs:ignore MediaWiki.Commenting.FunctionComment.ObjectTypeHintParam
	 * @param object[] $objects
	 *
	 * @phpcs:ignore MediaWiki.Commenting.FunctionComment.ObjectTypeHintReturn
	 * @return object[]
	 */
	private function cloneArrayOfObjects( array $objects ): array {
		$result = [];
		foreach ( $objects as $object ) {
			$result[] = clone $object;
		}
		return $result;
	}

}
