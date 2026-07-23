<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Presentation\RestSerialization;

use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Repo\Domains\Statements\Application\Serialization\StatementListSerializer;

/**
 * @license GPL-2.0-or-later
 */
class SensesSerializer {

	public function __construct(
		private GlossesSerializer $glossesSerializer,
		private StatementListSerializer $statementListSerializer,
	) {
	}

	public function serialize( Senses $senses ): array {
		$result = [];
		foreach ( $senses as $sense ) {
			$result[] = [
				'id' => $sense->id->getSerialization(),
				'glosses' => $this->glossesSerializer->serialize( $sense->glosses ),
				'statements' => $this->statementListSerializer->serialize( $sense->statements ),
			];
		}
		return $result;
	}

}
