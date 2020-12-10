<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\Model\Form;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpRemoveFormRepresentation implements ChangeOp {

	private const SUMMARY_ACTION_REMOVE = 'remove-form-representations';

	/**
	 * TODO LanguageCode model?
	 *
	 * @var string
	 */
	private $language;

	/**
	 * @param string $language The language to remove
	 */
	public function __construct( $language ) {
		$this->language = $language;
	}

	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Form::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Form::class, $entity, '$entity' );
		'@phan-var Form $entity';

		/** @var Form $entity */

		$representations = $entity->getRepresentations();

		if ( !$representations->hasTermForLanguage( $this->language ) ) {
			return new DummyChangeOpResult();
		}

		$this->updateSummary( $entity, $summary );

		$representations->removeByLanguage( $this->language );

		return new DummyChangeOpResult();
	}

	private function updateSummary( Form $form, Summary $summary = null ) {
		if ( $summary === null ) {
			return;
		}

		$summary->setAction( self::SUMMARY_ACTION_REMOVE );

		$summary->setLanguage( $this->language );
		$summary->addAutoCommentArgs( [
			$form->getId()->getSerialization() // TODO: use FormId not string?
		] );
		$summary->addAutoSummaryArgs( [
			$this->language => $form->getRepresentations()->getByLanguage( $this->language )->getText()
		] );
	}

	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

}
