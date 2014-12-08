module.exports = function(grunt) {

    var dest = grunt.option("target");
    if ( ! dest ) {
        grunt.fatal("Build target path is not specified.");
    }

    grunt.loadNpmTasks("grunt-sprockets");
    grunt.loadNpmTasks("grunt-contrib-uglify");
    grunt.loadNpmTasks("grunt-sass");
    grunt.loadNpmTasks("grunt-contrib-cssmin");

    grunt.initConfig({
        paths: {
            src: "src",
            dest: dest
        },

        sprockets: {
            options: {
                allowDuplicateRequire: false
            },
            build: {
                files: ["<%= paths.src %>/js/main.js"],
                dest: "<%= paths.dest %>/main.js"
            }
        },

        sass: {
            compile: {
                files: {
                    "<%= paths.dest %>/main.css": "<%= paths.src %>/scss/main.scss"
                }
            }
        },

        cssmin: {
            minify: {
                files: {
                    "<%= paths.dest %>/main.min.css": "<%= paths.dest %>/main.css"
                }
            }
        },

        uglify: {
            build: {
                files: {
                    "<%= paths.dest %>/main.min.js": "<%= paths.dest %>/main.js"
                }
            }
        }
    });

    grunt.registerTask("js", ["sprockets", "uglify"]);
    grunt.registerTask("css", ["sass", "cssmin"]);
    grunt.registerTask("default", ["js", "css"]);

};
