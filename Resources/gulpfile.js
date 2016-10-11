'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');
var uglify = require('gulp-uglify');

gulp.task('css', function () {
    return gulp.src('Private/CSS/**/*.scss').
        pipe(sass().on('error', sass.logError)).
        pipe(gulp.dest('Public/CSS'));
});

gulp.task('js', function() {
    return gulp.src('Private/JavaScript/**/*.js').
        pipe(uglify()).
        pipe(gulp.dest('Public/JavaScript'));
});

gulp.task('default', ['css', 'js'], function () {
    gulp.watch('Private/CSS/**/*.scss', ['css']);
    gulp.watch('Private/JavaScript/**/*.js', ['js']);
});
