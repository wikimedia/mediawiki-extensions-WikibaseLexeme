<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Presentation\RestSerialization;

use Wikibase\Lexeme\Domain\Model\ReadModel\Sense;
use Wikibase\Repo\Domains\Statements\Application\Serialization\StatementListSerializer;

/**
 * @license GPL-2.0-or-later
 */
class SenseSerializer {

	public function __construct(
		private GlossesSerializer $glossesSerializer,
		private StatementListSerializer $statementListSerializer,
	) {
	}

	public function serialize( Sense $sense ): array {
		return [
			'id' => $sense->id->getSerialization(),
			'glosses' => $this->glossesSerializer->serialize( $sense->glosses ),
			'statements' => $this->statementListSerializer->serialize( $sense->statements ),
		];
	}

}
