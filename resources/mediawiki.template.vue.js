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
				}
			};
		}
	} );

}() );
