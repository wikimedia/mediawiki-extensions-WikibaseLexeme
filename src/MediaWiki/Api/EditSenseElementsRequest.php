<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Repo\ChangeOp\ChangeOp;

/**
 * @license GPL-2.0-or-later
 */
class EditSenseElementsRequest {

	private $senseId;
	private $changeOp;
	private $baseRevId;

	public function __construct( SenseId $senseId, ChangeOp $changeOp, $baseRevId ) {
		$this->senseId = $senseId;
		$this->changeOp = $changeOp;
		$this->baseRevId = $baseRevId;
	}

	public function getChangeOp(): ChangeOp {
		return $this->changeOp;
	}

	public function getSenseId(): SenseId {
		return $this->senseId;
	}

	public function getBaseRevId() {
		return $this->baseRevId;
	}

}
