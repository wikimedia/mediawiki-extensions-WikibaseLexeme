<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\ChangeOp\ChangeOpAddForm;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class AddFormRequest {

	/**
	 * @var LexemeId
	 */
	private $lexemeId;

	/**
	 * @var TermList
	 */
	private $representations;

	/**
	 * @var ItemId[]
	 */
	private $grammaticalFeatures;

	/**
	 * @param LexemeId $lexemeId
	 * @param TermList $representations
	 * @param ItemId[] $grammaticalFeatures
	 */
	public function __construct(
		LexemeId $lexemeId,
		TermList $representations,
		array $grammaticalFeatures
	) {
		Assert::parameterElementType( ItemId::class, $grammaticalFeatures, '$grammaticalFeatures' );
		Assert::parameter( !$representations->isEmpty(), '$representations', 'should not be empty' );

		$this->lexemeId = $lexemeId;
		$this->representations = $representations;
		$this->grammaticalFeatures = $grammaticalFeatures;
	}

	/**
	 * @return ChangeOpAddForm
	 */
	public function getChangeOp() {
		return new ChangeOpAddForm( $this->representations, $this->grammaticalFeatures );
	}

	/**
	 * @return LexemeId
	 */
	public function getLexemeId() {
		return $this->lexemeId;
	}

	/**
	 * @param Lexeme $lexeme
	 *
	 * @return Form
	 */
	public function addFormTo( Lexeme $lexeme ) {
		//FIXME Test it
		//FIXME Assert on ID equality
		return $lexeme->addForm( $this->representations, $this->grammaticalFeatures );
	}

}
