<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Presentation\RestSerialization;

use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;

/**
 * @license GPL-2.0-or-later
 */
class SensesSerializer {

	public function __construct( private SenseSerializer $senseSerializer ) {
	}

	public function serialize( Senses $senses ): array {
		$result = [];
		foreach ( $senses as $sense ) {
			$result[] = $this->senseSerializer->serialize( $sense );
		}
		return $result;
	}

}
