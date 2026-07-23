<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Presentation\RestSerialization;

use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Repo\Domains\Statements\Application\Serialization\StatementListSerializer;

/**
 * @license GPL-2.0-or-later
 */
class LexemeSerializer {

	public function __construct(
		private LemmasSerializer $lemmasSerializer,
		private StatementListSerializer $statementListSerializer,
		private SensesSerializer $sensesSerializer,
	) {
	}

	public function serialize( Lexeme $lexeme ): array {
		return [
			'id' => $lexeme->id->getSerialization(),
			'lemmas' => $this->lemmasSerializer->serialize( $lexeme->lemmas ),
			'statements' => $this->statementListSerializer->serialize( $lexeme->statements ),
			'senses' => $this->sensesSerializer->serialize( $lexeme->senses ),
		];
	}

}
