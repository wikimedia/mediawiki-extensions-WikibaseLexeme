<?php

namespace Wikibase\Lexeme\Tests\MediaWiki;

use ApiTestCase;
use ApiUsageException;

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
 */
class WikibaseLexemeExtensionRegistrationTest extends ApiTestCase {

	/**
	 * @dataProvider provideLexemeApiModules
	 */
	public function testGivenRepoEnabledLexemeApiModulesRegistered( $module ) {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( 'WikibaseRepo not enabled' );
		}

		try {
			$this->doApiRequest( [ 'action' => $module ] );
			$this->fail( 'Exception expected but not thrown' );
		} catch ( ApiUsageException $e ) {
			$this->assertEquals( 'apierror-missingparam', $e->getMessageObject()->getKey() );
		}
	}

	/**
	 * @dataProvider provideLexemeApiModules
	 */
	public function testGivenRepoNotEnabledNoLexemeApiModulesRegistered( $module ) {
		if ( defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( 'WikibaseRepo enabled' );
		}

		try {
			$this->doApiRequest( [ 'action' => $module ] );
			$this->fail( 'Exception expected but not thrown' );
		} catch ( ApiUsageException $e ) {
			$this->assertEquals( 'apierror-unrecognizedvalue', $e->getMessageObject()->getKey() );
		}
	}

	public function provideLexemeApiModules() {
		return [
			[ 'wbladdform' ],
			[ 'wbleditformelements' ],
			[ 'wblremoveform' ],
		];
	}

}
