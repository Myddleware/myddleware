/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  St�phane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  St�phane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com	
 
 This file is part of Myddleware.
 
 Myddleware is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Myddleware is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
*********************************************************************************/

$(function () {

    var is,
        transition;

    // from valentine
    is = {
        fun: function (f) {
            return typeof f === 'function';
        },
        arr: function (ar) {
            return ar instanceof Array;
        },
        obj: function (o) {
            return o instanceof Object && !is.fun(o) && !is.arr(o);
        }
    };

    /*
        Based on Bootstrap
        Mozilla and Webkit support only
    */
    transition = (function () {
        var st = document.createElement('div').style,
            transitionEnd = 'TransitionEnd',
            transitionProp = 'Transition',
            support = st.transition !== undefined ||
                st.WebkitTransition !== undefined ||
                st.MozTransition !== undefined;

        return support && {
            prop: (function () {
                if (st.WebkitTransition !== undefined) {
                    transitionProp = 'WebkitTransition';
                } else if (st.MozTransition !== undefined) {
                    transitionProp = 'MozTransition';
                }
                return transitionProp;
            }()),
            end: (function () {
                if (st.WebkitTransition !== undefined) {
                    transitionEnd = 'webkitTransitionEnd';
                } else if (st.MozTransition !== undefined) {
                    transitionEnd = 'transitionend';
                }
                return transitionEnd;
            }())
        };
    }());

    function extend() {
        // based on jQuery deep merge
        var options, name, src, copy, clone,
            target = arguments[0], i = 1, length = arguments.length;

        for (; i < length; i += 1) {
            if ((options = arguments[i]) !== null) {
                // Extend the base object
                for (name in options) {
                    src = target[name];
                    copy = options[name];
                    if (target === copy) {
                        continue;
                    }
                    if (copy && (is.obj(copy))) {
                        clone = src && is.obj(src) ? src : {};
                        target[name] = extend(clone, copy);
                    } else if (copy !== undefined) {
                        target[name] = copy;
                    }
                }
            }
        }
        return target;
    }

    function clone(obj) {
        if (null === obj || 'object' !== typeof obj) {
            return obj;
        }
        var copy = obj.constructor(),
            attr;
        for (attr in obj) {
            if (obj.hasOwnProperty(attr)) {
                copy[attr] = obj[attr];
            }
        }
        return copy;
    }

    // from jquery
    function proxy(fn, context) {
        var slice = Array.prototype.slice,
            args = slice.call(arguments, 2);
        return function () {
            return fn.apply(context, args.concat(slice.call(arguments)));
        };
    }

    function animate(options) {
        var el = options.el,
            complete = options.complete ? options.complete : function () {},
            animation,
            dummy;

        // no animation obj OR animation is not available,
        // fallback to css and call the callback
        if (! options.animation ||
            ! (el.animate || (options.css3transition && transition))) {
            el.css(options.fallbackCss);
            complete();
            return;
        }

        // we will animate, apply start CSS
        if (options.animStartCss) {
            if (options.animStartCss.opacity === 0) {
                options.animStartCss.opacity = 0.01; // ie quirk
            }
            el.css(options.animStartCss);
        }
        animation = options.animation;

        // css3 setted, if available apply the css
        if (options.css3transition && transition) {
            dummy = el[0].offsetWidth; // force reflow; source: bootstrap
            el[0].style[transition.prop] = 'all ' + animation.duration + 'ms';

            // takaritas
            delete animation.duration;
            delete animation.easing;

            el.css(animation);
            //el.unbind(transition.end);
            el.on(transition.end, function () {
                // delete transition properties and events
                el.off(transition.end);
                el[0].style[transition.prop] = 'none';
                complete();
            });
        } else if (window.ender) {
            // use morpheus
            el.animate(extend(animation, {'complete': complete}));
        } else {
            // use animate from jquery
            el.animate(animation, animation.duration, animation.easing, complete);
        }
    }

    /*
        Carousel Constructor
    */
    function Carousel(el, options) {
        this.init(el, options);

        // only return the API
        // instead of this
        return {
            getPageSize: proxy(this.getPageSize, this),
            getCursor: proxy(this.getCursor, this),
            nextPage: proxy(this.nextPage, this),
            prevPage: proxy(this.prevPage, this),
            isVisibleItem: proxy(this.isVisibleItem, this),
            scrollToItem: proxy(this.scrollToItem, this),
            getOptions: proxy(this.getOptions, this),
            setOptions: proxy(this.setOptions, this)
        };
    }

    Carousel.prototype = {
        init: function (el, options) {
            var opt;

            this.options = {
                window: '.window',
                items: 'li',
                pager: null,
                nextPager: 'a.next',
                prevPager: 'a.prev',
                activeClass: null,
                disabledClass: 'disabled',
                duration: 400,
                vertical: false,
                keyboard: false,
                css3transition: false,
                extraOffset: 0
            };
            this.setOptions(options);
            opt = this.options;

            if (opt.css3transition && ! transition) {
                opt.css3transition = false;
            }

            this.$el = $(el);
            this.$window = this.$el.find(
                opt.window
            );
            this.$itemWrapper = this.$window.children().first();
            this.$items = this.$el.find(
                opt.items
            );
            this.$nextPager = this.$el.find(
                opt.nextPager
            );
            this.$prevPager = this.$el.find(
                opt.prevPager
            );

            this.setDimensions();

            if (opt.pager) {
                this.$pager = this.$el.find(
                    opt.pager
                );

                this.createPager();

                this.$pagerItems = this.$pager.find('li');
            }

            if (this.$items.length <= this.pageSize) {
                this.hidePrevPager();
                this.hideNextPager();
                return;
            }

            this.cursor = this.getActiveIndex();

            if (this.cursor < 0) {
                if (this.options.activeClass) {
                    for (var i = 0; i < this.pageSize; i += 1) {
                        $(this.$items.get(i)).addClass('active');
                    }
                }

                this.cursor = 0;
            }

            if (this.cursor > this.lastPosition) {
                this.cursor = this.lastPosition;
            }

            if (this.cursor > 0) {
                this.scrollToItem(this.cursor, true);
            }

            if (this.cursor === 0) {
                this.hidePrevPager();
            }

            if (this.cursor >= this.lastPosition) {
                this.hideNextPager();
            }

            this.$nextPager.on('click', proxy(this.nextPage, this));
            this.$prevPager.on('click', proxy(this.prevPage, this));

            if (opt.keyboard) {
                $(document).on('keyup', proxy(this.onKeyUp, this));
            }

            this.$el.addClass('carousel-inited');
        },

        setDimensions: function () {
            var $secondItem,
                alignedDimension = 'width',
                marginType = ['margin-left', 'margin-right'];

            if (this.options.vertical) {
                alignedDimension = 'height';
                marginType = ['margin-top', 'margin-bottom'];
            }

            $secondItem = this.$items.first().next();
            this.itemMargin = parseInt($secondItem.css(marginType[0]), 10) +
                parseInt($secondItem.css(marginType[1]), 10);
            this.itemDimension = $secondItem[alignedDimension]() + this.itemMargin;

            this.windowDimension = this.$window[alignedDimension]();
            this.pageSize = Math.floor(
                (this.windowDimension + this.itemMargin) / this.itemDimension
            );
            this.pageDimension = this.pageSize * this.itemDimension;
            this.lastPosition = this.$items.length - this.pageSize;

        },

        createPager: function () {
            var itemsLen = this.$items.length,
                pagerItemsFrag = document.createDocumentFragment(),
                pagerItem,
                i;

            for (i = 0; i < itemsLen; i += 1) {
                pagerItem = document.createElement('li');
                $pagerItem = $(pagerItem);

                $pagerItem.on('click', proxy(this.usePager, this, i, itemsLen));

                if (i < this.pageSize) {
                    $pagerItem.addClass('active');
                }

                pagerItemsFrag.appendChild(pagerItem);
            }

            this.$pager.empty().get(0).appendChild(pagerItemsFrag);
        },

        usePager: function (pos, len) {
            if (pos > (len - this.pageSize)) {
                this.scrollToItem(len - this.pageSize);
            } else {
                this.scrollToItem(pos);
            }
        },

        nextPage: function (e) {
            if (typeof(e) !== 'undefined') {
                e.preventDefault();
            }

            if (this.cursor >= this.lastPosition) {
                return;
            }

            var itemIdx = this.cursor + this.pageSize;
            if (itemIdx > this.lastPosition) {
                itemIdx = this.lastPosition;
            }

            this.scrollToItem(itemIdx);
        },

        prevPage: function (e) {
            if (typeof(e) !== 'undefined') {
                e.preventDefault();
            }

            if (this.cursor === 0) {
                return;
            }

            var itemIdx = this.cursor - this.pageSize;
            if (itemIdx < 0) {
                itemIdx = 0;
            }

            this.scrollToItem(itemIdx);
        },

        nextItem: function () {
            if (this.cursor >= this.lastPosition) {
                return;
            }

            this.scrollToItem(this.cursor + 1);
        },

        prevItem: function () {
            if (this.cursor === 0) {
                return;
            }
            this.scrollToItem(this.cursor - 1);
        },

        scrollToItem: function (idx, doNotAnimate) {
            var animateTo,
                scrollTo,
                direction = this.options.vertical ? 'top' : 'left',
                animObj = {},
                activeClassName = this.options.activeClass || 'active',
                itemsLen = this.$items.length,
                i;

            this.cursorPrevious = this.cursor;
            this.cursor = idx;

            if (this.cursor === 0) {
                this.hidePrevPager();
            } else {
                this.showPrevPager();
            }

            if (this.cursor >= this.lastPosition) {
                this.hideNextPager();
            } else {
                this.showNextPager();
            }

            scrollTo = this.cursor * this.itemDimension;
            if (this.cursor === this.lastPosition) {
                scrollTo = scrollTo -
                    (this.windowDimension - this.pageDimension + this.itemMargin) +
                    this.options.extraOffset;
            }

            scrollTo *= -1;
            animObj[direction] = scrollTo;

            if (! doNotAnimate) {
                animObj.duration = this.options.duration;
            }

            if (this.options.activeClass) {
                activeClass = this.options.activeClass;

                if (this.getPageSize() === 1) {
                    $(this.$items.get(this.cursorPrevious)).removeClass(activeClass);
                    $(this.$items.get(idx)).addClass(activeClass);
                } else {
                    itemslen = this.$items.length;
                    this.$items.removeClass(activeClass);

                    for (i = 0; i < itemslen; i += 1) {
                        if (this.isVisibleItem(i)) {
                            $(this.$items.get(i)).addClass(activeClass);
                        }
                    }
                }
            }

            if (this.options.pager) {
                if (this.getPageSize() === 1) {
                    $(this.$pagerItems.get(this.cursorPrevious)).removeClass(activeClassName);
                    $(this.$pagerItems.get(this.cursor)).addClass(activeClassName);
                } else {
                    this.$pagerItems.removeClass(activeClassName);

                    for (i = 0; i < itemsLen; i += 1) {
                        if (this.isVisibleItem(i)) {
                            $(this.$pagerItems.get(i)).addClass(activeClassName);
                        }
                    }
                }
            }

            animate({
                el: this.$itemWrapper,
                animation: doNotAnimate ? false : animObj,
                fallbackCss: animObj,
                css3transition: this.options.css3transition
            });
        },

        onKeyUp: function (e) {
            if (e.keyCode === 39) {
                this.nextPage();
            } else if (e.keyCode === 37) {
                this.prevPage();
            }
        },

        getActiveIndex: function () {
            var i = 0,
                il = this.$items.length;

            for (; i < il; i += 1) {
                if ($(this.$items.get(i)).hasClass('active')) {
                    return i;
                }
            }

            return -1;
        },

        hideNextPager: function () {
            this.$nextPager.addClass(
                this.options.disabledClass
            );
        },

        hidePrevPager: function () {
            this.$prevPager.addClass(
                this.options.disabledClass
            );
        },

        showNextPager: function () {
            this.$nextPager.removeClass(
                this.options.disabledClass
            );
        },

        showPrevPager: function () {
            this.$prevPager.removeClass(
                this.options.disabledClass
            );
        },

        getPageSize: function () {
            return this.pageSize;
        },

        getCursor: function () {
            return this.cursor;
        },

        isVisibleItem: function (idx) {
            if (this.cursor + this.pageSize <= idx || this.cursor > idx) {
                return false;
            }
            return true;
        },

        getOptions: function () {
            return this.options;
        },

        setOptions: function (options) {
            extend(this.options, options || {});
        }
    };

    // changed the name of function as otherwise if it's called carousel it conflicts with Bootstrap carousel
    $.fn.carousel = function (options) {
        return new Carousel(this.first(), options);
    };
});

window.onload = function () {

    $("#animation-cleft").carousel({
        vertical: true,
        duration: 1000
    });
    $("#animation-cright").carousel({
        vertical: true,
        duration: 1000
    });
};