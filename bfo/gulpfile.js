var gulp = require('gulp'),
    cleancss = require('gulp-clean-css'),
    uglify = require('gulp-uglify'),
    saveLicense = require('uglify-save-license');

//CSS optimizations and concactinations
var sass = require('gulp-sass');
var concat = require('gulp-concat');

gulp.task('styles', function() {
    gulp.src(['./app/front/css/bfo-darkmode.scss'])
    .pipe(sass().on('error', sass.logError))
    .pipe(cleancss())
    .pipe(concat('bfo.darkmode.css'))
    .pipe(gulp.dest('./app/front/css'));
    return gulp.src(['./app/front/css/bfo-main-sass.scss', './app/front/css/bfo-oddstable-sass.scss', './app/front/css/bfo-responsive-sass.scss'])
        .pipe(sass().on('error', sass.logError))
        .pipe(cleancss())
        .pipe(concat('bfo.min.css'))
        .pipe(gulp.dest('./app/front/css'));
});

//Javascript optimizations and concactinations
gulp.task('scripts', function() {
        /*return gulp.src(['./app/front/js/lib/jquery-1.11.3.min.js', './app/front/js/lib/js.cookie.js', './app/front/js/lib/highcharts.js', './app/front/js/lib/highcharts-more.js', './app/front/js/lib/fastclick-min.js'
            ,'./app/front/js/bfo_main.js','./app/front/js/bfo_charts.js'])*/
            return gulp.src(['./app/front/js/lib/jquery-3.5.1.min.js', './app/front/js/lib/js.cookie-3.min.js', './app/front/js/lib/highcharts4-final.js', './app/front/js/lib/highcharts4-more-final.js', './app/front/js/lib/fastclick-min.js'
            ,'./app/front/js/bfo_main.js','./app/front/js/bfo_charts.js'])
        .pipe(uglify({
            output: {
                comments: saveLicense
            }
        }))
        .pipe(concat('bfo.min.js'))
        .pipe(gulp.dest('./app/front/js/'));
});

//Defines watch task (continous check)
gulp.task('watch', function() {
    gulp.watch('./app/front/css/*.scss', gulp.series('styles'));
    gulp.watch(['./app/front/js/bfo_main.js', './app/front/js/bfo_charts.js'], gulp.series('scripts'));
  });