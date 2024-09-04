<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\Unit\DataModel;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lexeme\Domain\Model\FormSet;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lexeme\Domain\Model\SenseSet;

/**
 * @method static NewLexeme havingId(LexemeId | string $lexemeId)
 *
 * @license GPL-2.0-or-later
 */
class NewLexeme {

	private ItemId $lexicalCategory;

	private ItemId $language;

	private ?LexemeId $lexemeId = null;

	/**
	 * FIXME: deceptive name
	 * @var Snak[]
	 */
	private array $statements = [];

	/**
	 * @var string[] Lemmas indexed by language
	 */
	private array $lemmas = [];

	/**
	 * @var Sense[]
	 */
	private array $senses = [];

	/**
	 * @var Form[]
	 */
	private array $forms = [];

	private const DEFAULT_ID = 'L1';

	public static function create(): self {
		return new self();
	}

	/**
	 * @param Form|NewForm $form
	 */
	public static function havingForm( $form ): self {
		$result = new self();
		return $result->withForm( $form );
	}

	/**
	 * @param Sense|NewSense $sense
	 */
	public static function havingSense( $sense ): self {
		$result = new self();
		return $result->withSense( $sense );
	}

	public function __construct() {
		$this->lexicalCategory = $this->newRandomItemId();
		$this->language = $this->newRandomItemId();
	}

	public function build(): Lexeme {
		$forms = new FormSet( $this->forms );
		$nextFormId = $forms->maxFormIdNumber() + 1;

		$senses = new SenseSet( $this->senses );
		$nextSenseId = $senses->maxSenseIdNumber() + 1;

		$lemmas = new TermList();
		foreach ( $this->lemmas as $lang => $term ) {
			$lemmas->setTextForLanguage( $lang, $term );
		}

		if ( $lemmas->isEmpty() ) {
			$lemmas->setTextForLanguage(
				$this->newRandomLanguageCode(),
				$this->newRandomLemma()
			);
		}

		$lexeme = new Lexeme(
			$this->lexemeId,
			$lemmas,
			$this->lexicalCategory,
			$this->language,
			null,
			$nextFormId,
			$forms,
			$nextSenseId,
			$senses
		);

		foreach ( $this->statements as $statement ) {
			$lexeme->getStatements()->addNewStatement( $statement );
		}

		return $lexeme;
	}

	/**
	 * @param ItemId|string $itemId
	 */
	public function withLexicalCategory( $itemId ): self {
		$result = clone $this;
		if ( !$itemId instanceof ItemId ) {
			$itemId = new ItemId( $itemId );
		}
		$result->lexicalCategory = $itemId;
		return $result;
	}

	/**
	 * @param ItemId|string $itemId
	 */
	public function withLanguage( $itemId ): self {
		$result = clone $this;
		if ( !$itemId instanceof ItemId ) {
			$itemId = new ItemId( $itemId );
		}
		$result->language = $itemId;
		return $result;
	}

	/**
	 * @param LexemeId|string $lexemeId
	 */
	public function withId( $lexemeId ): self {
		$result = clone $this;
		if ( !$lexemeId instanceof LexemeId ) {
			$lexemeId = new LexemeId( $lexemeId );
		}
		$result->lexemeId = $lexemeId;
		return $result;
	}

	// FIXME: deceptive name
	public function withStatement( Snak $mainSnak ): self {
		$result = clone $this;
		$result->statements[] = clone $mainSnak;
		return $result;
	}

	public function withLemma( string $language, string $lemma ): self {
		$result = clone $this;
		$result->lemmas[$language] = $lemma;
		return $result;
	}

	private function newRandomItemId(): ItemId {
		return new ItemId( 'Q' . mt_rand( 1, ItemId::MAX ) );
	}

	private function newRandomLanguageCode(): string {
		return $this->newRandomString( 2 );
	}

	private function newRandomLemma(): string {
		return $this->newRandomString( mt_rand( 5, 10 ) );
	}

	private function newRandomString( int $length ): string {
		$characters = 'abcdefghijklmnopqrstuvwxyz';

		return substr( str_shuffle( $characters ), 0, $length );
	}

	public function __clone() {
		$this->statements = $this->cloneArrayOfObjects( $this->statements );
		$this->forms = $this->cloneArrayOfObjects( $this->forms );
	}

	/**
	 * @param Sense|NewSense $sense
	 */
	public function withSense( $sense ): self {
		$result = clone $this;

		if ( $sense instanceof NewSense ) {
			$sense = $sense->andLexeme( $this->lexemeId ?: self::DEFAULT_ID )->build();
		} elseif ( !$sense instanceof Sense ) {
			throw new \InvalidArgumentException( '$sense has incorrect type' );
		}

		$result->senses[] = $sense;
		return $result;
	}

	/**
	 * @param Form|NewForm $form
	 */
	public function withForm( $form ): self {
		$result = clone $this;

		if ( $form instanceof NewForm ) {
			$form = $form->andLexeme( $this->lexemeId ?: self::DEFAULT_ID )->build();
		}

		$result->forms[] = $form;

		return $result;
	}

	public static function __callStatic( string $name, array $arguments ): self {
		$result = new self();
		$methodName = str_replace( 'having', 'with', $name );
		return call_user_func_array( [ $result, $methodName ], $arguments );
	}

	/**
	 * @param object[] $objects
	 *
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
