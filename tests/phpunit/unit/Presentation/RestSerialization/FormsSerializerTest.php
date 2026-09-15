<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\Unit\Presentation\RestSerialization;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Form;
use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;
use Wikibase\Lexeme\Domain\Model\ReadModel\GrammaticalFeatures;
use Wikibase\Lexeme\Domain\Model\ReadModel\Representation;
use Wikibase\Lexeme\Domain\Model\ReadModel\Representations;
use Wikibase\Lexeme\Presentation\RestSerialization\FormSerializer;
use Wikibase\Lexeme\Presentation\RestSerialization\FormsSerializer;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Lexeme\Presentation\RestSerialization\FormsSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FormsSerializerTest extends TestCase {

	/**
	 * @dataProvider formsProvider
	 */
	public function testSerialize( Forms $forms, array $serialization ): void {
		$formSerializer = $this->createStub( FormSerializer::class );
		$formSerializer->method( 'serialize' )->willReturnCallback(
			static fn ( Form $form ) => [ 'id' => $form->id->getSerialization() ]
		);

		$this->assertEquals( $serialization, ( new FormsSerializer( $formSerializer ) )->serialize( $forms ) );
	}

	public static function formsProvider(): Generator {
		yield 'empty' => [ new Forms(), [] ];

		yield 'single form' => [
			new Forms( self::newForm( 'L1-F1' ) ),
			[ [ 'id' => 'L1-F1' ] ],
		];

		yield 'multiple forms' => [
			new Forms( self::newForm( 'L1-F1' ), self::newForm( 'L1-F2' ) ),
			[ [ 'id' => 'L1-F1' ], [ 'id' => 'L1-F2' ] ],
		];
	}

	private static function newForm( string $formId ): Form {
		return new Form(
			new FormId( $formId ),
			new Representations( new Representation( 'en-gb', 'colourise' ) ),
			new GrammaticalFeatures( new ItemId( 'Q1' ) ),
			new StatementList()
		);
	}
}
