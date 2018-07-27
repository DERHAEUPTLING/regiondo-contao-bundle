'use strict';

const gulp = require('gulp');
const uglify = require('gulp-uglify');
const rename = require('gulp-rename');

gulp.task('default', function () {
    return gulp.src(['src/Resources/public/*.js', '!src/Resources/public/*.min.js'])
        .pipe(uglify())
        .pipe(rename(function(path) {
            path.extname = '.min' + path.extname;
        }))
        .pipe(gulp.dest('src/Resources/public'));
});
