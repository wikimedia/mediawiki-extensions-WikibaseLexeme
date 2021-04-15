<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpBase;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 */
class ChangeOpSenseAdd extends ChangeOpBase {

	private const SUMMARY_ACTION_ADD = 'add-sense';

	/**
	 * @var ChangeOp
	 */
	private $changeOpSense;

	/**
	 * @var GuidGenerator
	 */
	private $guidGenerator;

	/**
	 * @param ChangeOp $changeOpSense
	 */
	public function __construct( ChangeOp $changeOpSense, GuidGenerator $guidGenerator ) {
		$this->changeOpSense = $changeOpSense;
		$this->guidGenerator = $guidGenerator;
	}

	public function validate( EntityDocument $entity ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );

		return Result::newSuccess();
	}

	public function apply( EntityDocument $entity, Summary $summary = null ) {
		Assert::parameterType( Lexeme::class, $entity, '$entity' );
		'@phan-var Lexeme $entity';

		/** @var Lexeme $entity */

		$sense = new BlankSense();

		$entity->addOrUpdateSense( $sense );
		$this->changeOpSense->apply( $sense, null );

		// update statements to have a suitable guid now that the new sense id is known
		// fixme This should find a new home in a more central place, maybe StatementList
		foreach ( $sense->getStatements() as $statement ) {
			$statement->setGuid( $this->guidGenerator->newGuid( $sense->getId() ) );
		}

		if ( $sense->getGlosses()->count() === 1 ) {
			$array = $sense->getGlosses()->toTextArray();
			reset( $array );
			$language = key( $array );
		} else {
			$language = null;
		}

		if ( $summary !== null ) {
			// TODO: consistently do not extend ChangeOpBase?
			$this->updateSummary(
				$summary,
				self::SUMMARY_ACTION_ADD,
				$language,
				array_values( $sense->getGlosses()->toTextArray() )
			);
			// TODO: use SenseId not string?
			$summary->addAutoCommentArgs( $sense->getId()->getSerialization() );
		}

		return new DummyChangeOpResult();
	}

}
