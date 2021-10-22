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
class Scribunto_LuaWikibaseLexemeEntityFormLibrary extends Scribunto_LuaLibraryBase {

	/** @var UsageAccumulator|null */
	private $usageAccumulator;

	/** @var EntityIdParser|null */
	private $entityIdParser;

	public function getUsageAccumulator(): UsageAccumulator {
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

	/**
	 * Register the mw.wikibase.lexeme.entity.form.lua library.
	 */
	public function register() {
		// These functions will be exposed to the Lua module.
		// They are member functions on a Lua table which is private to the module, thus
		// these can't be called from user code, unless explicitly exposed in Lua.
		$lib = [
			'addAllUsage' => [ $this, 'addAllUsage' ],
		];

		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.wikibase.lexeme.entity.form.lua', $lib, []
		);
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
