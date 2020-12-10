<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Lexeme\Domain\Model\Sense;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpRemoveSenseGloss implements ChangeOp {

	private const SUMMARY_ACTION_REMOVE = 'remove-sense-glosses';

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
		Assert::parameterType( Sense::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Sense::class, $entity, '$entity' );
		'@phan-var Sense $entity';

		/** @var Sense $entity */

		$glosses = $entity->getGlosses();

		if ( !$glosses->hasTermForLanguage( $this->language ) ) {
			return new DummyChangeOpResult();
		}

		$this->updateSummary( $entity, $summary );

		$glosses->removeByLanguage( $this->language );

		return new DummyChangeOpResult();
	}

	private function updateSummary( Sense $sense, Summary $summary = null ) {
		if ( $summary === null ) {
			return;
		}

		$summary->setAction( self::SUMMARY_ACTION_REMOVE );

		$summary->setLanguage( $this->language );
		$summary->addAutoCommentArgs( [
			$sense->getId()->getSerialization() // TODO: use SenseId not string?
		] );
		$summary->addAutoSummaryArgs( [
			$this->language => $sense->getGlosses()->getByLanguage( $this->language )->getText()
		] );
	}

	public function getActions() {
		return [ EntityPermissionChecker::ACTION_EDIT ];
	}

}
