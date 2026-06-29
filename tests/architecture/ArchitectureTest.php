<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

/**
 * @coversNothing
 *
 * @license GPL-2.0-or-later
 */
class ArchitectureTest {
	private const DOMAIN_MODELS = 'Wikibase\Lexeme\Domain\Model';
	private const DOMAIN_DUMMY = 'Wikibase\Lexeme\Domain\DummyObjects';
	private const DOMAIN_SERVICES = 'Wikibase\Lexeme\Domain\Services';
	private const APPLICATION_REST_SERIALIZATION = 'Wikibase\Lexeme\Presentation\RestSerialization';
	private const APPLICATION_VALIDATORS = 'Wikibase\Lexeme\Validation';
	private const APPLICATION_USE_CASES = 'Wikibase\Lexeme\Interactors';

	public function testDomainModel(): Rule {
		return PHPat::rule()
			->classes( Selector::inNamespace( self::DOMAIN_MODELS ) )
			->canOnlyDependOn()
			->classes( ...$this->allowedDomainModelDependencies() );
	}

	/**
	 * Domain models may depend on:
	 *  - DataModel namespaces containing entities and their parts
	 *  - the domain's DummyObjects namespace
	 *  - other classes from their own namespace
	 */
	private function allowedDomainModelDependencies(): array {
		return [
			...$this->dataModelNamespaces(),
			Selector::inNamespace( self::DOMAIN_MODELS ),
			Selector::inNamespace( self::DOMAIN_DUMMY ),
		];
	}

	public function testDomainServices(): Rule {
		return PHPat::rule()
			->classes( Selector::inNamespace( self::DOMAIN_SERVICES ) )
			->canOnlyDependOn()
			->classes( ...$this->allowedDomainServicesDependencies() );
	}

	/**
	 * Domain services may depend on:
	 *  - the domain models namespace and everything it depends on
	 *  - some hand-picked DataModel services
	 *  - other classes from their own namespace
	 */
	private function allowedDomainServicesDependencies(): array {
		return [
			...$this->allowedDomainModelDependencies(),
			...$this->allowedDataModelServices(),
			Selector::inNamespace( self::DOMAIN_SERVICES ),
		];
	}

	public function testSerialization(): Rule {
		return PHPat::rule()
			->classes( Selector::inNamespace( self::APPLICATION_REST_SERIALIZATION ) )
			->canOnlyDependOn()
			->classes( ...$this->allowedSerializationDependencies() );
	}

	/**
	 * Serialization may depend on:
	 *  - the domain services namespace and everything it depends on
	 *  - other classes from its own namespace
	 */
	private function allowedSerializationDependencies(): array {
		return [
			...$this->allowedDomainServicesDependencies(),
			Selector::inNamespace( self::APPLICATION_REST_SERIALIZATION ),
		];
	}

	public function testValidation(): Rule {
		return PHPat::rule()
			->classes( Selector::inNamespace( self::APPLICATION_VALIDATORS ) )
			->canOnlyDependOn()
			->classes( ...$this->allowedValidationDependencies() );
	}

	/**
	 * Validation may depend on:
	 *  - the serialization namespace and everything it depends on
	 *  - other classes from its own namespace
	 */
	private function allowedValidationDependencies(): array {
		return [
			...$this->allowedSerializationDependencies(),
			Selector::inNamespace( self::APPLICATION_VALIDATORS ),
		];
	}

	public function testUseCases(): Rule {
		return PHPat::rule()
			->classes( Selector::inNamespace( self::APPLICATION_USE_CASES ) )
			->excluding( Selector::inNamespace( 'Wikibase\Lexeme\Interactors\MergeLexemes' ) )
			->canOnlyDependOn()
			->classes( ...$this->allowedUseCasesDependencies() );
	}

	/**
	 * Use cases may depend on:
	 *  - the validation namespace and everything it depends on
	 *  - other classes from their own namespace
	 */
	private function allowedUseCasesDependencies(): array {
		return [
			...$this->allowedValidationDependencies(),
			Selector::inNamespace( self::APPLICATION_USE_CASES ),
		];
	}

	private function allowedDataModelServices(): array {
		return [];
	}

	private function dataModelNamespaces(): array {
		return [
			// These are listed in such a complicated way so that only DataModel
			// entities and their parts are allowed without the
			// namespaces nested within DataModel like e.g. Wikibase\DataModel\Serializers.
			...array_map(
				static fn ( string $escapedNamespace ) => Selector::classname(
					'/^' . preg_quote( $escapedNamespace ) . '\\\\\w+$/',
					true
				),
				[
					'Wikibase\DataModel',
					'Wikibase\DataModel\Entity',
					'Wikibase\DataModel\Exception',
					'Wikibase\DataModel\Snak',
					'Wikibase\DataModel\Statement',
					'Wikibase\DataModel\Term',
					'Wikimedia\Assert',
				]
			),
			Selector::inNamespace( 'DataValues' ),
		];
	}

}
