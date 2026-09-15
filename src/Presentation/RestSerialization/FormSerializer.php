<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Presentation\RestSerialization;

use Wikibase\Lexeme\Domain\Model\ReadModel\Form;
use Wikibase\Repo\Domains\Statements\Application\Serialization\StatementListSerializer;

/**
 * @license GPL-2.0-or-later
 */
class FormSerializer {

	public function __construct(
		private RepresentationsSerializer $representationsSerializer,
		private GrammaticalFeaturesSerializer $grammaticalFeaturesSerializer,
		private StatementListSerializer $statementListSerializer,
	) {
	}

	public function serialize( Form $form ): array {
		return [
			'id' => $form->id->getSerialization(),
			'representations' => $this->representationsSerializer->serialize( $form->representations ),
			'grammatical_features' => $this->grammaticalFeaturesSerializer->serialize( $form->grammaticalFeatures ),
			'statements' => $this->statementListSerializer->serialize( $form->statements ),
		];
	}

}
