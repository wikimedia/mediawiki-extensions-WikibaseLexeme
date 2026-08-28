<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess\Store;

use Wikibase\DataModel\Statement\StatementList as StatementListWriteModel;
use Wikibase\Lexeme\Domain\Model\FormSet;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Form;
use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;
use Wikibase\Lexeme\Domain\Model\ReadModel\Glosses;
use Wikibase\Lexeme\Domain\Model\ReadModel\GrammaticalFeatures;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Model\ReadModel\Representations;
use Wikibase\Lexeme\Domain\Model\ReadModel\Sense;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Lexeme\Domain\Model\SenseSet;
use Wikibase\Lexeme\Domain\Services\LexemeRetriever;
use Wikibase\Lexeme\Domain\Services\LexemeWriteModelRetriever;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Statements\Domain\Services\StatementReadModelConverter;

/**
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupLexemeRetriever implements LexemeRetriever, LexemeWriteModelRetriever {

	public function __construct(
		private EntityRevisionLookup $entityRevisionLookup,
		private StatementReadModelConverter $statementReadModelConverter
	) {
	}

	public function getLexeme( LexemeId $lexemeId ): ?Lexeme {
		$lexeme = $this->getLexemeWriteModel( $lexemeId );

		if ( $lexeme === null ) {
			return null;
		}

		return new Lexeme(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$lexeme->getId(),
			Lemmas::fromTermList( $lexeme->getLemmas() ),
			$lexeme->getLexicalCategory(),
			$lexeme->getLanguage(),
			$this->convertStatements( $lexeme->getStatements() ),
			$this->buildForms( $lexeme->getForms() ),
			$this->buildSenses( $lexeme->getSenses() )
		);
	}

	private function buildForms( FormSet $forms ): Forms {
		$readModelForms = [];
		foreach ( $forms->toArray() as $form ) {
			$readModelForms[] = new Form(
				$form->getId(),
				Representations::fromTermList( $form->getRepresentations() ),
				new GrammaticalFeatures( ...$form->getGrammaticalFeatures() ),
				$this->convertStatements( $form->getStatements() )
			);
		}
		return new Forms( ...$readModelForms );
	}

	private function buildSenses( SenseSet $senses ): Senses {
		$readModelSenses = [];
		foreach ( $senses->toArray() as $sense ) {
			$readModelSenses[] = new Sense(
				$sense->getId(),
				Glosses::fromTermList( $sense->getGlosses() ),
				$this->convertStatements( $sense->getStatements() )
			);
		}
		return new Senses( ...$readModelSenses );
	}

	private function convertStatements( StatementListWriteModel $statements ): StatementList {
		return new StatementList( ...array_map(
			$this->statementReadModelConverter->convert( ... ),
			iterator_to_array( $statements )
		) );
	}

	public function getLexemeWriteModel( LexemeId $lexemeId ): ?LexemeWriteModel {
		try {
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $lexemeId );
		} catch ( RevisionedUnresolvedRedirectException ) {
			return null;
		}

		if ( !$entityRevision ) {
			return null;
		}

		/** @var LexemeWriteModel $lexeme */
		$lexeme = $entityRevision->getEntity();
		'@phan-var LexemeWriteModel $lexeme';

		return $lexeme;
	}

}
