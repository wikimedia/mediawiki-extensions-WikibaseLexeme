<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Repo\ChangeOp\ChangeOpBase;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpLanguage extends ChangeOpBase {

	private $language;
	private $languageValidator;

	public function __construct( ItemId $language, ValueValidator $languageValidator ) {
		$this->language = $language;
		$this->languageValidator = $languageValidator;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 * @throws InvalidArgumentException
	 */
	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		return $this->languageValidator->validate( $this->language );
	}

	/**
	 * @param EntityDocument $entity
	 * @param Summary|null $summary
	 *
	 * @throws InvalidArgumentException
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		/** @var Lexeme $entity */
		$this->updateSummary( $summary, 'set', '', $this->language->getSerialization() );
		$entity->setLanguage( $this->language );
	}

}
