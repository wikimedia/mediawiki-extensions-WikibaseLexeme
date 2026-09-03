<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Interactors;

use LogicException;
use RuntimeException;

/**
 * @license GPL-2.0-or-later
 */
class UseCaseError extends RuntimeException {

	public const string RESOURCE_NOT_FOUND = 'resource-not-found';
	public const string INVALID_PATH_PARAMETER = 'invalid-path-parameter';
	public const string MISSING_FIELD = 'missing-field';
	public const string INVALID_VALUE = 'invalid-value';
	public const string INVALID_KEY = 'invalid-key';
	public const string REQUEST_LIMIT_REACHED = 'request-limit-reached';
	public const string REQUEST_LIMIT_REASON_RATE_LIMIT = 'rate-limit-reached';
	public const string REQUEST_LIMIT_REASON_TEMP_ACCOUNT_CREATION_LIMIT = 'temp-account-creation-limit-reached';
	public const string VALUE_TOO_LONG = 'value-too-long';
	public const string RESOURCE_TOO_LARGE = 'resource-too-large';
	public const string REFERENCED_RESOURCE_NOT_FOUND = 'referenced-resource-not-found';
	public const string PERMISSION_DENIED = 'permission-denied';
	public const string PERMISSION_DENIED_REASON_USER_BLOCKED = 'blocked-user';
	public const string PERMISSION_DENIED_REASON_IP_BLOCKED = 'blocked-ip';
	public const string PERMISSION_DENIED_UNKNOWN_REASON = 'permission-denied-unknown-reason';
	public const string STATEMENT_GROUP_PROPERTY_ID_MISMATCH = 'statement-group-property-id-mismatch';

	public const string CONTEXT_DENIAL_CONTEXT = 'denial_context';
	public const string CONTEXT_DENIAL_REASON = 'denial_reason';
	public const string CONTEXT_RESOURCE_TYPE = 'resource_type';
	public const string CONTEXT_PARAMETER = 'parameter';
	public const string CONTEXT_PATH = 'path';
	public const string CONTEXT_FIELD = 'field';
	public const string CONTEXT_KEY = 'key';
	public const string CONTEXT_LIMIT = 'limit';
	public const string CONTEXT_REASON = 'reason';
	public const string CONTEXT_STATEMENT_GROUP_PROPERTY_ID = 'statement_group_property_id';
	public const string CONTEXT_STATEMENT_PROPERTY_ID = 'statement_property_id';

	private const array EXPECTED_CONTEXT_KEYS = [
		self::RESOURCE_NOT_FOUND => [
			'required' => [ self::CONTEXT_RESOURCE_TYPE ],
		],
		self::INVALID_PATH_PARAMETER => [
			'required' => [ self::CONTEXT_PARAMETER ],
		],
		self::MISSING_FIELD => [
			'required' => [ self::CONTEXT_PATH, self::CONTEXT_FIELD ],
		],
		self::INVALID_VALUE => [
			'required' => [ self::CONTEXT_PATH ],
		],
		self::INVALID_KEY => [
			'required' => [ self::CONTEXT_PATH, self::CONTEXT_KEY ],
		],
		self::VALUE_TOO_LONG => [
			'required' => [ self::CONTEXT_PATH, self::CONTEXT_LIMIT ],
		],
		self::REFERENCED_RESOURCE_NOT_FOUND => [
			'required' => [ self::CONTEXT_PATH ],
		],
		self::REQUEST_LIMIT_REACHED => [
			'required' => [ self::CONTEXT_REASON ],
		],
		self::PERMISSION_DENIED => [
			'required' => [ self::CONTEXT_DENIAL_REASON ],
			'optional' => [ self::CONTEXT_DENIAL_CONTEXT ],
		],
		self::PERMISSION_DENIED_UNKNOWN_REASON => [
			'required' => [],
		],
		self::STATEMENT_GROUP_PROPERTY_ID_MISMATCH => [
			'required' => [
				self::CONTEXT_PATH,
				self::CONTEXT_STATEMENT_GROUP_PROPERTY_ID,
				self::CONTEXT_STATEMENT_PROPERTY_ID,
			],
		],
		self::RESOURCE_TOO_LARGE => [
			'required' => [ self::CONTEXT_LIMIT ],
		],
	];

	public function __construct(
		public readonly string $errorCode,
		public readonly string $errorMessage,
		public readonly array $context = [],
	) {
		parent::__construct();

		if ( !array_key_exists( $errorCode, self::EXPECTED_CONTEXT_KEYS ) ) {
			throw new LogicException( "Unknown error code: '$errorCode'" );
		}

		$contextDefinition = self::EXPECTED_CONTEXT_KEYS[$errorCode];
		$contextKeys = array_keys( $context );
		$expectedContextKeys = array_merge( ...array_values( $contextDefinition ) );
		$unexpectedContext = array_values( array_diff( $contextKeys, $expectedContextKeys ) );
		if ( $unexpectedContext ) {
			throw new LogicException(
				"Error context for '$errorCode' should not contain keys: " . json_encode( $unexpectedContext )
			);
		}
		$missingContext = array_values( array_diff( $contextDefinition['required'], $contextKeys ) );
		if ( $missingContext ) {
			throw new LogicException(
				"Error context for '$errorCode' should contain keys: " . json_encode( $missingContext )
			);
		}
	}

	public static function newResourceNotFound( string $resourceType ): self {
		return new self(
			self::RESOURCE_NOT_FOUND,
			'The requested resource does not exist',
			[ self::CONTEXT_RESOURCE_TYPE => $resourceType ],
		);
	}

	public static function newInvalidPathParameter( string $parameterName ): self {
		return new self(
			self::INVALID_PATH_PARAMETER,
			"Invalid path parameter: '$parameterName'",
			[ self::CONTEXT_PARAMETER => $parameterName ],
		);
	}

	public static function newMissingField( string $path, string $field ): self {
		return new self(
			self::MISSING_FIELD,
			'Required field missing',
			[ self::CONTEXT_PATH => $path, self::CONTEXT_FIELD => $field ],
		);
	}

	public static function newReferencedResourceNotFound( string $path ): self {
		return new self(
			self::REFERENCED_RESOURCE_NOT_FOUND,
			'The referenced resource does not exist',
			[ self::CONTEXT_PATH => $path ],
		);
	}

	public static function newInvalidValue( string $path ): self {
		return new self(
			self::INVALID_VALUE,
			"Invalid value at '$path'",
			[ self::CONTEXT_PATH => $path ],
		);
	}

	public static function newInvalidKey( string $path, string $key ): self {
		return new self(
			self::INVALID_KEY,
			"Invalid key '$key' in '$path'",
			[ self::CONTEXT_PATH => $path, self::CONTEXT_KEY => $key ],
		);
	}

	public static function newValueTooLong( string $path, int $maxLength ): self {
		return new self(
			self::VALUE_TOO_LONG,
			'The input value is too long',
			[ self::CONTEXT_PATH => $path, self::CONTEXT_LIMIT => $maxLength ],
		);
	}

	public static function newStatementGroupPropertyIdMismatch(
		string $path,
		string $statementGroupPropertyId,
		string $statementPropertyId,
	): self {
		return new self(
			self::STATEMENT_GROUP_PROPERTY_ID_MISMATCH,
			"Statement's Property ID does not match the Statement group key",
			[
				self::CONTEXT_PATH => $path,
				self::CONTEXT_STATEMENT_GROUP_PROPERTY_ID => $statementGroupPropertyId,
				self::CONTEXT_STATEMENT_PROPERTY_ID => $statementPropertyId,
			],
		);
	}

	public static function newRateLimitReached( string $reason ): self {
		return new self(
			self::REQUEST_LIMIT_REACHED,
			'Exceeded the limit of actions that can be performed in a given span of time',
			[ self::CONTEXT_REASON => $reason ]
		);
	}

	public static function newPermissionDenied( string $reason, array $denialContext = [], ): self {
		$context = [ self::CONTEXT_DENIAL_REASON => $reason ];

		if ( $denialContext ) {
			$context[self::CONTEXT_DENIAL_CONTEXT] = $denialContext;
		}

		return new self(
			self::PERMISSION_DENIED,
			'Access to resource is denied',
			$context
		);
	}

	public static function newResourceTooLarge( int $maxSizeInKb ): self {
		return new self(
			self::RESOURCE_TOO_LARGE,
			"Edit resulted in a resource that exceeds the size limit of $maxSizeInKb kB",
			[ self::CONTEXT_LIMIT => $maxSizeInKb ]
		);
	}
}
