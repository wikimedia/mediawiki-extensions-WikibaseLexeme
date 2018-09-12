<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\Lexeme\ChangeOp\ChangeOpSenseAdd;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Repo\ChangeOp\ChangeOp;

/**
 * @license GPL-2.0-or-later
 */
class AddSenseRequest {

	private $lexemeId;
	private $editSenseChangeOp;

	public function __construct(
		LexemeId $lexemeId,
		ChangeOp $editSenseChangeOp
	) {
		$this->lexemeId = $lexemeId;
		$this->editSenseChangeOp = $editSenseChangeOp;
	}

	public function getChangeOp(): ChangeOpSenseAdd {
		return new ChangeOpSenseAdd( $this->editSenseChangeOp );
	}

	public function getLexemeId(): LexemeId {
		return $this->lexemeId;
	}

}
