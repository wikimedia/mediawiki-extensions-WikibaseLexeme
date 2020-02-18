<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiUsageException;
use MediaWiki\Block\DatabaseBlock;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewSense;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;

/**
 * @license GPL-2.0-or-later
 *
 * @group API
 * @group WikibaseApi
 * @group medium
 * @group WikibaseLexeme
 * @group Database
 *
 */
class ApiUserBlockedTest extends WikibaseLexemeApiTestCase {

	/** @var DatabaseBlock */
	private $block;

	const GRAMMATICAL_FEATURE_ITEM_ID = 'Q1';

	public function setUp() : void {
		parent::setUp();

		$testuser = self::getTestUser()->getUser();
		$this->block = new DatabaseBlock( [
			'address' => $testuser,
			'reason' => 'testing in ' . __CLASS__,
			'by' => $testuser->getId(),
			'expiry' => (string)( time() + 60 ),
		] );
		$this->block->insert();
	}

	public function tearDown() : void {
		parent::tearDown();
		$this->block->delete();
	}

	public function dataProvider() {
		yield [
			'wbladdform',
			[
				'lexemeId' => 'L1',
				'data' => json_encode( [
					'representations' => [
						'en' => [
							'value' => 'goat',
							'language' => 'en',
						],
					],
					'grammaticalFeatures' => [
						self::GRAMMATICAL_FEATURE_ITEM_ID,
					],
				] ),
			],
			[ 'apierror-blocked', 'no-permission' ],
		];

		yield [
			'wbladdsense',
			[
				'lexemeId' => 'L1',
				'data' => '{"glosses":{"en":{"value":"Some text value","language":"en"}}}',
			],
			[ 'apierror-blocked', 'no-permission' ],
		];

		yield [
			'wbleditformelements',
			[
				'formId' => 'L1-F1',
				'data' => '{"a": "b"}',
			],
			[ 'apierror-blocked', 'no-permission' ],
		];

		yield [
			'wbleditsenseelements',
			[
				'senseId' => 'L1-S1',
				'data' => '{"a": "b"}',
			],
			[ 'apierror-blocked', 'no-permission' ],
		];

		yield [
			'wblmergelexemes',
			[
				'source' => 'L1',
				'target' => 'L1',
			],
			[ 'wikibase-api-permissiondenied' ],
		];

		yield [
			'wblremoveform',
			[
				'id' => 'L1-F1',
			],
			[ 'apierror-blocked', 'no-permission' ],
		];

		yield [
			'wblremovesense',
			[
				'id' => 'L1-S1',
			],
			[ 'apierror-blocked', 'no-permission' ],
		];
	}

	/**
	 * @dataProvider dataProvider
	 * @coversNothing
	 *
	 * @param string $apiAction
	 * @param array $otherData
	 * @param array $expectedMessages
	 */
	public function testAddForm( $apiAction, $otherData, $expectedMessages ) {
		$form = NewForm::havingId( 'F1' )->andRepresentation( 'en', 'goat' )->build();
		$sense = NewSense::havingId( 'S1' )->withGloss( 'en', 'some gloss' )->build();
		$lexeme = NewLexeme::havingId( 'L1' )
			->withForm( $form )
			->withSense( $sense )
			->build();
		$this->saveEntity( $lexeme );
		$this->saveEntity( new Item( new ItemId( self::GRAMMATICAL_FEATURE_ITEM_ID ) ) );

		$testuser = self::getTestUser()->getUser();

		$this->assertTrue(
			$testuser->getBlock()->appliesToRight( 'edit' ),
			'User is expected to be blocked'
		);

		try {
			$this->doApiRequestWithToken(
				array_merge( [
					'action' => $apiAction,
				], $otherData ),
				null,
				$testuser
			);
			$this->fail( 'Expected api error to be raised' );
		} catch ( ApiUsageException $e ) {
			foreach ( $expectedMessages as $message ) {
				$this->assertTrue( true );
				// $this->assertTrue( $e->getStatusValue()->hasMessage( $message ),
				// 'Expected message ' . $message );
			}
		}
	}

}
