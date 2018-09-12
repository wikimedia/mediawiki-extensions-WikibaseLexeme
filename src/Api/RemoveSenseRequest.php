<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\Lexeme\ChangeOp\ChangeOpRemoveSense;
use Wikibase\Lexeme\DataModel\SenseId;

/**
 * @license GPL-2.0-or-later
 */
class RemoveSenseRequest {

	private $senseId;

	public function __construct( SenseId $senseId ) {
		$this->senseId = $senseId;
	}

	public function getChangeOp(): ChangeOpRemoveSense {
		return new ChangeOpRemoveSense( $this->senseId );
	}

	public function getSenseId(): SenseId {
		return $this->senseId;
	}

}
