<?php

namespace Wikibase\Lexeme\DataModel\Services\Diff;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\EntityPatcherStrategy;

class LexemePatcher implements EntityPatcherStrategy {

	public function canPatchEntityType( $entityType ) {
		// TODO: Implement canPatchEntityType() method.
	}

	public function patchEntity( EntityDocument $entity, EntityDiff $patch ) {
		// TODO: Implement patchEntity() method.
	}
}
