<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess\Store;

use InvalidArgumentException;
use Wikibase\Lexeme\DataAccess\CrudEditSummaryAdapter;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\Exceptions\RateLimitReached;
use Wikibase\Lexeme\Domain\Model\Exceptions\ResourceTooLargeException;
use Wikibase\Lexeme\Domain\Model\Exceptions\TempAccountCreationLimitReached;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Model\ReadModel\LexemeRevision;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Lexeme\Domain\Services\LexemeCreator;
use Wikibase\Lexeme\Domain\Services\LexemeUpdater;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata as CrudEditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\RateLimitReached as CrudRateLimitReached;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\ResourceTooLargeException as CrudResourceTooLargeException;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\TempAccountCreationLimitReached as CrudTempAccountException;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Statements\Domain\Services\StatementReadModelConverter;

/**
 * @license GPL-2.0-or-later
 */
class EntityUpdaterLexemeUpdater implements LexemeCreator, LexemeUpdater {

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
			$entityRevision = $this->entityUpdater->create( $lexeme, $this->convertEditMetadata( $editMetadata ) );
		} catch ( CrudTempAccountException ) {
			throw new TempAccountCreationLimitReached();
		} catch ( CrudResourceTooLargeException $e ) {
			throw new ResourceTooLargeException( $e->getResourceSizeLimit() );
		} catch ( CrudRateLimitReached ) {
			throw new RateLimitReached();
		}

		return $this->convertToLexemeRevision( $entityRevision );
	}

	/**
	 * @inheritDoc
	 */
	public function update( LexemeWriteModel $lexeme, EditMetadata $editMetadata ): LexemeRevision {
		if ( !$lexeme->getId() ) {
			throw new InvalidArgumentException( 'Cannot update a Lexeme without an ID' );
		}

		try {
			$entityRevision = $this->entityUpdater->update( $lexeme, $this->convertEditMetadata( $editMetadata ) );
		} catch ( CrudTempAccountException ) {
			throw new TempAccountCreationLimitReached();
		} catch ( CrudResourceTooLargeException $e ) {
			throw new ResourceTooLargeException( $e->getResourceSizeLimit() );
		} catch ( CrudRateLimitReached ) {
			throw new RateLimitReached();
		}

		return $this->convertToLexemeRevision( $entityRevision );
	}

	private function convertEditMetadata( EditMetadata $editMetadata ): CrudEditMetadata {
		return new CrudEditMetadata(
			$editMetadata->tags,
			$editMetadata->isBot,
			new CrudEditSummaryAdapter( $editMetadata->editSummaryAction, $editMetadata->comment ),
		);
	}

	private function convertToLexemeRevision( EntityRevision $entityRevision ): LexemeRevision {
		/** @var LexemeWriteModel $lexeme */
		$lexeme = $entityRevision->getEntity();
		'@phan-var LexemeWriteModel $lexeme';

		return new LexemeRevision(
			new Lexeme(
				// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
				$lexeme->getId(),
				Lemmas::fromTermList( $lexeme->getLemmas() ),
				$lexeme->getLexicalCategory(),
				$lexeme->getLanguage(),
				new StatementList( ...array_map(
					$this->statementReadModelConverter->convert( ... ),
					iterator_to_array( $lexeme->getStatements() )
				) ),
				new Forms(),
				new Senses(),
			),
			$entityRevision->getRevisionId(),
			$entityRevision->getTimestamp(),
		);
	}

}
