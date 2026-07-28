<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\Unit\Presentation\RestSerialization;

use ArrayObject;
use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Form;
use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;
use Wikibase\Lexeme\Domain\Model\ReadModel\GrammaticalFeatures;
use Wikibase\Lexeme\Domain\Model\ReadModel\Representation;
use Wikibase\Lexeme\Domain\Model\ReadModel\Representations;
use Wikibase\Lexeme\Presentation\RestSerialization\FormsSerializer;
use Wikibase\Lexeme\Presentation\RestSerialization\GrammaticalFeaturesSerializer;
use Wikibase\Lexeme\Presentation\RestSerialization\RepresentationsSerializer;
use Wikibase\Repo\Domains\Statements\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Lexeme\Presentation\RestSerialization\FormsSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FormsSerializerTest extends TestCase {
	private const SERIALIZED_REPRESENTATIONS = [ 'en-gb' => 'colourise', 'en-us' => 'colorize' ];
	private const SERIALIZED_GRAMMATICAL_FEATURES = [ 'Q1', 'Q2' ];
	private const SERIALIZED_STATEMENTS = [ 'P1' => [ 'a serialized statement' ] ];

	/**
	 * @dataProvider formsProvider
	 */
	public function testSerialize( Forms $forms, array $serialization ): void {
		$representationsSerializer = $this->createStub( RepresentationsSerializer::class );
		$representationsSerializer->method( 'serialize' )->willReturn(
			new ArrayObject( self::SERIALIZED_REPRESENTATIONS )
		);

		$grammaticalFeaturesSerializer = $this->createStub( GrammaticalFeaturesSerializer::class );
		$grammaticalFeaturesSerializer->method( 'serialize' )->willReturn( self::SERIALIZED_GRAMMATICAL_FEATURES );

		$statementListSerializer = $this->createStub( StatementListSerializer::class );
		$statementListSerializer->method( 'serialize' )->willReturn(
			new ArrayObject( self::SERIALIZED_STATEMENTS )
		);

		$this->assertEquals(
			$serialization,
			( new FormsSerializer(
				$representationsSerializer,
				$grammaticalFeaturesSerializer,
				$statementListSerializer
			) )
				->serialize( $forms )
		);
	}

	public static function formsProvider(): Generator {
		$statements = new ArrayObject( self::SERIALIZED_STATEMENTS );

		yield 'empty' => [
		new Forms(),
		[],
		];

		yield 'single form' => [
		new Forms(
		new Form(
			new FormId( 'L1-F1' ),
			new Representations(
					new Representation( 'en-gb', 'colourise' ),
					new Representation( 'en-us', 'colorize' )
				),
			new GrammaticalFeatures( new ItemId( 'Q1' ), new ItemId( 'Q2' ) ),
			new StatementList()
		)
		),
		[
		[
			'id' => 'L1-F1',
			'representations' => new ArrayObject( self::SERIALIZED_REPRESENTATIONS ),
			'grammatical_features' => self::SERIALIZED_GRAMMATICAL_FEATURES,
			'statements' => $statements,
		],
		],
		];

		yield 'multiple forms' => [
		new Forms(
		new Form(
			new FormId( 'L1-F1' ),
			new Representations(
					new Representation( 'en-gb', 'colourise' ),
					new Representation( 'en-us', 'colorize' )
				),
			new GrammaticalFeatures( new ItemId( 'Q1' ), new ItemId( 'Q2' ) ),
			new StatementList()
		),
		new Form(
			new FormId( 'L1-F2' ),
			new Representations(
					new Representation( 'en-gb', 'colourised' ),
					new Representation( 'en-us', 'colorized' )
				),
			new GrammaticalFeatures( new ItemId( 'Q3' ), new ItemId( 'Q4' ) ),
			new StatementList()
		)
		),
		[
		[
			'id' => 'L1-F1',
			'representations' => new ArrayObject( self::SERIALIZED_REPRESENTATIONS ),
			'grammatical_features' => self::SERIALIZED_GRAMMATICAL_FEATURES,
			'statements' => $statements,
		],
		[
			'id' => 'L1-F2',
			'representations' => new ArrayObject( self::SERIALIZED_REPRESENTATIONS ),
			'grammatical_features' => self::SERIALIZED_GRAMMATICAL_FEATURES,
			'statements' => $statements,
		],
		],
		];
	}
}
