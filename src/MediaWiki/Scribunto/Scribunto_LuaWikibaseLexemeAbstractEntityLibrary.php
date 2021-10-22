<?php

namespace Wikibase\Lexeme\MediaWiki\Scribunto;

use Scribunto_LuaLibraryBase;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lexeme\Domain\Model\LexemeSubEntityId;

/**
 * @license GPL-2.0-or-later
 */
abstract class Scribunto_LuaWikibaseLexemeAbstractEntityLibrary extends Scribunto_LuaLibraryBase {

	/** @var UsageAccumulator|null */
	private $usageAccumulator;

	/** @var EntityIdParser|null */
	private $entityIdParser;

	private function getUsageAccumulator(): UsageAccumulator {
		if ( $this->usageAccumulator === null ) {
			$parserOutput = $this->getParser()->getOutput();
			$this->usageAccumulator = WikibaseClient::getUsageAccumulatorFactory()
				->newFromParserOutput( $parserOutput );
		}

		return $this->usageAccumulator;
	}

	private function getEntityIdParser(): EntityIdParser {
		if ( $this->entityIdParser === null ) {
			$this->entityIdParser = WikibaseClient::getEntityIdParser();
		}
		return $this->entityIdParser;
	}

	public function addAllUsage( string $prefixedEntityId ) {
		$entityId = $this->getEntityIdParser()->parse( $prefixedEntityId );
		if ( $entityId instanceof LexemeSubEntityId ) {
			$this->getUsageAccumulator()->addAllUsage( $entityId->getLexemeId() );
		} else {
			$this->getUsageAccumulator()->addAllUsage( $entityId );
		}
	}

}
