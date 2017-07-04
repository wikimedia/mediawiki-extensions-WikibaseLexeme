<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Repo\Tests\NewStatement;

/**
 * @method static self havingRepresentation (string $representation)
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
	 * @var string
	 */
	private $representation;

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
	 * @param string $representation
	 * @return self
	 */
	public function andRepresentation( $representation ) {
		if ( $this->representation ) {
			throw new \LogicException(
				'Representation is already set. You are not allowed to change it'
			);
		}
		$result = clone $this;
		$result->representation = $representation;
		return $result;
	}

	/**
	 * @param ItemId|string $itemId
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
		$representation = $this->representation ?: 'representation' . mt_rand( 0, mt_getrandmax() );
		return new Form(
			$formId,
			$representation,
			$this->grammaticalFeatures,
			new StatementList( $this->statements )
		);
	}

	/**
	 * @param string $name
	 * @param array $arguments
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

	private function newRandomFormId() {
		return new FormId( 'F' . mt_rand( 1, mt_getrandmax() ) );
	}

	/**
	 * @param array $objects
	 * @return array
	 */
	private function cloneArrayOfObjects( array $objects ) {
		$result = [];
		foreach ( $objects as $object ) {
			$result[] = clone $object;
		}
		return $result;
	}

}
