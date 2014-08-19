module.exports = function(grunt) {
    grunt.loadNpmTasks('grunt-contrib-symlink');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-coffee');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-commands');

    var filesLess = {},
        filesCoffee = {};

    // Configuration
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        symlink: {
            fontawesome_font_otf: {
                src: 'bower_components/font-awesome/fonts/FontAwesome.otf',
                dest: 'web/fonts/FontAwesome.otf'
            },
            fontawesome_font_eot: {
                src: 'bower_components/font-awesome/fonts/fontawesome-webfont.eot',
                dest: 'web/fonts/fontawesome-webfont.eot'
            },
            fontawesome_font_svg: {
                src: 'bower_components/font-awesome/fonts/fontawesome-webfont.svg',
                dest: 'web/fonts/fontawesome-webfont.svg'
            },
            fontawesome_font_ttf: {
                src: 'bower_components/font-awesome/fonts/fontawesome-webfont.ttf',
                dest: 'web/fonts/fontawesome-webfont.ttf'
            },
            fontawesome_font_woff: {
                src: 'bower_components/font-awesome/fonts/fontawesome-webfont.woff',
                dest: 'web/fonts/fontawesome-webfont.woff'
            },
            bootstrap_font_eot: {
                src: 'bower_components/bootstrap/dist/fonts/glyphicons-halflings-regular.eot',
                dest: 'web/fonts/glyphicons-halflings-regular.eot'
            },
            bootstrap_font_svg: {
                src: 'bower_components/bootstrap/dist/fonts/glyphicons-halflings-regular.svg',
                dest: 'web/fonts/glyphicons-halflings-regular.svg'
            },
            bootstrap_font_ttf: {
                src: 'bower_components/bootstrap/dist/fonts/glyphicons-halflings-regular.ttf',
                dest: 'web/fonts/glyphicons-halflings-regular.ttf'
            },
            bootstrap_font_woff: {
                src: 'bower_components/bootstrap/dist/fonts/glyphicons-halflings-regular.woff',
                dest: 'web/fonts/glyphicons-halflings-regular.woff'
            }
        },

        clean: {
            built: ["web/built"]
        },

        less: {
            bundles: {
                files: filesLess
            }
        },

        cssmin: {
            minify: {
                expand: true,
                src: ['web/built/all.css'],
                ext: '.min.css'
            }
        },

        coffee: {
            compileBare: {
                options: {
                    bare: true
                },
                files: filesCoffee
            }
        },

        uglify: {
            dist: {
                files: {
                    'web/built/all.min.js': ['web/built/all.js']
                }
            }
        },

        concat: {
            bowerjs: {
                src: [
                    'bower_components/jquery/dist/jquery.js',
                    'bower_components/jquery-ui/ui/jquery-ui.js',
                    'bower_components/bootstrap/dist/js/bootstrap.js',
                    'bower_components/angular/angular.js',
                    'bower_components/underscore/underscore.js',
                    'bower_components/backbone/backbone.js'
                ],
                dest: 'web/built/bower.js'
            },
            js: {
                src: [
                    'web/built/bower.js',
                    'web/bundles/*/js/*.js',
                    'web/bundles/*/js/*/*.js',
                    'web/built/*/js/*.js',
                    'web/built/*/js/*/*.js'
                ],
                dest: 'web/built/all.js'
            },
            bowercss: {
                src: [
                    'bower_components/bootstrap/dist/css/bootstrap.css',
                    'bower_components/jquery-ui/themes/base/jquery-ui.css',
                    'bower_components/font-awesome/css/font-awesome.css'
                ],
                dest: 'web/built/bower.css'
            },
            css: {
                src: [
                    'web/built/bower.css',
                    'web/bundles/*/css/*.css',
                    'web/bundles/*/css/*/*.css',
                    'web/bundles/*/css/*/*/*.css',
                    'web/built/*/css/**.css'
                ],
                dest: 'web/built/all.css'
            }
        },

        watch: {
            css: {
                files: ['web/bundles/*/less/*.less'],
                tasks: ['css', 'command:assetic_dump']
            },
            javascript: {
                files: ['web/bundles/*/coffee/*.coffee'],
                tasks: ['javascript', 'command:assetic_dump']
            }
        },

        command : {
            assets_install: {
                cmd  : 'php app/console assets:install --symlink'
            },
            assetic_dump: {
                cmd  : 'php app/console assetic:dump'
            }
        }
    });

    // Default task(s).
    grunt.registerTask('default', ['clean', 'command:assets_install', 'symlink', 'concat:bowercss', 'css', 'concat:bowerjs', 'javascript', 'command:assetic_dump']);
    grunt.registerTask('css', ['less:discovering', 'less', 'concat:css', 'cssmin']);
    grunt.registerTask('javascript', ['coffee:discovering', 'coffee', 'concat:js', 'uglify']);
    grunt.registerTask('less:discovering', 'This is a function', function() {
        // LESS Files management
        // Source LESS files are located inside : bundles/[bundle]/less/
        // Destination CSS files are located inside : built/[bundle]/css/
        var mappingFileLess = grunt.file.expandMapping(
            ['*/less/*.less', '*/less/*/*.less'],
            'web/built/', {
                cwd: 'web/bundles/',
                rename: function(dest, matchedSrcPath, options) {
                    return dest + matchedSrcPath.replace(/less/g, 'css');
                }
            });

        grunt.util._.each(mappingFileLess, function(value) {
            // Why value.src is an array ??
            filesLess[value.dest] = value.src[0];
        });
    });
    grunt.registerTask('coffee:discovering', 'This is a function', function() {
        // COFFEE Files management
        // Source COFFEE files are located inside : bundles/[bundle]/coffee/
        // Destination JS files are located inside : built/[bundle]/js/
        var mappingFileCoffee = grunt.file.expandMapping(
            ['*/coffee/*.coffee', '*/coffee/*/*.coffee'],
            'web/built/', {
                cwd: 'web/bundles/',
                rename: function(dest, matchedSrcPath, options) {
                    return dest + matchedSrcPath.replace(/coffee/g, 'js');
                }
            });

        grunt.util._.each(mappingFileCoffee, function(value) {
            // Why value.src is an array ??
            filesCoffee[value.dest] = value.src[0];
        });
    });
};