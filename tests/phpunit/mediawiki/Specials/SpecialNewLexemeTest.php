<?php

namespace Wikibase\Lexeme\Tests\Specials;

use SpecialPageTestBase;
use Wikibase\Lexeme\Specials\SpecialNewLexeme;
use Wikibase\Repo\Specials\SpecialNewProperty;

/**
 * @covers Wikibase\Lexeme\Specials\SpecialNewLexeme
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class SpecialNewLemexeTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		return new SpecialNewLexeme();
	}

	public function testExecute() {

		$matchers['lemma'] = [
			'tag' => 'div',
			'attributes' => [
				'id' => 'wb-newentity-label',
			],
			'child' => [
				'tag' => 'input',
				'attributes' => [
					'name' => 'lemma',
				]
			] ];

		list( $output, ) = $this->executeSpecialPage( '' );
		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}

		list( $output, ) = $this->executeSpecialPage( 'LemmaText' );
		$matchers['lemma']['child'][0]['attributes']['value'] = 'LemmaText';

		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

}
