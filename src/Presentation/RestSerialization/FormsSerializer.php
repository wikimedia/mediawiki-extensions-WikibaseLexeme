<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Presentation\RestSerialization;

use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;

/**
 * @license GPL-2.0-or-later
 */
class FormsSerializer {

	public function __construct( private FormSerializer $formSerializer ) {
	}

	public function serialize( Forms $forms ): array {
		$result = [];
		foreach ( $forms as $form ) {
			$result[] = $this->formSerializer->serialize( $form );
		}
		return $result;
	}

}
