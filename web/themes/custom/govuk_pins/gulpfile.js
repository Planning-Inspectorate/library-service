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

// Copy govuk-frontend JS from the parent theme's node_modules.
// This keeps js/govuk-frontend.min.js in sync with the installed version
// so the inline window.GOVUKFrontend.initAll() call in html.html.twig works.
gulp.task('assets', function () {
  // all.bundle.js is the UMD/IIFE build that exposes window.GOVUKFrontend
  // for the inline initAll() call in html.html.twig.
  // govuk-frontend.min.js is an ES module and cannot be used as a plain script.
  return gulp.src([
    `${parentNodeModules}/govuk-frontend/dist/govuk/all.bundle.js`,
    `${parentNodeModules}/govuk-frontend/dist/govuk/all.bundle.js.map`
  ], { encoding: false })
    .pipe(gulp.dest(`${themeDir}/js`));
});

// Watch for Sass changes
gulp.task('watch', function () {
  gulp.watch(`${themeDir}/sass/**/*.scss`, gulp.series('sass'));
});

// Default and build tasks
gulp.task('build', gulp.series('sass', 'assets'));
gulp.task('default', gulp.series('sass', 'assets'));
