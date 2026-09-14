<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess\Store;

use Wikibase\DataModel\Statement\StatementList as StatementListWriteModel;
use Wikibase\Lexeme\Domain\Model\Form as FormWriteModel;
use Wikibase\Lexeme\Domain\Model\FormSet;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\ReadModel\Form;
use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;
use Wikibase\Lexeme\Domain\Model\ReadModel\Glosses;
use Wikibase\Lexeme\Domain\Model\ReadModel\GrammaticalFeatures;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Model\ReadModel\Representations;
use Wikibase\Lexeme\Domain\Model\ReadModel\Sense;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Lexeme\Domain\Model\Sense as SenseWriteModel;
use Wikibase\Lexeme\Domain\Model\SenseSet;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Statements\Domain\Services\StatementReadModelConverter;

/**
 * @license GPL-2.0-or-later
 */
class LexemeReadModelConverter {

	public function __construct(
		private StatementReadModelConverter $statementReadModelConverter,
	) {
	}

	public function convert( LexemeWriteModel $lexeme ): Lexeme {
		return new Lexeme(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$lexeme->getId(),
			Lemmas::fromTermList( $lexeme->getLemmas() ),
			$lexeme->getLexicalCategory(),
			$lexeme->getLanguage(),
			$this->convertStatements( $lexeme->getStatements() ),
			$this->convertForms( $lexeme->getForms() ),
			$this->convertSenses( $lexeme->getSenses() ),
		);
	}

	private function convertForms( FormSet $forms ): Forms {
		return new Forms( ...array_map(
			fn ( FormWriteModel $form ) => new Form(
				$form->getId(),
				Representations::fromTermList( $form->getRepresentations() ),
				new GrammaticalFeatures( ...$form->getGrammaticalFeatures() ),
				$this->convertStatements( $form->getStatements() ),
			),
			$forms->toArray()
		) );
	}

	private function convertSenses( SenseSet $senses ): Senses {
		return new Senses( ...array_map(
			fn ( SenseWriteModel $sense ) => new Sense(
				$sense->getId(),
				Glosses::fromTermList( $sense->getGlosses() ),
				$this->convertStatements( $sense->getStatements() ),
			),
			$senses->toArray()
		) );
	}

	private function convertStatements( StatementListWriteModel $statements ): StatementList {
		return new StatementList( ...array_map(
			$this->statementReadModelConverter->convert( ... ),
			iterator_to_array( $statements )
		) );
	}

}
