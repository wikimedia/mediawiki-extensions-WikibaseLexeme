<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiUsageException;
use DataValues\StringValue;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\DataModel\NewLexeme;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;

/**
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 */
class SetClaimTest extends WikibaseLexemeApiTestCase {

	public function testGivenFormId_setClaimAddsStatementOnTheForm() {
		$propertyId = new PropertyId( 'P1' );
		$this->saveTestProperty( $propertyId );

		$lexemeId = new LexemeId( 'L1' );
		$formId = new FormId( 'L1-F1' );

		$form = NewForm::havingId( $formId )->build();
		$lexeme = NewLexeme::havingId( $lexemeId )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$guid = $formId->getSerialization() . '$00000000-0000-0000-0000-000000000000';
		$value = 'test';

		$params = [
			'action' => 'wbsetclaim',
			'claim' => json_encode( $this->getStatementData( $guid, $propertyId, $value ) ),
		];

		$this->doApiRequestWithToken( $params );

		$form = $this->loadForm( $lexemeId, $formId );

		$statements = $form->getStatements()->getByPropertyId( $propertyId )->toArray();
		$statement = $statements[0];

		$this->assertSame( $value, $statement->getMainSnak()->getDataValue()->getValue() );
		$this->assertSame( $guid, $statement->getGuid() );
	}

	public function testGivenFormIdAndGuidOfExistingStatement_setClaimEditsTheStatement() {
		$propertyId = new PropertyId( 'P1' );
		$this->saveTestProperty( $propertyId );

		$lexemeId = new LexemeId( 'L1' );
		$formId = new FormId( 'L1-F1' );

		$guid = $formId->getSerialization() . '$00000000-0000-0000-0000-000000000000';

		$statement = $this->getStatement( $guid, $propertyId, 'test' );

		$form = NewForm::havingId( $formId )->andStatement( $statement )->build();
		$lexeme = NewLexeme::havingId( $lexemeId )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbsetclaim',
			'claim' => json_encode( $this->getStatementData( $guid, $propertyId, 'goat' ) ),
		];

		$this->doApiRequestWithToken( $params );

		$form = $this->loadForm( $lexemeId, $formId );

		$statements = $form->getStatements()->getByPropertyId( $propertyId )->toArray();
		$statement = $statements[0];

		$this->assertSame( 'goat', $statement->getMainSnak()->getDataValue()->getValue() );
		$this->assertSame( $guid, $statement->getGuid() );
	}

	public function testGivenFormIdAndIndex_setClaimReordersStatementsAccordingly() {
		$propertyId = new PropertyId( 'P1' );
		$this->saveTestProperty( $propertyId );

		$lexemeId = new LexemeId( 'L1' );
		$formId = new FormId( 'L1-F1' );

		$guidCat = $formId->getSerialization() . '$00000000-0000-0000-0000-000000000000';
		$guidGoat = $formId->getSerialization() . '$66666666-6666-6666-6666-666666666666';

		$statementCat = $this->getStatement( $guidCat, $propertyId, 'cat' );
		$statementGoat = $this->getStatement( $guidCat, $propertyId, 'goat' );

		$form = NewForm::havingId( $formId )
			->andStatement( $statementCat )
			->andStatement( $statementGoat )
			->build();
		$lexeme = NewLexeme::havingId( $lexemeId )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$params = [
			'action' => 'wbsetclaim',
			'claim' => json_encode( $this->getStatementData( $guidGoat, $propertyId, 'goat' ) ),
			'index' => 0,
		];

		$this->doApiRequestWithToken( $params );

		$form = $this->loadForm( $lexemeId, $formId );

		$statements = $form->getStatements()->getByPropertyId( $propertyId )->toArray();
		$statement = $statements[0];

		$this->assertSame( 'goat', $statement->getMainSnak()->getDataValue()->getValue() );
		$this->assertSame( $guidGoat, $statement->getGuid() );
	}

	public function testGivenFormId_setClaimResponseSetsSuccess() {
		$propertyId = new PropertyId( 'P1' );
		$this->saveTestProperty( $propertyId );

		$lexemeId = new LexemeId( 'L1' );
		$formId = new FormId( 'L1-F1' );

		$form = NewForm::havingId( $formId )->build();
		$lexeme = NewLexeme::havingId( $lexemeId )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$guid = $formId->getSerialization() . '$00000000-0000-0000-0000-000000000000';
		$value = 'test';

		$params = [
			'action' => 'wbsetclaim',
			'claim' => json_encode( $this->getStatementData( $guid, $propertyId, $value ) ),
		];

		list( $result, ) = $this->doApiRequestWithToken( $params );

		$this->assertSame( 1, $result['success'] );
	}

	// TODO: test statement data in response?

	public function testGivenFormId_setClaimSetsEditSummaryAccordingly() {
		$propertyId = new PropertyId( 'P1' );
		$this->saveTestProperty( $propertyId );

		$lexemeId = new LexemeId( 'L1' );
		$formId = new FormId( 'L1-F1' );

		$form = NewForm::havingId( $formId )->build();
		$lexeme = NewLexeme::havingId( $lexemeId )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$guid = $formId->getSerialization() . '$00000000-0000-0000-0000-000000000000';
		$value = 'test';

		$params = [
			'action' => 'wbsetclaim',
			'claim' => json_encode( $this->getStatementData( $guid, $propertyId, $value ) ),
		];

		$this->doApiRequestWithToken( $params );

		$revision = $this->loadPageRevision( $lexemeId );

		$this->assertSame(
			'/* wbsetclaim-create:2||1 */ [[Property:P1]]: test',
			$revision->getComment()->text
		);
	}

	public function testGivenFormIdAndCustomSummary_setClaimSetsEditSummaryAccordingly() {
		$propertyId = new PropertyId( 'P1' );
		$this->saveTestProperty( $propertyId );

		$lexemeId = new LexemeId( 'L1' );
		$formId = new FormId( 'L1-F1' );

		$form = NewForm::havingId( $formId )->build();
		$lexeme = NewLexeme::havingId( $lexemeId )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$guid = $formId->getSerialization() . '$00000000-0000-0000-0000-000000000000';
		$value = 'test';

		$params = [
			'action' => 'wbsetclaim',
			'claim' => json_encode( $this->getStatementData( $guid, $propertyId, $value ) ),
			'summary' => 'The best edit ever',
		];

		$this->doApiRequestWithToken( $params );

		$revision = $this->loadPageRevision( $lexemeId );

		$this->assertSame(
			'/* wbsetclaim-create:2||1 */ [[Property:P1]]: test, The best edit ever',
			$revision->getComment()->text
		);
	}

	public function testGivenFormIdWithoutEditPermission_violationIsReported() {
		$propertyId = new PropertyId( 'P1' );
		$this->saveTestProperty( $propertyId );

		$lexemeId = new LexemeId( 'L1' );
		$formId = new FormId( 'L1-F1' );

		$form = NewForm::havingId( $formId )->build();
		$lexeme = NewLexeme::havingId( $lexemeId )->withForm( $form )->build();

		$this->saveLexeme( $lexeme );

		$this->mergeMwGlobalArrayValue( 'wgGroupPermissions', [
			'*' => [
				'read' => true,
				'edit' => false
			]
		] );

		try {
			$this->doApiRequestWithToken( [
				'action' => 'wbsetclaim',
				'claim' => json_encode( $this->getStatementData(
					$formId->getSerialization() . '$00000000-0000-0000-0000-000000000000',
					$propertyId,
					'test'
				) ),
			], null, self::createTestUser()->getUser() );
			$this->fail( 'Expected apierror-writeapidenied to be raised' );
		} catch ( ApiUsageException $exception ) {
			$this->assertSame( 'apierror-writeapidenied', $exception->getMessageObject()->getKey() );
		}
	}

	private function saveTestProperty( PropertyId $propertyId ) {
		$property = new Property( $propertyId, null, 'string' );
		$this->entityStore->saveEntity( $property, self::class, $this->getTestUser()->getUser() );
	}

	private function saveLexeme( Lexeme $lexeme ) {
		$this->entityStore->saveEntity( $lexeme, self::class, $this->getTestUser()->getUser() );
	}

	/**
	 * TODO: only pass FormId once FormId can tell what is the lexeme ID
	 * @param LexemeId $lexemeId
	 * @param FormId $formId
	 * @return Lexeme
	 */
	private function loadForm( LexemeId $lexemeId, FormId $formId ) {
		$lookup = $this->getLookup();

		/** @var Lexeme $lexeme */
		$lexeme = $lookup->getEntity( $lexemeId );

		return $lexeme->getForm( $formId );
	}

	/**
	 * @return EntityLookup
	 */
	private function getLookup() {
		return $this->wikibaseRepo->getEntityLookup();
	}

	private function getStatementData( $guid, PropertyId $propertyId, $value ) {
		return [
			'id' => $guid,
			'type' => 'claim',
			'mainsnak' => [
				'snaktype' => 'value',
				'property' => $propertyId->getSerialization(),
				'datavalue' => [
					'value' => $value,
					'type' => 'string'
				]
			]
		];
	}

	private function getStatement( $guid, PropertyId $propertyId, $value ) {
		$snak = new PropertyValueSnak( $propertyId, new StringValue( $value ) );
		return new Statement( $snak, null, null, $guid );
	}

	private function loadPageRevision( $lexemeId ) {
		$lookup = $this->wikibaseRepo->getEntityRevisionLookup();
		$revisionId = $lookup->getEntityRevision( $lexemeId )->getRevisionId();

		return MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById( $revisionId );
	}

}
