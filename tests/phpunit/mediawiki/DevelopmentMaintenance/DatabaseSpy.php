<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\DevelopmentMaintenance;

use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\IDatabase;

/**
 * A very incomplete and limited spy, used to log update calls.
 */
abstract class DatabaseSpy implements IDatabase {

	private $selectData;

	private $maxSelectCallCount;

	private $selectCallCount;

	private $updateData = [];

	public function __construct( IResultWrapper $selectData, $maxSelectCallCount ) {
		$this->selectData = $selectData;
		$this->maxSelectCallCount = $maxSelectCallCount;
		$this->selectCallCount = 0;
	}

	/**
	 * @see IDatabase::update
	 */
	public function update( $table, $values, $conds, $fname = __METHOD__, $options = [] ) {
		$this->updateData[] = [
			'new' => $values,
			'conds' => $conds,
		];
	}

	public function getUpdateData() {
		return $this->updateData;
	}

	/**
	 * @see IDatabase::select
	 */
	public function select(
		$table, $vars, $conds = '', $fname = __METHOD__,
		$options = [], $join_conds = []
	) {
		if ( $this->selectCallCount === $this->maxSelectCallCount ) {
			return [];
		}

		$this->selectCallCount ++;

		return $this->selectData;
	}

}
