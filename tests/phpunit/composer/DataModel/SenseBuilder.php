<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Sense;
use Wikibase\Lexeme\DataModel\SenseId;

class SenseBuilder {

	/**
	 * @var SenseId
	 */
	private $senseId;

	/**
	 * @var Term[] Indexed by language
	 */
	private $glosses = [];

	/**
	 * @param SenseId|string $senseId
	 * @return SenseBuilder
	 */
	public static function havingId( $senseId ) {
		$senseId = $senseId instanceof SenseId ? $senseId : new SenseId( $senseId );
		return new self( $senseId );
	}

	private function __construct( SenseId $senseId ) {
		$this->senseId = $senseId;
	}

	/**
	 * @param string $language
	 * @param string $gloss
	 *
	 * @return SenseBuilder
	 */
	public function withGloss( $language, $gloss ) {
		$result = clone $this;
		if ( isset( $result->glosses[$language] ) ) {
			throw new \LogicException(
				"Gloss with language '{$language}' is already set. "
				. "You're not allowed overwriting it."
			);
		}
		$result->glosses[$language] = new Term( $language, $gloss );
		return $result;
	}

	public function build() {
		return new Sense( $this->senseId, new TermList( $this->glosses ), new StatementList( [] ) );
	}

}
