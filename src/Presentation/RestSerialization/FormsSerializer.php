<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Presentation\RestSerialization;

use Wikibase\Lexeme\Domain\Model\ReadModel\Forms;
use Wikibase\Repo\Domains\Statements\Application\Serialization\StatementListSerializer;

/**
 * @license GPL-2.0-or-later
 */
class FormsSerializer {

	public function __construct(
		private RepresentationsSerializer $representationsSerializer,
		private GrammaticalFeaturesSerializer $grammaticalFeaturesSerializer,
		private StatementListSerializer $statementListSerializer,
	) {
	}

	public function serialize( Forms $forms ): array {
		$result = [];
		foreach ( $forms as $form ) {
			$result[] = [
				'id' => $form->id->getSerialization(),
				'representations' => $this->representationsSerializer->serialize( $form->representations ),
				'grammatical_features' => $this->grammaticalFeaturesSerializer->serialize( $form->grammaticalFeatures ),
				'statements' => $this->statementListSerializer->serialize( $form->statements ),
			];
		}
		return $result;
	}

}
