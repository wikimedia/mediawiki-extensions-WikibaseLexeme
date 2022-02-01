// gives us a document to work on
require( 'jsdom-global' )();
// expected to exist by Vue (for instanceof check) but not provided by our jsdom version
// eslint-disable-next-line no-restricted-globals
global.SVGElement = function SVGElement() {};
