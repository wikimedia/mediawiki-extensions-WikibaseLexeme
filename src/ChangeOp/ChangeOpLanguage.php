<?php

namespace Wikibase\Lexeme\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\ChangeOp\ChangeOpBase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\Providers\LanguageProvider;
use Wikibase\Lexeme\Validators\LexemeValidatorFactory;
use Wikibase\Summary;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
 */
class ChangeOpLanguage extends ChangeOpBase {

	/**
	 * @var ItemId|null
	 */
	private $language;

	/**
	 * @var LexemeValidatorFactory
	 */
	private $lexemeValidatorFactory;

	/**
	 * @param ItemId|null $language
	 * @param LexemeValidatorFactory $lexemeValidatorFactory
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		ItemId $language = null,
		LexemeValidatorFactory $lexemeValidatorFactory
	) {
		if ( $language !== null ) {
			Assert::parameterType( ItemId::class, $language, '$language' );
		}

		$this->language = $language;
		$this->lexemeValidatorFactory = $lexemeValidatorFactory;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 *
	 * @throws InvalidArgumentException
	 */
	public function validate( EntityDocument $entity ) {
		Assert::parameterType( LanguageProvider::class, $entity, '$entity' );

		$languageValidator = $this->lexemeValidatorFactory->getLanguageValidator();

		return $languageValidator->validate( $this->language );
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
		// TODO: Add setLanguage to LanguageProvider interface
		$language = $entity->getLanguage();

		if ( $this->language === null ) {
			if ( $language ) {
				$this->updateSummary(
					$summary,
					'remove',
					'',
					$language->getSerialization()
				);
				$entity->setLanguage( null );
			}

			return;
		}

		$this->updateSummary( $summary, 'set', '', $this->language->getSerialization() );
		$entity->setLanguage( $this->language );
	}

}
