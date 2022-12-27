<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataAccess\LexemeDescription;

// phpcs:disable Generic.Files.LineLength.TooLong
// We need long template strings here...
/**
 * @covers \Wikibase\Lexeme\DataAccess\LexemeDescription
 *
 * @license GPL-2.0-or-later
 */
class LexemeDescriptionTest extends TestCase {
	use LexemeDescriptionTestCase;

	private $labels = [
		'Q1' => [
			'en' => 'English',
			'fr' => 'Anglais',
		],
		'Q2' => [
			'en' => 'noun',
			'fr' => 'nom',
		],
		'Q3' => [
			'en' => 'singular',
			'fr' => 'singulier'
		],
		'Q4' => [
			'en' => 'plural',
		],
		'Q5' => [
			'en' => 'nominative',
		],
		'Q6' => [
			'ru' => 'настоящее время'
		]
	];

	public function termResultsProvider() {
		return [
			"one form" => [
				'en',
				'en',
				[ 'Q1', 'Q2', 'Q3' ],
				[
					'lexeme-id' => 'L1',
					'lemma' => 'duck',
					'features' => [ 'Q3' ],
					'language' => 'Q1',
					'category' => 'Q2',
				],
				'singular for: duck (L1): English, noun'
			],
			// Many features
			"multiple features" => [
				'en',
				'en',
				[ 'Q1', 'Q2', 'Q3', 'Q5' ],
				[
					'lexeme-id' => 'L3',
					'lemma' => 'duck',
					'features' => [ 'Q3', 'Q5' ],
					'language' => 'Q1',
					'category' => 'Q2',
				],
				'singular, nominative for: duck (L3): English, noun',
			],
			"in language" => [
				'qqx',
				'fr',
				[ 'Q1', 'Q2', 'Q3' ],
				[
					'lexeme-id' => 'L1',
					'lemma' => 'duck',
					'features' => [ 'Q3' ],
					'language' => 'Q1',
					'category' => 'Q2',
				],
				'(wikibaselexeme-form-description: singulier, duck, L1, (wikibaselexeme-description: Anglais, nom))',
			],
			// No features
			"no feature" => [
				'en',
				'en',
				[ 'Q1', 'Q2' ],
				[
					'lexeme-id' => 'L1',
					'lemma' => 'quack',
					'features' => [],
					'language' => 'Q1',
					'category' => 'Q2',
				],
				'no features for: quack (L1): English, noun',
			],
			"no label - fallback" => [
				'qqx',
				'fr',
				[ 'Q1', 'Q5', 'Q4' ],
				[
					'lexeme-id' => 'L1',
					'lemma' => 'duck',
					'features' => [ 'Q4' ],
					'language' => 'Q1',
					'category' => 'Q5',
				],
				'(wikibaselexeme-form-description: plural, duck, L1, (wikibaselexeme-description: Anglais, nominative))',
			],
			"no label" => [
				'qqx',
				'fr',
				[ 'Q1', 'Q2', 'Q6' ],
				[
					'lexeme-id' => 'L1',
					'lemma' => 'duck',
					'features' => [ 'Q6' ],
					'language' => 'Q1',
					'category' => 'Q2',
				],
				// Long line split into two
				'(wikibaselexeme-form-description: (wikibaselexeme-unknown-category), ' .
				'duck, L1, (wikibaselexeme-description: Anglais, nom))',
			],
		];
	}

	/**
	 * @dataProvider termResultsProvider
	 */
	public function testFormDescription(
		$displayLanguage,
		$termLanguage,
		array $fetchIds,
		array $data,
		$expected
	) {
		$idParser = $this->getIdParser();
		$features = array_map( static function ( $id ) use ( $idParser ) {
			return $idParser->parse( $id );
		}, $data['features'] );
		$itemIds = array_combine( $fetchIds, array_map( static function ( $id ) {
			return new ItemId( $id );
		}, $fetchIds ) );

		$termLookupFactory = $this->getTermLookupFactory( $fetchIds, $termLanguage );
		$termLookup = $termLookupFactory->newLabelDescriptionLookup(
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( $termLanguage ),
			$itemIds
		);
		$descriptionMaker = new LexemeDescription(
			$termLookup,
			$idParser,
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( $displayLanguage )
		);

		$formId = $idParser->parse( $data['lexeme-id'] );
		$desc = $descriptionMaker->createFormDescription(
			$formId, $features, $data['lemma'], $data['language'], $data['category']
		);
		$this->assertSame( $expected, $desc );
	}

}
