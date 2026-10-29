<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess;

use Wikibase\Lexeme\Domain\Services\LexemeJsonPatcher;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ConvertArrayObjectsToArray;
use Wikibase\Repo\Domains\Crud\Domain\Services\JsonPatcher;

/**
 * @license GPL-2.0-or-later
 */
class JsonDiffLexemeJsonPatcher implements LexemeJsonPatcher {

	public function __construct( private JsonPatcher $jsonPatcher ) {
	}

	/**
	 * @inheritDoc
	 */
	public function patch( array $target, array $patch ): mixed {
		return $this->jsonPatcher->patch( ConvertArrayObjectsToArray::execute( $target ), $patch );
	}

}
