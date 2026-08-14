<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess\Store;

use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Model\ReadModel\LexemeRevision;
use Wikibase\Lexeme\Domain\Model\ReadModel\Senses;
use Wikibase\Lexeme\Domain\Services\LexemeCreator;
use Wikibase\Repo\Domains\Crud\Domain\Model\CreateItemEditSummary;
use Wikibase\Repo\Domains\Crud\Domain\Model\EditMetadata;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;

/**
 * @license GPL-2.0-or-later
 */
class EntityUpdaterLexemeCreator implements LexemeCreator {

	public function __construct( private EntityUpdater $entityUpdater ) {
	}

	public function create( LexemeWriteModel $lexeme ): LexemeRevision {
		$entityRevision = $this->entityUpdater->create(
			$lexeme,
			new EditMetadata(
				[],
				false,
				// CreateItemEditSummary is just here as a placeholder. This will be replaced in T434528.
				CreateItemEditSummary::newSummary( null )
			)
		);

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
				new StatementList(),
				new Forms(),
				new Senses(),
			),
			$entityRevision->getRevisionId(),
			$entityRevision->getTimestamp(),
		);
	}

}
