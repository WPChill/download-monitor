/* jshint node:true */
module.exports = function ( grunt ) {
	'use strict';

	// load all tasks
	require( 'load-grunt-tasks' )( grunt, { scope: 'devDependencies' } );

	grunt.initConfig( {
		// setting folder templates
		dirs: {
			css: 'assets/css',
			images: 'assets/images',
			js: 'assets/js',
			reports: 'assets/js/reports',
			shop: 'assets/js/shop',
			lang: 'languages'
		},

		// Compile all .less files.
		less: {
			compile: {
				options: {
					// These paths are searched for @imports
					paths: [ '<%= dirs.css %>/'  ]
				},
				files: [ {
					expand: true,
					cwd: '<%= dirs.css %>/',
					src: [
						'*.less',
						'!icons.less',
						'!mixins.less'
					],
					dest: '<%= dirs.css %>/',
					ext: '.css'
				} ]
			}
		},

		// Minify all .css files.
		cssmin: {
			minify: {
				expand: true,
				cwd: '<%= dirs.css %>/',
				src: [ '*.css' ],
				dest: '<%= dirs.css %>/',
				ext: '.css'
			}
		},

		// Minify .js files.
		uglify: {
			options: {
				preserveComments: 'some'
			},
			frontend: {
				files: [ {
					expand: true,
					cwd: '<%= dirs.js %>',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: '<%= dirs.js %>',
					ext: '.min.js'
				} ]
			},
			reports: {
				files: [ {
					expand: true,
					cwd: '<%= dirs.reports %>',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: '<%= dirs.reports %>',
					ext: '.min.js'
				} ]
			},
			shop: {
				files: [ {
					expand: true,
					cwd: '<%= dirs.shop %>',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: '<%= dirs.shop %>',
					ext: '.min.js'
				} ]
			}
		},

		// Watch changes for assets
		watch: {
			less: {
				files: [ '<%= dirs.css %>/*.less' ],
				tasks: [ 'less', 'cssmin' ]
			},
			js: {
				files: [
					'<%= dirs.js %>/*js',
					'<%= dirs.shop %>/*js',
					'!<%= dirs.js %>/*.min.js',
					'!<%= dirs.shop %>/*.min.js',
				],
				tasks: [ 'uglify' ]
			}
		},

		// Generate POT files.
		makepot: {
			options: {
				type: 'wp-plugin',
				domainPath: 'languages',
				potHeaders: {
					'report-msgid-bugs-to': 'https://github.com/download-monitor/download-monitor/issues',
					'language-team': 'LANGUAGE <EMAIL@ADDRESS>'
				}
			},
			frontend: {
				options: {
					potFilename: 'download-monitor.pot',
					exclude: [
						'node_modules/.*',
						'tests/.*',
						'tmp/.*'
					],
					processPot: function ( pot ) {
						return pot;
					}
				}
			}
		},

		po2mo: {
			files: {
				src: '<%= dirs.lang %>/*.po',
				expand: true
			}
		},

		shell: {
			options: {
				stdout: true,
				stderr: true
			},
			txpull: {
				command: [
					'tx pull -a -f',
				].join( '&&' )
			}
		},

		copy: {
			build: {
				expand: true,
				src: [
					'**',
					'!node_modules/**',
					'!build/**',
					'!tests/**',
					'!.git/**',
					'!.tx/**',
					'!readme.md',
					'!README.md',
					'!phpcs.ruleset.xml',
					'!package-lock.json',
					'!svn-ignore.txt',
					'!Gruntfile.js',
					'!package.json',
					'!composer.json',
					'!composer.lock',
					'!phpunit.xml',
					'!postcss.config.js',
					'!webpack.config.js',
					'!set_tags.sh',
					'!download-monitor.zip',
					'!old/**',
					'!nbproject/**'
				],
				dest: 'build/'
			}
		},

		compress: {
			build: {
				options: {
					pretty: true,                           // Pretty print file sizes when logging.
					archive: 'download-monitor.zip'
				},
				expand: true,
				cwd: 'build/',
				src: [ '**/*' ],
				dest: 'download-monitor/'
			}
		},

		clean: {
			init: {
				src: [ 'build/' ]
			}
		}

	} );

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-shell' );
	grunt.loadNpmTasks( 'grunt-contrib-less' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks('grunt-wp-i18n');
	grunt.loadNpmTasks('grunt-checktextdomain');
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks('@fltwk/grunt-po2mo');

	// Register tasks
	grunt.registerTask( 'default', [
		'less',
		'cssmin',
		'uglify'
	] );

	// Just an alias for pot file generation
	grunt.registerTask( 'pot', [
		'makepot'
	] );

	grunt.registerTask( 'dev', [
		'default',
		'shell:txpull',
		'makepot'
	] );

	// Build task
	grunt.registerTask( 'build-archive', [
		'clean',
		'copy',
		'compress:build',
		'clean'
	] );

	grunt.registerTask('makemo', ['po2mo']);

};
