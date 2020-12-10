<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\DataAccess\ChangeOp\Validation\LemmaTermValidator;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOpBase;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpLemmaEdit extends ChangeOpBase {

	private const SUMMARY_ACTION_ADD = 'add';
	private const SUMMARY_ACTION_UPDATE = 'set';

	/**
	 * @var string
	 */
	private $language;

	/**
	 * @var string
	 */
	private $lemma;

	/**
	 * @var LemmaTermValidator
	 */
	private $lemmaTermValidator;

	/**
	 * @param string $language
	 * @param string $lemma
	 * @param LemmaTermValidator $lemmaTermValidator
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $language, $lemma, LemmaTermValidator $lemmaTermValidator ) {
		Assert::parameterType( 'string', $language, '$language' );
		Assert::parameterType( 'string', $lemma, '$lemma' );

		$this->language = $language;
		$this->lemma = $lemma;
		$this->lemmaTermValidator = $lemmaTermValidator;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 * @throws InvalidArgumentException
	 */
	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		return $this->lemmaTermValidator->validate( $this->lemma );
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

		$this->updateSummary(
			$summary,
			$lemmas->hasTermForLanguage( $this->language ) ?
				self::SUMMARY_ACTION_UPDATE :
				self::SUMMARY_ACTION_ADD,
			$this->language,
			$this->lemma
		);
		$lemmas->setTextForLanguage( $this->language, $this->lemma );

		return new DummyChangeOpResult();
	}

}
