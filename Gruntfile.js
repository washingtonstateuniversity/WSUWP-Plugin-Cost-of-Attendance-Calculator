module.exports = function( grunt ) {
    grunt.initConfig( {
        pkg: grunt.file.readJSON( "package.json" ),

        phpcs: {
            plugin: {
                src: [ "./*.php", "./includes/*.php" ]
            },
            options: {
                bin: "vendor/bin/phpcs --extensions=php --ignore=\"*/vendor/*,*/node_modules/*\"",
                standard: "phpcs.ruleset.xml"
            }
        },

        jscs: {
            scripts: {
                src: [ "Gruntfile.js", "js/calculator.js" ],
                options: {
                    preset: "jquery",
                    maximumLineLength: 250
                }
            }
        },

        jshint: {
            gruntScript: {
                src: [ "Gruntfile.js" ],
                options: {
                    curly: true,
                    eqeqeq: true,
                    noarg: true,
                    quotmark: "double",
                    undef: true,
                    unused: false,
                    node: true // Define globals available when running in Node.
                }
            }
        },

        uglify: {
            scripts: {
                src: "js/calculator.js",
                dest: "js/calculator.min.js"
            }
        }
    } );

    grunt.loadNpmTasks( "grunt-jscs" );
    grunt.loadNpmTasks( "grunt-contrib-jshint" );
    grunt.loadNpmTasks( "grunt-contrib-uglify" );
    grunt.loadNpmTasks( "grunt-phpcs" );

    // Default task(s).
    grunt.registerTask( "default", [ "phpcs", "jscs", "jshint", "uglify" ] );
};
