module.exports = function( grunt ) {

	grunt.initConfig( {

		pkg: grunt.file.readJSON( 'package.json' ),

		dirs: {
			lang: 'languages'
		},

		less: {
			development: {
				options: {
					compress: true,
					yuicompress: true,
					optimization: 2,
				},
				files: {
					// target.css file: source.less file
					'assets/css/public.css': 'assets/css/source/public.less',
					'assets/css/admin.css': 'assets/css/source/admin.less',
				},
			},
		},

		uglify: {
			options: { mangle: false },
			business_hours: {
				files: [
					{
						expand: true,
						cwd: 'assets/js',
						src: [ '**/*.js', '!**/*.min.js' ],
						dest: 'assets/js',
						ext: '.min.js',
					},
				],
			},
		},

		watch: {
			business_hours: {
				files: [ 'assets/css/source/*.less', 'assets/js/*.js', '!assets/js/*.min.js', 'readme.txt' ],
				tasks: [ 'uglify', 'wp_readme_to_markdown', 'less' ],
			},
		},

		wp_readme_to_markdown: {
			your_target: {
				files: {
					'readme.md': 'readme.txt',
				},
			},
		},

		// Pull in the latest translations
		exec: {
			// Create a ZIP file
			zip: 'python /usr/bin/git-archive-all ../gravity-forms-business-hours.zip',
			transifex: 'tx pull -a --parallel',
		},

		// Convert the .po files to .mo files
		potomo: {
			dist: {
				options: {
					poDel: false,
				},
				files: [
					{
						expand: true,
						cwd: '<%= dirs.lang %>',
						src: [ '*.po' ],
						dest: '<%= dirs.lang %>',
						ext: '.mo',
						nonull: true,
					},
				],
			},
		},

		// Build translations without POEdit
		makepot: {
			target: {
				options: {
					mainFile: 'gravity-forms-business-hours.php',
					type: 'wp-plugin',
					domainPath: '/languages',
					updateTimestamp: false,
					exclude: [ 'node_modules/.*', 'assets/.*', 'tmp/.*', 'tests/.*' ],
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true,
					},
					processPot: function( pot, options ) {
						pot.headers.language = 'en_US';
						pot.headers[ 'language-team' ] = 'Katz Web Services, Inc. <support@katz.co>';
						pot.headers[ 'last-translator' ] = 'Katz Web Services, Inc. <support@katz.co>';
						pot.headers[ 'report-msgid-bugs-to' ] = 'https://gravityview.co/support/';

						var translation,
								excluded_meta = [
									'Gravity Forms Business Hours by GravityView',
									'Add a Business Hours field to your Gravity Forms form. Brought to you by <a href="https://gravityview.co">GravityView</a>, the best plugin for displaying Gravity Forms entries.',
									'https://wordpress.org/plugins/gravity-forms-business-hours/',
									'Katz Web Services, Inc.',
									'GravityView',
									'http://www.katzwebservices.com',
									'https://gravityview.co',
									'gv-calendar',
									'GPLv2 or later',
									'http://www.gnu.org/licenses/gpl-2.0.html',
								];

						for ( translation in pot.translations[ '' ] ) {
							if ( 'undefined' !== typeof pot.translations[ '' ][ translation ].comments.extracted ) {
								if ( excluded_meta.indexOf( pot.translations[ '' ][ translation ].msgid ) >= 0 ) {
									console.log( 'Excluded meta: ' + pot.translations[ '' ][ translation ].msgid );
									delete pot.translations[ '' ][ translation ];
								}
							}
						}

						return pot;
					},
				},
			},
		},

		// Add textdomain to all strings, and modify existing textdomains in included packages.
		addtextdomain: {
			options: {
				textdomain: 'gravity-forms-business-hours',    // Project text domain.
				updateDomains: [ 'gravityview', 'gravityforms', 'edd_sl', 'edd', 'easy-digital-downloads' ],  // List of text domains to replace.
			},
			target: {
				files: {
					src: [
						'*.php',
						'**/*.php',
						'!node_modules/**',
						'!tests/**',
						'!tmp/**',
					],
				},
			},
		},
	} );

	grunt.loadNpmTasks( 'grunt-contrib-less' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-exec' );
	grunt.loadNpmTasks( 'grunt-potomo' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );

	grunt.registerTask( 'default', [ 'less', 'uglify', 'wp_readme_to_markdown', 'watch' ] );
	grunt.registerTask( 'translate', [ 'exec:transifex', 'potomo', 'addtextdomain', 'makepot' ] );
};
