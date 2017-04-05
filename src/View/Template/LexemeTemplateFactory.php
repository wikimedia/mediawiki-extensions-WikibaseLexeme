<?php

namespace Wikibase\Lexeme\View\Template;

use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\Template\TemplateRegistry;

/**
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeTemplateFactory extends TemplateFactory {

	/**
	 * @var self
	 */
	private static $instance;

	public static function getDefaultInstance() {
		if ( self::$instance === null ) {
			self::$instance = new self(
				new TemplateRegistry( include __DIR__ . '/../../../resources/templates.php' )
			);
		}

		return self::$instance;
	}

}
