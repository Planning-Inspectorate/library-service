'use strict';

const path = require('path');
const gulp = require('gulp');
const sassVariables = require('gulp-sass-variables');
const sass = require('gulp-sass')(require('sass'));
const sourcemaps = require('gulp-sourcemaps');

// Drupal theme directory
const themeDir = '.';

// Absolute path to the parent govuk_theme node_modules.
// govuk-frontend SCSS is resolved from here so the subtheme does not need its
// own govuk-frontend installation.
const parentNodeModules = path.resolve(__dirname, '../../contrib/govuk_theme/node_modules');

// Compile Sass
gulp.task('sass', function () {
  return gulp.src(`${themeDir}/sass/**/*.scss`)
    .pipe(sourcemaps.init())
    .pipe(sassVariables({
      '$govuk-suppressed-warnings': [
        'legacy-organisation-colours'
      ]
    }))
    .pipe(sass({
      loadPaths: [parentNodeModules],
      quietDeps: true,
      silenceDeprecations: ['import', 'legacy-js-api']
    }).on('error', sass.logError))
    .pipe(sourcemaps.write('../map/'))
    .pipe(gulp.dest(`${themeDir}/css`));
});

// Watch for Sass changes
gulp.task('watch', function () {
  gulp.watch(`${themeDir}/sass/**/*.scss`, gulp.series('sass'));
});

// Default and build tasks
gulp.task('build', gulp.series('sass'));
gulp.task('default', gulp.series('sass'));
