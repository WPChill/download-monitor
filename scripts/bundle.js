const fs = require( 'fs' );
const path = require( 'path' );
const archiver = require( 'archiver' );

const pluginSlug = 'download-monitor';
const pluginFolder = 'download-monitor';
const version = require( '../package.json' ).version;

const output = fs.createWriteStream(
	path.join( __dirname, `../${ pluginFolder }-${ version }.zip` ),
);
const archive = archiver( 'zip', {
	zlib: { level: 9 },
} );

output.on( 'close', function() {
	console.log( archive.pointer() + ' total bytes' );
	console.log(
		'Archive has been finalized and the output file descriptor has closed.',
	);
} );
archive.on( 'error', function( err ) {
	throw err;
} );

archive.pipe( output );

archive.directory( 'build/includes/', `${ pluginFolder }/includes` );
archive.directory( 'build/assets/', `${ pluginFolder }/assets` );
archive.directory( 'build/languages/', `${ pluginFolder }/languages` );
archive.directory( 'build/src/', `${ pluginFolder }/src` );
archive.directory( 'build/templates/', `${ pluginFolder }/templates` );
archive.directory( 'build/vendor/', `${ pluginFolder }/vendor` );
archive.file( 'build/download-monitor.php', { name: `${ pluginFolder }/download-monitor.php` } );
archive.file( 'build/readme.txt', { name: `${ pluginFolder }/readme.txt` } );
archive.file( 'build/changelog.txt', { name: `${ pluginFolder }/changelog.txt` } );
archive.file( 'build/autoloader.php', {
	name: `${ pluginFolder }/autoloader.php`,
} );

archive.finalize();
