<?php

namespace Wikibase\Lexeme\MediaWiki\Scribunto;

use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LibraryBase;
use MediaWiki\Parser\ParserOutput;
use Wikibase\Client\ParserOutput\ParserOutputProvider;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lexeme\Domain\Model\LexemeSubEntityId;

/**
 * @license GPL-2.0-or-later
 */
abstract class Scribunto_LuaWikibaseLexemeAbstractEntityLibrary
	extends LibraryBase implements ParserOutputProvider {

	/** @var UsageAccumulator|null */
	private $usageAccumulator;

	/** @var EntityIdParser|null */
	private $entityIdParser;

	private function getUsageAccumulator(): UsageAccumulator {
		if ( $this->usageAccumulator === null ) {
			$this->usageAccumulator = WikibaseClient::getUsageAccumulatorFactory()
				->newFromParserOutputProvider( $this );
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

	public function getParserOutput(): ParserOutput {
		return $this->getParser()->getOutput();
	}
}
