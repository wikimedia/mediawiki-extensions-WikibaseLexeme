<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess;

use ArrayObject;
use Exception;
use InvalidArgumentException;
use Swaggest\JsonDiff\JsonPatch;
use Swaggest\JsonDiff\PatchTestOperationFailedException;
use Swaggest\JsonDiff\PathException;
use Wikibase\Lexeme\Domain\Services\Exceptions\PatchPathException;
use Wikibase\Lexeme\Domain\Services\Exceptions\PatchTestConditionFailedException;
use Wikibase\Lexeme\Domain\Services\LexemeJsonPatcher;

/**
 * Copy of {@link \Wikibase\Repo\Domains\Crud\Infrastructure\JsonDiffJsonPatcher}
 *
 * @license GPL-2.0-or-later
 */
class JsonDiffLexemeJsonPatcher implements LexemeJsonPatcher {

	/**
	 * @inheritDoc
	 */
	public function patch( array $target, array $patch ): mixed {
		try {
			$patchDocument = JsonPatch::import( $patch );
		} catch ( Exception ) {
			throw new InvalidArgumentException( 'Invalid patch' );
		}

		$patchDocument->setFlags( JsonPatch::TOLERATE_ASSOCIATIVE_ARRAYS );

		$target = $this->convertArrayObjectsToArray( $target );
		try {
			$patchDocument->apply( $target );
		} catch ( PatchTestOperationFailedException $e ) {
			throw new PatchTestConditionFailedException(
				$e->getMessage(),
				(array)$e->getOperation(),
				$e->getActualValue(),
			);
		} catch ( PathException $e ) {
			throw new PatchPathException( $e->getMessage(), $e->getField(), $e->getOpIndex() );
		}

		// JsonPatch (sometimes) adds/replaces new values as object, not associative array
		return $this->convertObjectsToArray( $target );
	}

	private function convertArrayObjectsToArray( iterable $serialization ): array {
		$output = [];
		foreach ( $serialization as $key => $value ) {
			if ( is_array( $value ) ) {
				$output[ $key ] = $this->convertArrayObjectsToArray( $value );
			} elseif ( $value instanceof ArrayObject ) {
				$output[ $key ] = $this->convertArrayObjectsToArray( (array)$value );
			} else {
				$output[ $key ] = $value;
			}
		}
		return $output;
	}

	private function convertObjectsToArray( mixed $serialization ): mixed {
		if ( !( is_object( $serialization ) || is_array( $serialization ) ) ) {
			return $serialization;
		}

		$output = [];
		foreach ( $serialization as $key => $value ) {
			if ( is_array( $value ) ) {
				$output[ $key ] = $this->convertObjectsToArray( $value );
			} elseif ( is_object( $value ) ) {
				$output[ $key ] = $this->convertObjectsToArray( (array)$value );
			} else {
				$output[ $key ] = $value;
			}
		}
		return $output;
	}

}
