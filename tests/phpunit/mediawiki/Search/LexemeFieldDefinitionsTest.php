<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Search;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\Search\LexemeFieldDefinitions;
use Wikibase\Repo\Search\Elastic\Fields\StatementProviderFieldDefinitions;

/**
 * @covers \Wikibase\Lexeme\Search\LexemeFieldDefinitions
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LexemeFieldDefinitionsTest extends TestCase {

	public function testGetFields() {
		$fieldDefinitions = new LexemeFieldDefinitions(
			new StatementProviderFieldDefinitions( [], [] ),
			$this->getMock( EntityLookup::class ),
			new PropertyId( 'P123' )
		);

		$expectedKeys = [
				'statement_keywords',
				'statement_count',
				'lemma',
				'lexeme_forms',
				'lexeme_language',
				'lexical_category',
			];

		$this->assertEquals( $expectedKeys, array_keys( $fieldDefinitions->getFields() ),
			'Fields do not match', 0, 1, true );
	}

	public function testGetFieldsNoCode() {
		$fieldDefinitions = new LexemeFieldDefinitions(
			new StatementProviderFieldDefinitions( [], [] ),
			$this->getMock( EntityLookup::class ),
			null
		);

		$expectedKeys = [
			'statement_keywords',
			'statement_count',
			'lemma',
			'lexeme_forms',
			'lexeme_language',
			'lexical_category',
		];

		$this->assertEquals( $expectedKeys, array_keys( $fieldDefinitions->getFields() ),
			'Fields do not match', 0, 1, true );
	}

}
