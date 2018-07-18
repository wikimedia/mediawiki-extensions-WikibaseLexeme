<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use CloneDatabase;
use User;

/**
 * Provides methods to create a db clone with non-temporary tables as a workaround
 * for temporary table problems with MySQL.
 * https://dev.mysql.com/doc/refman/8.0/en/temporary-table-problems.html
 *
 * Use as a trait for a MediaWikiTestCase.
 *
 * @license GPL-2.0-or-later
 */
trait NonTempTableTestCase {

	private $tablePrefix = '';

	private static $oldTablePrefix = '';

	/** @var CloneDatabase */
	private static $clonedDb;

	/** @var User */
	private static $user;

	public function setUp() {
		parent::setUp();

		$this->setupNonTempTableDbAndUser();
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();

		self::tearDownNonTempTableDb();
	}

	/**
	 * Clones the database to use non temporary tables and creates a test user.
	 *
	 * Call in setUp(), left protected so you can still call it should you override that
	 */
	protected function setupNonTempTableDbAndUser() {
		$className = basename( str_replace( '\\', '/', __CLASS__ ) );

		$prefix = $className . '-table-';
		$user = $className . '-user';

		if ( $this->db->tablePrefix() !== $prefix ) {
			$this->cloneDbWithNonTempTables( $prefix );
			self::$user = User::createNew( $user );
		}
	}

	/**
	 * Destroys the db with non-temporary tables and switches back to the previously used db
	 *
	 * Call in tearDownAfterClass(), left protected so you can still call it should you override that
	 */
	protected static function tearDownNonTempTableDb() {
		self::$clonedDb->destroy( true );
		CloneDatabase::changePrefix( self::$oldTablePrefix );
	}

	protected function getUser() {
		return self::$user;
	}

	private function cloneDbWithNonTempTables( $prefix ) {
		self::$oldTablePrefix = $this->db->tablePrefix();

		$dbClone = new CloneDatabase( $this->db,
			$this->db->listTables(),
			$prefix,
			$this->dbPrefix()
		);
		$dbClone->useTemporaryTables( false );
		$dbClone->cloneTableStructure();
		self::$clonedDb = $dbClone;

		CloneDatabase::changePrefix( $prefix );
	}

}
