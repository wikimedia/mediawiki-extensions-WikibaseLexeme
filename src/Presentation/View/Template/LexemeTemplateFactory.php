<?php

namespace Wikibase\Lexeme\Presentation\View\Template;

use Wikibase\View\Template\Template;
use Wikibase\View\Template\TemplateRegistry;

/**
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 * @author Thiemo Kreuz
 */
class LexemeTemplateFactory {

	/**
	 * @var TemplateRegistry
	 */
	private $templateRegistry;

	/**
	 * @param string[] $templates
	 */
	public function __construct( array $templates ) {
		$this->templateRegistry = new TemplateRegistry( $templates );
	}

	public static function factory(): self {
		$templates = include __DIR__ . '/../../../../resources/templates.php';
		return new self( $templates );
	}

	/**
	 * Shorthand function to retrieve a template filled with the specified parameters.
	 *
	 * important! note that the Template class does not escape anything.
	 * be sure to escape your params before using this function!
	 *
	 * @param string $key template key
	 * @param array  ...$params normal template parameters
	 *
	 * @return string
	 */
	public function render( $key, ...$params ) {

		if ( isset( $params[0] ) && is_array( $params[0] ) ) {
			$params = $params[0];
		}

		$template = new Template( $this->templateRegistry, $key, $params );

		return $template->render();
	}

}
