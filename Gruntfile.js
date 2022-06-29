/* jshint node:true */
module.exports = function ( grunt ) {
	'use strict';

	// load all tasks
	require( 'load-grunt-tasks' )( grunt, { scope: 'devDependencies' } );

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),
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
				src: [ '*.css', '!*.min.css' ],
				dest: '<%= dirs.css %>/',
				ext: '.min.css'
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
		// Check the text domain
		checktextdomain: {
			standard: {
				options: {
					text_domain: [ 'download-monitor' ], //Specify allowed domain(s)
					create_report_file: 'true',
					keywords: [ //List keyword specifications
						'__:1,2d',
						'_e:1,2d',
						'_x:1,2c,3d',
						'esc_html__:1,2d',
						'esc_html_e:1,2d',
						'esc_html_x:1,2c,3d',
						'esc_attr__:1,2d',
						'esc_attr_e:1,2d',
						'esc_attr_x:1,2c,3d',
						'_ex:1,2c,3d',
						'_n:1,2,4d',
						'_nx:1,2,4c,5d',
						'_n_noop:1,2,3d',
						'_nx_noop:1,2,3c,4d'
					]
				},
				files: [
					{
						src: [
							'**/*.php',
							'!**/node_modules/**',
						], //all php
						expand: true
					}
				]
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


		clean: {
			init: {
				src: [ 'build/' ]
			},
		},
		copy: {
			build: {
				expand: true,
				src: [
					'**',
					'!node_modules/**',
					'!dummy_data/**',
					'!vendor/**',
					'!build/**',
					'!readme.md',
					'!README.md',
					'!phpcs.ruleset.xml',
					'!package-lock.json',
					'!svn-ignore.txt',
					'!Gruntfile.js',
					'!package.json',
					'!composer.json',
					'!composer.lock',
					'!postcss.config.js',
					'!webpack.config.js',
					'!set_tags.sh',
					'!download-monitor.zip',
					'!old/**',
					'!bin/**',
					'!tests/**',
					'!codeception.dist.yml',
					'!regconfig.json',
					'!nbproject/**'
				],
				dest: 'build/'
			}
		},
		compress: {
			build: {
				options: {
					pretty: true,                           // Pretty print file sizes when logging.
					archive: '<%= pkg.name %>.zip'
				},
				expand: true,
				cwd: '',
				src: [ 
				'**',
				'!node_modules/**',
				'!dummy_data/**',
				'!.github/**',
				'!.git/**',
				'!build/**',
				'!readme.md',
				'!README.md',
				'!phpcs.ruleset.xml',
				'!package-lock.json',
				'!svn-ignore.txt',
				'!Gruntfile.js',
				'!package.json',
				'!composer.json',
				'!composer.lock',
				'!postcss.config.js',
				'!webpack.config.js',
				'!set_tags.sh',
				'!dlm-email-notification.zip',
				'!old/**',
				'!bin/**',
				'!tests/**',
				'!codeception.dist.yml',
				'!regconfig.json',
				'!nbproject/**' ],
				dest: '<%= pkg.name %>'
			}
		},

	} );

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-contrib-less' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks('grunt-wp-i18n');
	grunt.loadNpmTasks('grunt-checktextdomain');
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks('@floatwork/grunt-po2mo');
	grunt.loadNpmTasks('grunt-contrib-compress');

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
