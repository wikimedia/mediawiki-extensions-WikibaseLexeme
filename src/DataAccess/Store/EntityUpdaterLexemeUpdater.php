<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\DataAccess\CrudEditSummaryAdapter;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\Exceptions\EditPrevented;
use Wikibase\Lexeme\Domain\Model\Exceptions\RateLimitReached;
use Wikibase\Lexeme\Domain\Model\Exceptions\ResourceTooLargeException;
use Wikibase\Lexeme\Domain\Model\Exceptions\TempAccountCreationLimitReached;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\ReadModel\LexemeRevision;
use Wikibase\Lexeme\Domain\Services\LexemeCreator;
use Wikibase\Lexeme\Domain\Services\LexemeUpdater;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata as CrudEditMetadata;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\EditPrevented as CrudEditPrevented;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\RateLimitReached as CrudRateLimitReached;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\ResourceTooLargeException as CrudResourceTooLargeException;
use Wikibase\Repo\Domains\Crud\Domain\Services\Exceptions\TempAccountCreationLimitReached as CrudTempAccountException;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdater;

/**
 * @license GPL-2.0-or-later
 */
class EntityUpdaterLexemeUpdater implements LexemeCreator, LexemeUpdater {

	public function __construct(
		private EntityUpdater $entityUpdater,
		private LexemeReadModelConverter $lexemeReadModelConverter,
		private GuidGenerator $guidGenerator,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function create( LexemeWriteModel $lexeme, EditMetadata $editMetadata ): LexemeRevision {
		if ( $lexeme->getId() ) {
			throw new InvalidArgumentException( 'New Lexeme must not have an ID' );
		}

		return $this->storeLexeme( fn () => $this->entityUpdater->create(
			$lexeme,
			$this->convertEditMetadata( $editMetadata ),
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function update( LexemeWriteModel $lexeme, EditMetadata $editMetadata ): LexemeRevision {
		if ( !$lexeme->getId() ) {
			throw new InvalidArgumentException( 'Cannot update a Lexeme without an ID' );
		}
		$this->generateFormStatementIds( $lexeme );

		return $this->storeLexeme( fn () => $this->entityUpdater->update(
			$lexeme,
			$this->convertEditMetadata( $editMetadata ),
		) );
	}

	/**
	 * EntityUpdater only generates IDs for the statements of the Lexeme itself,
	 * so the statements of its Forms are dealt with here.
	 */
	private function generateFormStatementIds( LexemeWriteModel $lexeme ): void {
		foreach ( $lexeme->getForms()->toArray() as $form ) {
			foreach ( $form->getStatements() as $statement ) {
				if ( $statement->getGuid() === null ) {
					$statement->setGuid( $this->guidGenerator->newGuid( $form->getId() ) );
				}
			}
		}
	}

	/**
	 * @throws TempAccountCreationLimitReached
	 * @throws ResourceTooLargeException
	 * @throws RateLimitReached
	 * @throws EditPrevented
	 */
	private function storeLexeme( callable $attemptStoringLexeme ): LexemeRevision {
		try {
			$entityRevision = $attemptStoringLexeme();
		} catch ( CrudTempAccountException ) {
			throw new TempAccountCreationLimitReached();
		} catch ( CrudResourceTooLargeException $e ) {
			throw new ResourceTooLargeException( $e->getResourceSizeLimit() );
		} catch ( CrudRateLimitReached ) {
			throw new RateLimitReached();
		} catch ( CrudEditPrevented $e ) {
			throw new EditPrevented( $e->getReason(), $e->getContext() );
		}

		return $this->convertToLexemeRevision( $entityRevision );
	}

	private function convertEditMetadata( EditMetadata $editMetadata ): CrudEditMetadata {
		return new CrudEditMetadata(
			$editMetadata->tags,
			$editMetadata->isBot,
			new CrudEditSummaryAdapter( $editMetadata->editSummary ),
		);
	}

	private function convertToLexemeRevision( EntityRevision $entityRevision ): LexemeRevision {
		/** @var LexemeWriteModel $lexeme */
		$lexeme = $entityRevision->getEntity();
		'@phan-var LexemeWriteModel $lexeme';

		return new LexemeRevision(
			$this->lexemeReadModelConverter->convert( $lexeme ),
			$entityRevision->getRevisionId(),
			$entityRevision->getTimestamp(),
		);
	}

}
