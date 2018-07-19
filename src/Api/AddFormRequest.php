<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\ChangeOp\ChangeOpFormAdd;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Repo\ChangeOp\ChangeOp;

/**
 * @license GPL-2.0-or-later
 */
class AddFormRequest {

	/**
	 * @var LexemeId
	 */
	private $lexemeId;

	/**
	 * @var ChangeOp
	 */
	private $editFormchangeOp;

	/**
	 * @param LexemeId $lexemeId
	 * @param ChangeOp $editFormchangeOp
	 */
	public function __construct(
		LexemeId $lexemeId,
		ChangeOp $editFormchangeOp
	) {
		// TODO: consider the below in appropriate validation
		// Assert::parameterElementType( ItemId::class, $grammaticalFeatures, '$grammaticalFeatures' );
		// Assert::parameter( !$representations->isEmpty(), '$representations', 'should not be empty' );

		$this->lexemeId = $lexemeId;
		$this->editFormchangeOp = $editFormchangeOp;
	}

	/**
	 * @return ChangeOpFormAdd
	 */
	public function getChangeOp() {
		return new ChangeOpFormAdd( $this->editFormchangeOp, new GuidGenerator() );
	}

	/**
	 * @return LexemeId
	 */
	public function getLexemeId() {
		return $this->lexemeId;
	}

}
