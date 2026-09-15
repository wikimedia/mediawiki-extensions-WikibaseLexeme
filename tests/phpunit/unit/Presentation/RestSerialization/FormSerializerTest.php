<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\Unit\Presentation\RestSerialization;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Form;
use Wikibase\Lexeme\Domain\Model\ReadModel\GrammaticalFeatures;
use Wikibase\Lexeme\Domain\Model\ReadModel\Representation;
use Wikibase\Lexeme\Domain\Model\ReadModel\Representations;
use Wikibase\Lexeme\Presentation\RestSerialization\FormSerializer;
use Wikibase\Lexeme\Presentation\RestSerialization\GrammaticalFeaturesSerializer;
use Wikibase\Lexeme\Presentation\RestSerialization\RepresentationsSerializer;
use Wikibase\Repo\Domains\Statements\Application\Serialization\StatementListSerializer;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Lexeme\Presentation\RestSerialization\FormSerializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FormSerializerTest extends TestCase {

	public function testSerialize(): void {
		$representations = [ 'en-gb' => 'colourise', 'en-us' => 'colorize' ];
		$representationsSerializer = $this->createStub( RepresentationsSerializer::class );
		$representationsSerializer->method( 'serialize' )->willReturn(
			new ArrayObject( $representations )
		);

		$grammaticalFeatures = [ 'Q1', 'Q2' ];
		$grammaticalFeaturesSerializer = $this->createStub( GrammaticalFeaturesSerializer::class );
		$grammaticalFeaturesSerializer->method( 'serialize' )->willReturn( $grammaticalFeatures );

		$statements = [ 'P1' => [ 'a serialized statement' ] ];
		$statementListSerializer = $this->createStub( StatementListSerializer::class );
		$statementListSerializer->method( 'serialize' )->willReturn(
			new ArrayObject( $statements )
		);

		$form = new Form(
			new FormId( 'L1-F1' ),
			new Representations(
				new Representation( 'en-gb', 'colourise' ),
				new Representation( 'en-us', 'colorize' )
			),
			new GrammaticalFeatures( new ItemId( 'Q1' ), new ItemId( 'Q2' ) ),
			new StatementList()
		);

		$this->assertEquals(
			[
				'id' => 'L1-F1',
				'representations' => new ArrayObject( $representations ),
				'grammatical_features' => $grammaticalFeatures,
				'statements' => new ArrayObject( $statements ),
			],
			( new FormSerializer(
				$representationsSerializer,
				$grammaticalFeaturesSerializer,
				$statementListSerializer
			) )->serialize( $form )
		);
	}
}
