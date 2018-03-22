<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Tests\Api\WikibaseApiTestCase;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
abstract class WikibaseLexemeApiTestCase extends WikibaseApiTestCase {

	/**
	 * @var EntityStore
	 */
	protected $entityStore;

	public function setUp() {
		parent::setUp();

		$this->entityStore = WikibaseRepo::getDefaultInstance()->getEntityStore();
	}

}
