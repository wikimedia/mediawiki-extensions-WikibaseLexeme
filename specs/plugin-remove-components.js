'use strict';

// Fragments contribute only paths and tags to the runtime-joined document
// (see Wikibase's WikibaseRepoOpenApiDocFragments hook contract). After
// `bundle --dereferenced` inlines every $ref, the components section is
// redundant; drop it so the committed artifact carries no dead weight.
module.exports = function () {
	return {
		id: 'fragment',
		decorators: {
			oas3: {
				removeComponents: function () {
					return {
						Root: {
							leave( root ) {
								delete root.components;
							}
						}
					};
				}
			}
		}
	};
};
