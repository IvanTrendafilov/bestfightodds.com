var gulp = require('gulp'),
    rename = require('gulp-rename'),
    minifycss = require('gulp-minify-css'),
    jshint = require('gulp-jshint'),
    merge = require('gulp-merge'),
    uglify = require('gulp-uglify');


//CSS
var sass = require('gulp-sass');
var concat = require('gulp-concat');

gulp.task('styles', function() {
    gulp.src(['./app/front/css/bfo-main-sass.scss', './app/front/css/bfo-oddstable-sass.scss', './app/front/css/bfo-responsive-sass.scss'])
        .pipe(sass().on('error', sass.logError))
        .pipe(minifycss())
        .pipe(concat('bfo.min.css'))
        .pipe(gulp.dest('./app/front/css'));
});


//Javascript
var closureCompiler = require('gulp-closure-compiler');

gulp.task('scripts', function() {
  /*  return merge(
            gulp.src(['./app/front/js/lib/jquery-1.11.3.min.js', './app/front/js/lib/js.cookie.js', './app/front/js/lib/highcharts.js', './app/front/js/lib/highcharts-more.js', './app/front/js/lib/fastclick-min.js']),
            gulp.src('./app/front/js/bfo_main.js')
            .pipe(closureCompiler({
                compilerPath: './app/front/js/cc/compiler.jar',
                fileName: './app/front/js/bfo_main_gulp_cc.js',
                continueWithWarnings: true,
                compilerFlags: {
                    compilation_level: 'ADVANCED_OPTIMIZATIONS',
                    externs: [
                        './app/front/js/cc/extern-jquery-1.9.js',
                        './app/front/js/cc/extern-custom-bfo.js'
                    ],
                    // .call is super important, otherwise Closure Library will not work in strict mode. 
                    output_wrapper: '(function(){%output%}).call(window);',
                    warning_level: 'DEFAULT',
                }
            })),
            gulp.src('./app/front/js/bfo_charts.js')
            .pipe(closureCompiler({
                fileName: './app/front/js/bfo_charts_gulp_cc.js',
                compilerPath: './app/front/js/cc/compiler.jar',
                continueWithWarnings: true,
            }))
        )
        .pipe(concat('bfo.min.js'))
        .pipe(gulp.dest('./app/front/js/'));*/


        gulp.src(['./app/front/js/lib/jquery-1.11.3.min.js', './app/front/js/lib/js.cookie.js', './app/front/js/lib/highcharts.js', './app/front/js/lib/highcharts-more.js', './app/front/js/lib/fastclick-min.js'
            ,'./app/front/js/bfo_main.js','./app/front/js/bfo_charts.js'])
        .pipe(uglify({
            preserveComments: 'license'
        }))
        .pipe(concat('bfo.min.js'))
        .pipe(gulp.dest('./app/front/js/'));
});


/*gulp.task('scripts', function() {
  return gulp.src('./app/front/js/bfo_charts.js')
    .pipe(closureCompiler({
      compilerPath: './app/front/js/compiler.jar',
      fileName: 'bfo_main_gulp_cc.js',
      continueWithWarnings: 'true',
      compilerFlags: {
        compilation_level: 'ADVANCED_OPTIMIZATIONS',
        externs: [
          './app/front/js/extern-jquery-1.9.js',
          './app/front/js/extern-custom-bfo.js'
        ],
        // .call is super important, otherwise Closure Library will not work in strict mode. 
        output_wrapper: '(function(){%output%}).call(window);',
      }
    }))
    .pipe(gulp.dest('./app/front/js'));




});
*/


// JS hint task
gulp.task('jshint', function() {
    gulp.src('./app/front/js/bfo_*.js')
        .pipe(jshint())
        .pipe(jshint.reporter('default'));
});



//Watch
gulp.task('watch', function() {
    gulp.watch('./app/front/css/*.scss', ['styles']);

    gulp.watch(['./app/front/js/bfo_main.js', './app/front/js/bfo_charts.js'], ['scripts']);
});