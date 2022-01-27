<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseAdd;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Repo\ChangeOp\ChangeOp;

/**
 * @license GPL-2.0-or-later
 */
class AddSenseRequest {

	private $lexemeId;
	private $editSenseChangeOp;
	private $baseRevId;

	public function __construct(
		LexemeId $lexemeId,
		ChangeOp $editSenseChangeOp,
		?int $baseRevId
	) {
		$this->lexemeId = $lexemeId;
		$this->editSenseChangeOp = $editSenseChangeOp;
		$this->baseRevId = $baseRevId;
	}

	public function getChangeOp(): ChangeOpSenseAdd {
		return new ChangeOpSenseAdd( $this->editSenseChangeOp, new GuidGenerator() );
	}

	public function getLexemeId(): LexemeId {
		return $this->lexemeId;
	}

	public function getBaseRevId(): ?int {
		return $this->baseRevId;
	}

}
