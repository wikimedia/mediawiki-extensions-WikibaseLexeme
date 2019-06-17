<?php

namespace Wikibase\Lexeme\MediaWiki\Api;

use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpRemoveSense;
use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * @license GPL-2.0-or-later
 */
class RemoveSenseRequest {

	private $senseId;

	/**
	 * @var int|null
	 */
	private $baseRevId;

	/**
	 * @param SenseId $senseId
	 * @param int|null $baseRevId
	 */
	public function __construct( SenseId $senseId, $baseRevId ) {
		$this->senseId = $senseId;
		$this->baseRevId = $baseRevId;
	}

	public function getChangeOp(): ChangeOpRemoveSense {
		return new ChangeOpRemoveSense( $this->senseId );
	}

	public function getSenseId(): SenseId {
		return $this->senseId;
	}

	/**
	 * @return int|null
	 */
	public function getBaseRevId() {
		return $this->baseRevId;
	}

}
