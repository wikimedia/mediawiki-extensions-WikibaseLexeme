<?php

namespace Wikibase\Lexeme\Api;

use Wikibase\Lexeme\ChangeOp\ChangeOpRemoveSense;
use Wikibase\Lexeme\DataModel\SenseId;

/**
 * @license GPL-2.0-or-later
 */
class RemoveSenseRequest {

	/**
	 * @var SenseId
	 */
	private $senseId;

	public function __construct( SenseId $senseId ) {
		$this->senseId = $senseId;
	}

	/**
	 * @return ChangeOpRemoveSense
	 */
	public function getChangeOp() {
		return new ChangeOpRemoveSense( $this->senseId );
	}

	/**
	 * @return SenseId
	 */
	public function getSenseId() {
		return $this->senseId;
	}

}
