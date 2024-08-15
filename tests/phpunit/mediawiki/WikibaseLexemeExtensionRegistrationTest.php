<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use ApiUsageException;
use ExtensionRegistry;
use MediaWiki\Tests\Api\ApiTestCase;

/**
 * TODO: Those test should not really be skipped but always run. There is no way
 * to temporarily enable, and especially disable, Repo component for a single
 * test, so done like this for now.
 * TODO: The repo- and client-specific code should be moved to separate extensions,
 * and then such test becomes also simpler.
 *
 * @coversNothing
 *
 * @group medium
 * In the Database group because it extends the ApiTestCase that in setUp creates users
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class WikibaseLexemeExtensionRegistrationTest extends ApiTestCase {

	/**
	 * @dataProvider provideLexemeApiModules
	 */
	public function testLexemeApiModulesRegistered( $module ) {
		try {
			$this->doApiRequest( [ 'action' => $module ] );
			$this->fail( 'Exception expected but not thrown' );
		} catch ( ApiUsageException $e ) {
			$expected = ExtensionRegistry::getInstance()->isLoaded( 'WikibaseRepository' ) ?
				'paramvalidator-missingparam' :
				'paramvalidator-badvalue-enumnotmulti';
			$this->assertStatusError( $expected, $e->getStatusValue() );
		}
	}

	public static function provideLexemeApiModules() {
		return [
			[ 'wbladdform' ],
			[ 'wbleditformelements' ],
			[ 'wblremoveform' ],
		];
	}

}
