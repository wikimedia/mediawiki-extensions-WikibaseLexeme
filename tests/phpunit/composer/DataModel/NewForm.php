<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Repo\Tests\NewStatement;

/**
 * @method static self havingRepresentation (string $language, string $representation)
 * @method static self havingId (FormId | string $formId)
 * @method static self havingStatement (Statement | Snak | NewStatement $statement)
 * @method static self havingGrammaticalFeature (ItemId | string $itemId)
 */
class NewForm {

	/**
	 * @var FormId
	 */
	private $formId;

	/**
	 * @var string[] Indexed by language
	 */
	private $representations = [];

	/**
	 * @var Statement[]
	 */
	private $statements = [];

	/**
	 * @var ItemId[]
	 */
	private $grammaticalFeatures = [];

	/**
	 * @return self
	 */
	public static function any() {
		return new self();
	}

	private function __construct() {
	}

	/**
	 * @param FormId|string $formId
	 *
	 * @return self
	 */
	public function andId( $formId ) {
		if ( $this->formId ) {
			throw new \LogicException( 'Form ID is already set. You are not allowed to change it' );
		}
		$result = clone $this;

		if ( is_string( $formId ) ) {
			$formId = new FormId( $formId );
		}

		$result->formId = $formId;

		return $result;
	}

	/**
	 * @param string $language
	 * @param string $representation
	 *
	 * @return self
	 */
	public function andRepresentation( $language, $representation ) {
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
	 *
	 * @return self
	 */
	public function andGrammaticalFeature( $itemId ) {
		$result = clone $this;
		if ( is_string( $itemId ) ) {
			$itemId = new ItemId( $itemId );
		}
		$result->grammaticalFeatures[] = $itemId;

		return $result;
	}

	/**
	 * @param Statement|Snak|NewStatement $statement
	 *
	 * @return self
	 */
	public function andStatement( $statement ) {
		$result = clone $this;
		if ( $statement instanceof NewStatement ) {
			$statement = $statement->build();
		} elseif ( $statement instanceof Snak ) {
			$statement = new Statement( $statement );
		}
		$result->statements[] = $statement;

		return $result;
	}

	/**
	 * @return Form
	 */
	public function build() {
		$formId = $this->formId ?: $this->newRandomFormId();
		if ( empty( $this->representations ) ) {
			$representations = new TermList( [
				new Term( 'qqq', 'representation' . mt_rand( 0, mt_getrandmax() ) )
			] );
		} else {
			$representations = new TermList();
			foreach ( $this->representations as $language => $representation ) {
				$representations->setTextForLanguage( $language, $representation );
			}
		}
		return new Form(
			$formId,
			$representations,
			$this->grammaticalFeatures,
			new StatementList( $this->statements )
		);
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return self
	 */
	public static function __callStatic( $name, $arguments ) {
		$result = new self();
		$methodName = str_replace( 'having', 'and', $name );
		return call_user_func_array( [ $result, $methodName ], $arguments );
	}

	public function __clone() {
		$this->statements = $this->cloneArrayOfObjects( $this->statements );
	}

	/**
	 * @return FormId
	 */
	private function newRandomFormId() {
		return new FormId( 'F' . mt_rand( 1, mt_getrandmax() ) );
	}

	/**
	 * @param object[] $objects
	 *
	 * @return object[]
	 */
	private function cloneArrayOfObjects( array $objects ) {
		$result = [];
		foreach ( $objects as $object ) {
			$result[] = clone $object;
		}
		return $result;
	}

}
