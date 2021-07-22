<?php

namespace Wikibase\Lexeme\MediaWiki\Scribunto;

use Scribunto_LuaLibraryBase;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\Lexeme\Domain\Model\Lexeme;

/**
 * @license GPL-2.0-or-later
 */
class Scribunto_LuaWikibaseLexemeLibrary extends Scribunto_LuaLibraryBase {

	/** @var UsageAccumulator|null */
	private $usageAccumulator;

	/** @var EntityIdParser|null */
	private $entityIdParser;

	/** @var EntityLookup|null */
	private $entityLookup;

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

	private function getEntityLookup(): EntityLookup {
		if ( $this->entityLookup === null ) {
			$this->entityLookup = WikibaseClient::getRestrictedEntityLookup();
		}
		return $this->entityLookup;
	}

	/**
	 * Register the mw.wikibase.lexeme.lua library.
	 */
	public function register() {
		// These functions will be exposed to the Lua module.
		// They are member functions on a Lua table which is private to the module, thus
		// these can't be called from user code, unless explicitly exposed in Lua.
		$lib = [
			'getLemmas' => [ $this, 'getLemmas' ],
			'getLanguage' => [ $this, 'getLanguage' ],
			'getLexicalCategory' => [ $this, 'getLexicalCategory' ],
		];

		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.wikibase.lexeme.lua', $lib, []
		);
	}

	public function getLemmas( $prefixedEntityId ) {
		$this->checkType( 'getLemmas', 1, $prefixedEntityId, 'string' );

		$lexeme = $this->getLexeme( $prefixedEntityId );
		if ( $lexeme === null ) {
			return [ null ];
		}

		$terms = [ 'placeholder because Lua tables start at 1' ];
		foreach ( $lexeme->getLemmas() as $lemma ) {
			$terms[] = [ 1 => $lemma->getText(), 2 => $lemma->getLanguageCode() ];
		}
		unset( $terms[0] );
		return [ $terms ];
	}

	public function getLanguage( $prefixedEntityId ) {
		$this->checkType( 'getLanguage', 1, $prefixedEntityId, 'string' );

		$lexeme = $this->getLexeme( $prefixedEntityId );
		if ( $lexeme === null ) {
			return [ null ];
		}

		return [ $lexeme->getLanguage()->getSerialization() ];
	}

	public function getLexicalCategory( $prefixedEntityId ) {
		$this->checkType( 'getLexicalCategory', 1, $prefixedEntityId, 'string' );

		$lexeme = $this->getLexeme( $prefixedEntityId );
		if ( $lexeme === null ) {
			return [ null ];
		}

		return [ $lexeme->getLexicalCategory()->getSerialization() ];
	}

	private function getLexeme( string $prefixedEntityId ): ?Lexeme {
		try {
			$entityId = $this->getEntityIdParser()->parse( $prefixedEntityId );
		} catch ( EntityIdParsingException $e ) {
			return null;
		}

		// TODO support fine-grained usage tracking
		$this->getUsageAccumulator()->addAllUsage( $entityId );

		try {
			$entity = $this->getEntityLookup()->getEntity( $entityId );
		} catch ( EntityLookupException $e ) {
			return null;
		}

		if ( !$entity instanceof Lexeme ) {
			return null;
		}

		return $entity;
	}

}
