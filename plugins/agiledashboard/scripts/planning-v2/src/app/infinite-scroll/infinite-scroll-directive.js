/**
 * The MIT License
 *
 * Copyright (c) 2013 Brian Matthews
 * Copyright (c) BrownBear 2015 - Present
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

import angular from "angular";

export default infiniteScroll;

infiniteScroll.$inject = ["$window"];

function infiniteScroll($window) {
    return {
        restrict: "A",
        link: link,
    };

    function link(scope, element, attrs) {
        var threshold = parseInt(attrs.scrollThreshold, 10) || 0,
            callback = attrs.scrollCallback;

        angular.element($window).bind("scroll", function () {
            if (element[0].getBoundingClientRect().bottom < $window.innerHeight + threshold) {
                scope.$apply(callback);
            }
        });
    }
}
