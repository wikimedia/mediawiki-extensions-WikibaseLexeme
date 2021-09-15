<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Api;

use ApiUsageException;
use DataValues\StringValue;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Tests\MediaWiki\WikibaseLexemeApiTestCase;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewForm;
use Wikibase\Lexeme\Tests\Unit\DataModel\NewLexeme;
use Wikibase\Repo\WikibaseRepo;

/**
 * @coversNothing
 *
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 */
class SetClaimTest extends WikibaseLexemeApiTestCase {

	public function testGivenFormId_setClaimAddsStatementOnTheForm() {
		$propertyId = new NumericPropertyId( 'P1' );
		$this->saveTestProperty( $propertyId );

		$lexemeId = new LexemeId( 'L1' );
		$formId = new FormId( 'L1-F1' );

		$form = NewForm::havingId( $formId )->build();
		$lexeme = NewLexeme::havingId( $lexemeId )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

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
		$propertyId = new NumericPropertyId( 'P1' );
		$this->saveTestProperty( $propertyId );

		$lexemeId = new LexemeId( 'L1' );
		$formId = new FormId( 'L1-F1' );

		$guid = $formId->getSerialization() . '$00000000-0000-0000-0000-000000000000';

		$statement = $this->getStatement( $guid, $propertyId, 'test' );

		$form = NewForm::havingId( $formId )->andStatement( $statement )->build();
		$lexeme = NewLexeme::havingId( $lexemeId )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

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
		$propertyId = new NumericPropertyId( 'P1' );
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

		$this->saveEntity( $lexeme );

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
		$propertyId = new NumericPropertyId( 'P1' );
		$this->saveTestProperty( $propertyId );

		$lexemeId = new LexemeId( 'L1' );
		$formId = new FormId( 'L1-F1' );

		$form = NewForm::havingId( $formId )->build();
		$lexeme = NewLexeme::havingId( $lexemeId )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

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
		$propertyId = new NumericPropertyId( 'P1' );
		$this->saveTestProperty( $propertyId );

		$lexemeId = new LexemeId( 'L1' );
		$formId = new FormId( 'L1-F1' );

		$form = NewForm::havingId( $formId )->build();
		$lexeme = NewLexeme::havingId( $lexemeId )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

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
		$propertyId = new NumericPropertyId( 'P1' );
		$this->saveTestProperty( $propertyId );

		$lexemeId = new LexemeId( 'L1' );
		$formId = new FormId( 'L1-F1' );

		$form = NewForm::havingId( $formId )->build();
		$lexeme = NewLexeme::havingId( $lexemeId )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

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
		$propertyId = new NumericPropertyId( 'P1' );
		$this->saveTestProperty( $propertyId );

		$lexemeId = new LexemeId( 'L1' );
		$formId = new FormId( 'L1-F1' );

		$form = NewForm::havingId( $formId )->build();
		$lexeme = NewLexeme::havingId( $lexemeId )->withForm( $form )->build();

		$this->saveEntity( $lexeme );

		$this->mergeMwGlobalArrayValue( 'wgGroupPermissions', [
			'*' => [
				'read' => true,
				'edit' => false
			]
		] );
		$this->resetServices();

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

	public function testGivenClaimWithFormValueOnProperty_setsClaim() {
		$propertyId = 'P321';
		$formId = 'L123-F1';
		$this->saveEntity(
			NewLexeme::havingId( 'L123' )
				->withForm( NewForm::havingId( new FormId( $formId ) )->andLexeme( 'L123' ) )
				->build()
		);
		$this->entityStore->saveEntity(
			new Property( new NumericPropertyId( $propertyId ), null, 'wikibase-form' ),
			self::class,
			$this->getTestUser()->getUser()
		);

		list( $result ) = $this->doApiRequestWithToken( [
			'action' => 'wbsetclaim',
			'claim' => json_encode( $this->getStatementData(
				$propertyId . '$00000000-0000-0000-0000-000000000000',
				new NumericPropertyId( $propertyId ),
				[ 'id' => $formId ],
				'wikibase-entityid'
			) ),
		] );

		$this->assertSame( 1, $result['success'] );
	}

	private function saveTestProperty( NumericPropertyId $propertyId ) {
		$this->saveEntity( new Property( $propertyId, null, 'string' ) );
	}

	/**
	 * TODO: only pass FormId once FormId can tell what is the lexeme ID
	 * @param LexemeId $lexemeId
	 * @param FormId $formId
	 * @return Lexeme
	 */
	private function loadForm( LexemeId $lexemeId, FormId $formId ) {
		$lookup = WikibaseRepo::getEntityLookup();

		/** @var Lexeme $lexeme */
		$lexeme = $lookup->getEntity( $lexemeId );

		return $lexeme->getForm( $formId );
	}

	private function getStatementData( $guid, NumericPropertyId $propertyId, $value, $type = 'string' ) {
		return [
			'id' => $guid,
			'type' => 'claim',
			'mainsnak' => [
				'snaktype' => 'value',
				'property' => $propertyId->getSerialization(),
				'datavalue' => [
					'value' => $value,
					'type' => $type
				]
			]
		];
	}

	private function getStatement( $guid, NumericPropertyId $propertyId, $value ) {
		$snak = new PropertyValueSnak( $propertyId, new StringValue( $value ) );
		return new Statement( $snak, null, null, $guid );
	}

	private function loadPageRevision( $lexemeId ) {
		$lookup = WikibaseRepo::getEntityRevisionLookup();
		$revisionId = $lookup->getEntityRevision( $lexemeId )->getRevisionId();

		return MediaWikiServices::getInstance()->getRevisionStore()->getRevisionById( $revisionId );
	}

}
