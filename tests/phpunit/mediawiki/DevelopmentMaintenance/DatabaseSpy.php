<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\DevelopmentMaintenance;

use RuntimeException;
use Wikimedia\Rdbms\DBMasterPos;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * A very incomplete and limited spy, used to log update calls.
 */
class DatabaseSpy implements IDatabase {

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

	public function getServerInfo() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function bufferResults( $buffer = null ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function trxLevel() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function trxTimestamp() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function explicitTrxActive() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function tablePrefix( $prefix = null ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function dbSchema( $schema = null ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function getLBInfo( $name = null ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function setLBInfo( $name, $value = null ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function setLazyMasterHandle( IDatabase $conn ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function implicitGroupby() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function implicitOrderby() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function lastQuery() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function doneWrites() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function lastDoneWrites() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function writesPending() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function writesOrCallbacksPending() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function pendingWriteQueryDuration( $type = self::ESTIMATE_TOTAL ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function pendingWriteCallers() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function pendingWriteRowsAffected() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function isOpen() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function setFlag( $flag, $remember = self::REMEMBER_NOTHING ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function clearFlag( $flag, $remember = self::REMEMBER_NOTHING ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function restoreFlags( $state = self::RESTORE_PRIOR ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function getFlag( $flag ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function getDomainID() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function getWikiID() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function getType() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function open( $server, $user, $password, $dbName ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function fetchObject( $res ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function fetchRow( $res ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function numRows( $res ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function numFields( $res ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function fieldName( $res, $n ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function insertId() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function dataSeek( $res, $row ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function lastErrno() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function lastError() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function fieldInfo( $table, $field ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function affectedRows() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function getSoftwareLink() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function getServerVersion() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function close() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function reportConnectionError( $error = 'Unknown error' ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function query( $sql, $fname = __METHOD__, $tempIgnore = false ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function reportQueryError( $error, $errno, $sql, $fname, $tempIgnore = false ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function freeResult( $res ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function selectField(
		$table, $var, $cond = '', $fname = __METHOD__, $options = [], $join_conds = []
	) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function selectFieldValues(
		$table, $var, $cond = '', $fname = __METHOD__, $options = [], $join_conds = []
	) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function selectSQLText(
		$table, $vars, $conds = '', $fname = __METHOD__,
		$options = [], $join_conds = []
	) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function selectRow( $table, $vars, $conds, $fname = __METHOD__,
							  $options = [], $join_conds = []
	) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function estimateRowCount(
		$table, $vars = '*', $conds = '', $fname = __METHOD__, $options = [], $join_conds = []
	) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function selectRowCount(
		$tables, $vars = '*', $conds = '', $fname = __METHOD__, $options = [], $join_conds = []
	) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function fieldExists( $table, $field, $fname = __METHOD__ ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function indexExists( $table, $index, $fname = __METHOD__ ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function tableExists( $table, $fname = __METHOD__ ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function indexUnique( $table, $index ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function insert( $table, $a, $fname = __METHOD__, $options = [] ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function makeList( $a, $mode = self::LIST_COMMA ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function makeWhereFrom2d( $data, $baseKey, $subKey ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function aggregateValue( $valuedata, $valuename = 'value' ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function bitNot( $field ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function bitAnd( $fieldLeft, $fieldRight ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function bitOr( $fieldLeft, $fieldRight ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function buildConcat( $stringList ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function buildGroupConcatField(
		$delim, $table, $field, $conds = '', $join_conds = []
	) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function buildStringCast( $field ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function databasesAreIndependent() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function selectDB( $db ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function getDBname() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function getServer() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function addQuotes( $s ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function buildLike() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function anyChar() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function anyString() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function nextSequenceValue( $seqName ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function replace( $table, $uniqueIndexes, $rows, $fname = __METHOD__ ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function upsert(
		$table, array $rows, array $uniqueIndexes, array $set, $fname = __METHOD__
	) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function deleteJoin( $delTable, $joinTable, $delVar, $joinVar, $conds,
							   $fname = __METHOD__
	) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function delete( $table, $conds, $fname = __METHOD__ ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function insertSelect( $destTable, $srcTable, $varMap, $conds,
								 $fname = __METHOD__,
								 $insertOptions = [], $selectOptions = [], $selectJoinConds = []
	) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function unionSupportsOrderAndLimit() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function unionQueries( $sqls, $all ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function unionConditionPermutations(
		$table, $vars, array $permute_conds, $extra_conds = '', $fname = __METHOD__,
		$options = [], $join_conds = []
	) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function conditional( $cond, $trueVal, $falseVal ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function strreplace( $orig, $old, $new ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function getServerUptime() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function wasDeadlock() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function wasLockTimeout() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function wasErrorReissuable() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function wasReadOnlyError() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function masterPosWait( DBMasterPos $pos, $timeout ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function getReplicaPos() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function getMasterPos() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function serverIsReadOnly() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function onTransactionResolution( callable $callback, $fname = __METHOD__ ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function onTransactionIdle( callable $callback, $fname = __METHOD__ ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function onTransactionPreCommitOrIdle( callable $callback, $fname = __METHOD__ ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function setTransactionListener( $name, callable $callback = null ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function startAtomic( $fname = __METHOD__ ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function endAtomic( $fname = __METHOD__ ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function doAtomicSection( $fname, callable $callback ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function begin( $fname = __METHOD__, $mode = self::TRANSACTION_EXPLICIT ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function commit( $fname = __METHOD__, $flush = '' ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function rollback( $fname = __METHOD__, $flush = '' ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function flushSnapshot( $fname = __METHOD__ ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function listTables( $prefix = null, $fname = __METHOD__ ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function timestamp( $ts = 0 ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function timestampOrNull( $ts = null ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function ping( &$rtt = null ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function getLag() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function getSessionLagStatus() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function maxListLen() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function encodeBlob( $b ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function decodeBlob( $b ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function setSessionOptions( array $options ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function setSchemaVars( $vars ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function lockIsFree( $lockName, $method ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function lock( $lockName, $method, $timeout = 5 ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function unlock( $lockName, $method ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function getScopedLockAndFlush( $lockKey, $fname, $timeout ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function namedLocksEnqueue() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function getInfinity() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function encodeExpiry( $expiry ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function decodeExpiry( $expiry, $format = TS_MW ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function setBigSelects( $value = true ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function isReadOnly() {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function setTableAliases( array $aliases ) {
		throw new \RuntimeException( 'not yet implemented!' );
	}

	public function buildSubString( $input, $startPosition, $length = null ) {
		throw new \RuntimeException( __METHOD__ . ' not yet implemented!' );
	}

	public function buildIntegerCast( $field ) {
		throw new \RuntimeException( __METHOD__ . ' not yet implemented!' );
	}

	public function setIndexAliases( array $aliases ) {
		throw new \RuntimeException( __METHOD__ . ' not yet implemented!' );
	}

}
