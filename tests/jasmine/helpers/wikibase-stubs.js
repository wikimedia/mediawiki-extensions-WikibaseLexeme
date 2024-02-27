/**
 * In production, wikibase is present in the global namespace
 * In the jasmine tests, we need to stub the classes we're using
 */

/* eslint no-restricted-globals: 0 */
/* eslint no-implicit-globals: 0 */

class TempUserWatcher {
	getRedirectUrl() {
		return this.redirectUrl;
	}
	processApiResult( result ) {
		this.redirectUrl = result.tempuserredirect;
	}
}

class ValueChangeResult {
	constructor( savedValue, tempUserWatcher ) {
		this.savedValue = savedValue;
		this.tempUserWatcher = tempUserWatcher;
	}

	getSavedValue() {
		return this.savedValue;
	}

	getTempUserWatcher() {
		return this.tempUserWatcher;
	}
}

module.exports = function() {
	return {
		entityChangers: {
			TempUserWatcher: TempUserWatcher,
			ValueChangeResult: ValueChangeResult,
		}
	};
}