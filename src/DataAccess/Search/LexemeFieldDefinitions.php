<?php

namespace Wikibase\Lexeme\DataAccess\Search;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Repo\Search\Fields\FieldDefinitions;
use Wikibase\Repo\Search\Fields\WikibaseIndexField;

/**
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LexemeFieldDefinitions implements FieldDefinitions {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var PropertyId|null
	 */
	private $lexemeLanguageCodePropertyId;
	/**
	 * @var FieldDefinitions
	 */
	private $statements;

	public function __construct( FieldDefinitions $statements,
								 EntityLookup $entityLookup,
								 PropertyId $lexemeLanguageCodePropertyId = null ) {
		$this->statements = $statements;
		$this->lexemeLanguageCodePropertyId = $lexemeLanguageCodePropertyId;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @return WikibaseIndexField[]
	 */
	public function getFields() {
		$fields = $this->statements->getFields();

		$fields[LemmaField::NAME] = new LemmaField();
		$fields[FormsField::NAME] = new FormsField();
		$fields[LexemeLanguageField::NAME] = new LexemeLanguageField( $this->entityLookup,
			$this->lexemeLanguageCodePropertyId );
		$fields[LexemeCategoryField::NAME] = new LexemeCategoryField();
		return $fields;
	}

}
