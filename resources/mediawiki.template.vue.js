( function () {
	// Register vue compiler
	mw.template.registerCompiler( 'vue', {
		compile: function ( src ) {
			return {
				/**
				 * @ignore
				 * @return {string} The raw source code of the template
				 */
				getSource: function () {
					return src;
				},
				/**
				 * @return {string} The source code of the template, with the correct save message key
				 */
				renderSaveMessage: function () {
					var saveMessageKey = mw.config.get( 'wgEditSubmitButtonLabelPublish' )
						? 'wikibase-publish'
						: 'wikibase-save';

					return src.replace( '%saveMessageKey%', saveMessageKey );
				}
			};
		}
	} );

}() );
