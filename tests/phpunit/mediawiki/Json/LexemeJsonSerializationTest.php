<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Tests\MediaWiki\Rdf;

use DataValues\Serializers\DataValueSerializer;
use MediaWiki\Site\HashSiteStore;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use Serializers\DispatchingSerializer;
use Wikibase\DataAccess\Tests\InMemoryPrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Serialization\ExternalLexemeSerializer;
use Wikibase\Lexeme\Serialization\StorageLexemeSerializer;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\LinkedData\EntityDataSerializationService;
use Wikibase\Repo\Rdf\RdfBuilderFactory;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\LinkedData\EntityDataSerializationService
 *
 * @group Database
 * @group WikibaseLexeme
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LexemeJsonSerializationTest extends MediaWikiIntegrationTestCase {

	private const ENTITY_FIXTURES_FOLDER = __DIR__ . '/../../data/rdf/entities/';
	private const JSON_FORMAT_FIXTURES_FOLDER = __DIR__ . '/../../data/jsonEntities/';

	/**
	 * Returns a MockRepository containing the L2 lexeme
	 */
	private static function getMockRepository(): MockRepository {
		$mockRepo = new MockRepository();

		$l2 = new Lexeme( new LexemeId( 'L2' ) );
		$mockRepo->putEntity( $l2 );

		return $mockRepo;
	}

	private function newService(): EntityDataSerializationService {
		$dataTypeLookup = $this->createMock( PropertyDataTypeLookup::class );
		$dataTypeLookup->method( 'getDataTypeIdForProperty' )
			->willReturn( 'wikibase-item' );

		$entityTitleStoreLookup = $this->createMock( EntityTitleStoreLookup::class );
		$entityTitleStoreLookup->method( 'getTitleForId' )
			->willReturnCallback( static function ( EntityId $id ) {
				return Title::newFromTextThrow( $id->getEntityType() . ':' . $id->getSerialization() );
			} );

		$entityContentFactory = $this->createMock( EntityContentFactory::class );
		// should also be unused since we configure no page props
		$entityContentFactory->expects( $this->never() )
			->method( 'newFromEntity' );

		$rdfBuilderFactory = $this->createMock( RdfBuilderFactory::class );

		$serializerFactory = new SerializerFactory(
			new DataValueSerializer(), SerializerFactory::OPTION_SERIALIZE_USE_OBJECTS_FOR_EMPTY_MAPS
		);
		$serializer = new DispatchingSerializer( [
			$serializerFactory->newEntitySerializer(),
			new ExternalLexemeSerializer(
				new StorageLexemeSerializer(
					$serializerFactory->newTermListSerializer(),
					$serializerFactory->newStatementListSerializer()
				)
			),
		] );
		return new EntityDataSerializationService(
			$entityTitleStoreLookup,
			$dataTypeLookup,
			new EntityDataFormatProvider(),
			$serializerFactory,
			$serializer,
			new HashSiteStore(),
			$rdfBuilderFactory,
			WikibaseRepo::getEntityIdParser()
		);
	}

	public function testJsonSerialization(): void {
		$fixture = 'L2';
		$service = $this->newService();
		$mockRepo = $this->getMockRepository();
		$this->setService( 'WikibaseRepo.PropertyDataTypeLookup', $this->createMock( PropertyDataTypeLookup::class ) );
		$inMemoryTermLookup = new InMemoryPrefetchingTermLookup();
		$l2 = new LexemeId( 'L2' );
		$inMemoryTermLookup->setData( [ $mockRepo->getEntity( $l2 ) ] );
		$this->setService( 'WikibaseRepo.PrefetchingTermLookup', $inMemoryTermLookup );
		$entityJson = file_get_contents( self::ENTITY_FIXTURES_FOLDER . $fixture . '.json' );
		$entityDocument = WikibaseRepo::getEntityContentDataCodec()->decodeEntity(
			$entityJson,
			CONTENT_FORMAT_JSON
		);
		$entityId = new LexemeId( $fixture );
		$mockRepo->putEntity( $entityDocument, 0, 1779955112 );
		$entityRev = $mockRepo->getEntityRevision( $entityId );
		[ $data, $mimeType ] = $service->getSerializedData(
			'json',
			$entityRev,
			null,
			[],
			'application/json'
		);
		$this->assertEquals( 'application/json', $mimeType );
		$expectedJson = file_get_contents( self::JSON_FORMAT_FIXTURES_FOLDER . $fixture . '-formatted.json' );
		$this->assertEquals( $expectedJson, $data );
	}

}
