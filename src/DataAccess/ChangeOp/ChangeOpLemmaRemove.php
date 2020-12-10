<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOpBase;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpLemmaRemove extends ChangeOpBase {

	private const SUMMARY_ACTION_REMOVE = 'remove';

	/**
	 * @var string
	 */
	private $language;

	public function __construct( $language ) {
		Assert::parameterType( 'string', $language, '$language' );

		$this->language = $language;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 * @throws InvalidArgumentException
	 */
	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	/**
	 * @param EntityDocument $entity
	 * @param Summary|null $summary
	 *
	 * @throws InvalidArgumentException
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );
		'@phan-var Lexeme $entity';

		/** @var Lexeme $entity */
		$lemmas = $entity->getLemmas();

		if ( !$lemmas->hasTermForLanguage( $this->language ) ) {
			return new DummyChangeOpResult();
		}

		$this->updateSummary(
			$summary,
			self::SUMMARY_ACTION_REMOVE,
			$this->language,
			$lemmas->getByLanguage( $this->language )->getText()
		);
		$lemmas->removeByLanguage( $this->language );

		return new DummyChangeOpResult();
	}

}
