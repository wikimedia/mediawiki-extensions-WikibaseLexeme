<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess\Store;

use Wikibase\Lexeme\DataAccess\CrudEditSummaryAdapter;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\Exceptions\TempAccountCreationLimitReached;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Model\ReadModel\LexemeRevision;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Lexeme\Domain\Services\LexemeCreator;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata as CrudEditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\TempAccountCreationLimitReached as CrudTempAccountException;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Statements\Domain\Services\StatementReadModelConverter;

/**
 * @license GPL-2.0-or-later
 */
class EntityUpdaterLexemeCreator implements LexemeCreator {

	public function __construct(
		private EntityUpdater $entityUpdater,
		private StatementReadModelConverter $statementReadModelConverter,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function create( LexemeWriteModel $lexeme, EditMetadata $editMetadata ): LexemeRevision {
		try {
			$entityRevision = $this->entityUpdater->create(
				$lexeme,
				new CrudEditMetadata(
					$editMetadata->tags,
					$editMetadata->isBot,
					new CrudEditSummaryAdapter( $editMetadata->editSummaryAction, $editMetadata->comment ),
				),
			);
		} catch ( CrudTempAccountException ) {
			throw new TempAccountCreationLimitReached();
		}

		/** @var LexemeWriteModel $newLexeme */
		$newLexeme = $entityRevision->getEntity();
		'@phan-var LexemeWriteModel $newLexeme';

		return new LexemeRevision(
			new Lexeme(
				// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
				$newLexeme->getId(),
				Lemmas::fromTermList( $newLexeme->getLemmas() ),
				$newLexeme->getLexicalCategory(),
				$newLexeme->getLanguage(),
				new StatementList( ...array_map(
					$this->statementReadModelConverter->convert( ... ),
					iterator_to_array( $newLexeme->getStatements() )
				) ),
				new Forms(),
				new Senses(),
			),
			$entityRevision->getRevisionId(),
			$entityRevision->getTimestamp(),
		);
	}

}
