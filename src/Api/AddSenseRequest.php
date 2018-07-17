<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\Lexeme\ChangeOp\ChangeOpSenseAdd;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Repo\ChangeOp\ChangeOp;

/**
 * @license GPL-2.0-or-later
 */
class AddSenseRequest {

	/**
	 * @var LexemeId
	 */
	private $lexemeId;

	/**
	 * @var ChangeOp
	 */
	private $editSenseChangeOp;

	/**
	 * @param LexemeId $lexemeId
	 * @param ChangeOp $editSenseChangeOp
	 */
	public function __construct(
		LexemeId $lexemeId,
		ChangeOp $editSenseChangeOp
	) {
		$this->lexemeId = $lexemeId;
		$this->editSenseChangeOp = $editSenseChangeOp;
	}

	/**
	 * @return ChangeOpSenseAdd
	 */
	public function getChangeOp() {
		return new ChangeOpSenseAdd( $this->editSenseChangeOp );
	}

	/**
	 * @return LexemeId
	 */
	public function getLexemeId() {
		return $this->lexemeId;
	}

}
