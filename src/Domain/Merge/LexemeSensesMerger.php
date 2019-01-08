<?php

namespace Wikibase\Lexeme\Domain\Merge;

use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseAdd;
use Wikibase\Lexeme\DataAccess\ChangeOp\ChangeOpSenseClone;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Repo\ChangeOp\ChangeOps;

/**
 * @license GPL-2.0-or-later
 */
class LexemeSensesMerger {

	/**
	 * @var GuidGenerator
	 */
	private $guidGenerator;

	public function __construct(
		GuidGenerator $guidGenerator
	) {
		$this->guidGenerator = $guidGenerator;
	}

	/**
	 * @param Lexeme $source
	 * @param Lexeme $target Will be modified by reference
	 */
	public function merge( Lexeme $source, Lexeme $target ) {
		$changeOps = new ChangeOps();

		foreach ( $source->getSenses()->toArray() as $sourceSense ) {
			$changeOps->add( new ChangeOpSenseAdd(
				new ChangeOpSenseClone( $sourceSense ),
				$this->guidGenerator
			) );
		}

		$changeOps->apply( $target );
	}

}
