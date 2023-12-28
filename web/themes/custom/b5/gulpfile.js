'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass')(require('sass'));
var sourcemaps = require('gulp-sourcemaps');


// Transform SASS in CSS and minify it.
gulp.task('sass', async function () {
  gulp.src('scss/**/*.scss')
    .pipe(sourcemaps.init())
    .pipe(sass({ outputStyle: 'expanded' }).on('error', sass.logError))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('css'));
});


gulp.task('watch', function () {
  gulp.watch('./scss/**/*.scss', gulp.series(['sass']));
});


// // Tarea por defecto
// gulp.task('default', ['watch']);
