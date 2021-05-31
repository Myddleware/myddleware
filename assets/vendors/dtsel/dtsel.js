(function () {
    "use strict";

    var BODYTYPES = ["DAYS", "MONTHS", "YEARS"];
    var MONTHS = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];
    var WEEKDAYS = [
        "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
    ];

    /** @typedef {Object.<string, Function[]>} Handlers */
    /** @typedef {function(String, Function): null} AddHandler */
    /** @typedef {("DAYS"|"MONTHS"|"YEARS")} BodyType */
    /** @typedef {string|number} StringNum */
    /** @typedef {Object.<string, StringNum>} StringNumObj */

    /**
     * The local state
     * @typedef {Object} InstanceState
     * @property {Date} value
     * @property {Number} year
     * @property {Number} month
     * @property {Number} day
     * @property {Number} time
     * @property {Number} hours
     * @property {Number} minutes
     * @property {Number} seconds
     * @property {BodyType} bodyType
     * @property {Boolean} visible
     * @property {Number} cancelBlur
     */

    /** 
     * @typedef {Object} Config
     * @property {String} dateFormat
     * @property {String} timeFormat
     * @property {Boolean} showDate
     * @property {Boolean} showTime
     * @property {Number} paddingX
     * @property {Number} paddingY
     * @property {BodyType} defaultView
     * @property {"TOP"|"BOTTOM"} direction
    */

    /**
     * @class
     * @param {HTMLElement} elem 
     * @param {Config} config 
     */
    function DTS(elem, config) {
        var config = config || {};

        /** @type {Config} */
        var defaultConfig = {
            defaultView: BODYTYPES[0],
            dateFormat: "yyyy-mm-dd",
            timeFormat: "HH:MM:SS",
            showDate: true,
            showTime: false,
            paddingX: 5,
            paddingY: 5,
            direction: 'TOP'
        }

        if (!elem) {
            throw TypeError("input element or selector required for contructor");
        }
        if (Object.getPrototypeOf(elem) === String.prototype) {
            var _elem = document.querySelectorAll(elem);
            if (!_elem[0]){
                throw Error('"' + elem + '" not found.');
            }
            elem = _elem[0];
        }
        this.config = setDefaults(config, defaultConfig);
        this.dateFormat = this.config.dateFormat;
        this.timeFormat = this.config.timeFormat;
        this.dateFormatRegEx = new RegExp("yyyy|yy|mm|dd", "gi");
        this.timeFormatRegEx = new RegExp("hh|mm|ss|a", "gi");
        this.inputElem = elem;
        this.dtbox = null;
        this.setup();
    }
    DTS.prototype.setup = function () {
        var handler = this.inputElemHandler.bind(this);
        this.inputElem.addEventListener("focus", handler, false)
        this.inputElem.addEventListener("blur", handler, false);
    }
    DTS.prototype.inputElemHandler = function (e) {
        if (e.type == "focus") {
            if (!this.dtbox) {
                this.dtbox = new DTBox(e.target, this);
            }
            this.dtbox.visible = true;
        } else if (e.type == "blur" && this.dtbox && this.dtbox.visible) {
            var self = this;
            setTimeout(function () {
                if (self.dtbox.cancelBlur > 0) {
                    self.dtbox.cancelBlur -= 1;
                 } else {
                    self.dtbox.visible = false;
                    self.inputElem.blur();
                 }
            }, 100);
        }
    }
    /**
     * @class
     * @param {HTMLElement} elem 
     * @param {DTS} settings 
     */
    function DTBox(elem, settings) {
        /** @type {DTBox} */
        var self = this;

        /** @type {Handlers} */
        var handlers = {};

        /** @type {InstanceState} */
        var localState = {};

        /**
         * @param {String} key 
         * @param {*} default_val 
         */
        function getterSetter(key, default_val) {
            return {
                get: function () {
                    var val = localState[key];
                    return val === undefined ? default_val : val;
                },
                set: function (val) {
                    var prevState = self.state;
                    var _handlers = handlers[key] || [];
                    localState[key] = val;
                    for (var i = 0; i < _handlers.length; i++) {
                        _handlers[i].bind(self)(localState, prevState);
                    }
                },
            };
        };

        /** @type {AddHandler} */
        function addHandler(key, handlerFn) {
            if (!key || !handlerFn) {
                return false;
            }
            if (!handlers[key]) {
                handlers[key] = [];
            }
            handlers[key].push(handlerFn);
        }

        Object.defineProperties(this, {
            visible: getterSetter("visible", false),
            bodyType: getterSetter("bodyType", settings.config.defaultView),
            value: getterSetter("value"),
            year: getterSetter("year", 0),
            month: getterSetter("month", 0),
            day: getterSetter("day", 0),
            hours: getterSetter("hours", 0),
            minutes: getterSetter("minutes", 0),
            seconds: getterSetter("seconds", 0),
            cancelBlur: getterSetter("cancelBlur", 0),
            addHandler: {value: addHandler},
            month_long: {
                get: function () {
                    return MONTHS[self.month];
                },
            },
            month_short: {
                get: function () {
                    return self.month_long.slice(0, 3);
                },
            },
            state: {
                get: function () {
                    return Object.assign({}, localState);
                },
            },
            time: {
                get: function() {
                    var hours = self.hours * 60 * 60 * 1000;
                    var minutes = self.minutes * 60 * 1000;
                    var seconds = self.seconds * 1000;
                    return  hours + minutes + seconds;
                }
            },
        });
        this.el = {};
        this.settings = settings;
        this.elem = elem;
        this.setup();
    }
    DTBox.prototype.setup = function () {
        Object.defineProperties(this.el, {
            wrapper: { value: null, configurable: true },
            header: { value: null, configurable: true },
            body: { value: null, configurable: true },
            footer: { value: null, configurable: true }
        });
        this.setupWrapper();
        if (this.settings.config.showDate) {
            this.setupHeader();
            this.setupBody();
        }
        if (this.settings.config.showTime) {
            this.setupFooter();
        }

        var self = this;
        this.addHandler("visible", function (state, prevState) {
            if (state.visible && !prevState.visible){
                document.body.appendChild(this.el.wrapper);

                var parts = self.elem.value.split(/\s*,\s*/);
                var startDate = undefined;
                var startTime = 0;
                if (self.settings.config.showDate) {
                    startDate = parseDate(parts[0], self.settings);
                }
                if (self.settings.config.showTime) {
                    startTime = parseTime(parts[parts.length-1], self.settings);
                    startTime = startTime || 0;
                }
                if (!(startDate && startDate.getTime())) {
                    startDate = new Date();
                    startDate = new Date(
                        startDate.getFullYear(),
                        startDate.getMonth(),
                        startDate.getDate()
                    );
                }
                var value = new Date(startDate.getTime() + startTime);
                self.value = value;
                self.year = value.getFullYear();
                self.month = value.getMonth();
                self.day = value.getDate();
                self.hours = value.getHours();
                self.minutes = value.getMinutes();
                self.seconds = value.getSeconds();

                if (self.settings.config.showDate) {
                    self.setHeaderContent();
                    self.setBodyContent();
                }
                if (self.settings.config.showTime) {
                    self.setFooterContent();
                }
            } else if (!state.visible && prevState.visible) {
                document.body.removeChild(this.el.wrapper);
            }
        });
    }
    DTBox.prototype.setupWrapper = function () {
        if (!this.el.wrapper) {
            var el = document.createElement("div");
            el.classList.add("date-selector-wrapper");
            Object.defineProperty(this.el, "wrapper", { value: el });
        }
        var self = this;
        var htmlRoot = document.getElementsByTagName('html')[0];
        function setPosition(e){
            var minTopSpace = 300;
            var box = getOffset(self.elem);
            var config = self.settings.config;
            var paddingY = config.paddingY || 5;
            var paddingX = config.paddingX || 5;
            var top = box.top + self.elem.offsetHeight + paddingY;
            var left = box.left + paddingX;
            var bottom = htmlRoot.clientHeight - box.top + paddingY;

            self.el.wrapper.style.left = `${left}px`;
            if (box.top > minTopSpace && config.direction != 'BOTTOM') {
                self.el.wrapper.style.bottom = `${bottom}px`;
                self.el.wrapper.style.top = '';
            } else {
                self.el.wrapper.style.top = `${top}px`;
                self.el.wrapper.style.bottom = ''; 
            }
        }

        function handler(e) {
            self.cancelBlur += 1;
            setTimeout(function(){
                self.elem.focus();
            }, 50);
        }
        setPosition();
        this.setPosition = setPosition;
        this.el.wrapper.addEventListener("mousedown", handler, false);
        this.el.wrapper.addEventListener("touchstart", handler, false);
        window.addEventListener('resize', this.setPosition);
    }
    DTBox.prototype.setupHeader = function () {
        if (!this.el.header) {
            var row = document.createElement("div");
            var classes = ["cal-nav-prev", "cal-nav-current", "cal-nav-next"];
            row.classList.add("cal-header");
            for (var i = 0; i < 3; i++) {
                var cell = document.createElement("div");
                cell.classList.add("cal-nav", classes[i]);
                cell.onclick = this.onHeaderChange.bind(this);
                row.appendChild(cell);
            }
            row.children[0].innerHTML = "&lt;";
            row.children[2].innerHTML = "&gt;";
            Object.defineProperty(this.el, "header", { value: row });
            tryAppendChild(row, this.el.wrapper);
        }
        this.setHeaderContent();
    }
    DTBox.prototype.setHeaderContent = function () {
        var content = this.year;
        if ("DAYS" == this.bodyType) {
            content = this.month_long + " " + content;
        } else if ("YEARS" == this.bodyType) {
            var start = this.year + 10 - (this.year % 10);
            content = start - 10 + "-" + (start - 1);
        }
        this.el.header.children[1].innerText = content;
    }
    DTBox.prototype.setupBody = function () {
        if (!this.el.body) {
            var el = document.createElement("div");
            el.classList.add("cal-body");
            Object.defineProperty(this.el, "body", { value: el });
            tryAppendChild(el, this.el.wrapper);
        }
        var toAppend = null;
        function makeGrid(rows, cols, className, firstRowClass, clickHandler) {
            var grid = document.createElement("div");
            grid.classList.add(className);
            for (var i = 1; i < rows + 1; i++) {
                var row = document.createElement("div");
                row.classList.add("cal-row", "cal-row-" + i);
                if (i == 1 && firstRowClass) {
                    row.classList.add(firstRowClass);
                }
                for (var j = 1; j < cols + 1; j++) {
                    var col = document.createElement("div");
                    col.classList.add("cal-cell", "cal-col-" + j);
                    col.onclick = clickHandler;
                    row.appendChild(col);
                }
                grid.appendChild(row);
            }
            return grid;
        }
        if ("DAYS" == this.bodyType) {
            toAppend = this.el.body.calDays;
            if (!toAppend) {
                toAppend = makeGrid(7, 7, "cal-days", "cal-day-names", this.onDateSelected.bind(this));
                for (var i = 0; i < 7; i++) {
                    var cell = toAppend.children[0].children[i];
                    cell.innerText = WEEKDAYS[i].slice(0, 2);
                    cell.onclick = null;
                }
                this.el.body.calDays = toAppend;
            }
        } else if ("MONTHS" == this.bodyType) {
            toAppend = this.el.body.calMonths;
            if (!toAppend) {
                toAppend = makeGrid(3, 4, "cal-months", null, this.onMonthSelected.bind(this));
                for (var i = 0; i < 3; i++) {
                    for (var j = 0; j < 4; j++) {
                        var monthShort = MONTHS[4 * i + j].slice(0, 3);
                        toAppend.children[i].children[j].innerText = monthShort;
                    }
                }
                this.el.body.calMonths = toAppend;
            }
        } else if ("YEARS" == this.bodyType) {
            toAppend = this.el.body.calYears;
            if (!toAppend) {
                toAppend = makeGrid(3, 4, "cal-years", null, this.onYearSelected.bind(this));
                this.el.body.calYears = toAppend;
            }
        }
        empty(this.el.body);
        tryAppendChild(toAppend, this.el.body);
        this.setBodyContent();
    }
    DTBox.prototype.setBodyContent = function () {
        var grid = this.el.body.children[0];
        var classes = ["cal-cell-prev", "cal-cell-next", "cal-value"];
        if ("DAYS" == this.bodyType) {
            var oneDayMilliSecs = 24 * 60 * 60 * 1000;
            var start = new Date(this.year, this.month, 1);
            var adjusted = new Date(start.getTime() - oneDayMilliSecs * start.getDay());

            grid.children[6].style.display = "";
            for (var i = 1; i < 7; i++) {
                for (var j = 0; j < 7; j++) {
                    var cell = grid.children[i].children[j];
                    var month = adjusted.getMonth();
                    var date = adjusted.getDate();
                    
                    cell.innerText = date;
                    cell.classList.remove(classes[0], classes[1], classes[2]);
                    if (month != this.month) {
                        if (i == 6 && j == 0) {
                            grid.children[6].style.display = "none";
                            break;
                        }
                        cell.classList.add(month < this.month ? classes[0] : classes[1]);
                    } else if (isEqualDate(adjusted, this.value)){
                        cell.classList.add(classes[2]);
                    }
                    adjusted = new Date(adjusted.getTime() + oneDayMilliSecs);
                }
            }
        } else if ("YEARS" == this.bodyType) {
            var year = this.year - (this.year % 10) - 1;
            for (i = 0; i < 3; i++) {
                for (j = 0; j < 4; j++) {
                    grid.children[i].children[j].innerText = year;
                    year += 1;
                }
            }
            grid.children[0].children[0].classList.add(classes[0]);
            grid.children[2].children[3].classList.add(classes[1]);
        }
    }

    /** @param {Event} e */
    DTBox.prototype.onTimeChange = function(e) {
        e.stopPropagation();
        if (e.type == 'mousedown') {
            this.cancelBlur += 1;
            return;
        }
        
        var el = e.target;
        this[el.name] = parseInt(el.value) || 0;
        this.setupFooter();
        if (e.type == 'change') {
            var self = this;
            setTimeout(function(){
                self.elem.focus();
            }, 50);
        }
        this.setInputValue();
    }

    DTBox.prototype.setupFooter = function() {
        if (!this.el.footer) {
            var footer = document.createElement("div");
            var handler = this.onTimeChange.bind(this);
            var self = this;
            
            function makeRow(label, name, range, changeHandler) {
                var row = document.createElement("div");
                row.classList.add('cal-time');

                var labelCol = row.appendChild(document.createElement("div"));
                labelCol.classList.add('cal-time-label');
                labelCol.innerText = label;

                var valueCol = row.appendChild(document.createElement("div"));
                valueCol.classList.add('cal-time-value');
                valueCol.innerText = '00';

                var inputCol = row.appendChild(document.createElement("div"));
                var slider = inputCol.appendChild(document.createElement("input"));
                Object.assign(slider, {step:1, min:0, max:range, name:name, type:'range'});
                Object.defineProperty(footer, name, {value: slider});
                inputCol.classList.add('cal-time-slider');
                slider.onchange = changeHandler;
                slider.oninput = changeHandler;
                slider.onmousedown = changeHandler;
                self[name] = self[name] || parseInt(slider.value) || 0;
                footer.appendChild(row)
            }
            makeRow('HH:', 'hours', 23, handler);
            makeRow('MM:', 'minutes', 59, handler);
            makeRow('SS:', 'seconds', 59, handler);

            footer.classList.add("cal-footer");
            Object.defineProperty(this.el, "footer", { value: footer });
            tryAppendChild(footer, this.el.wrapper);
        }
        this.setFooterContent();
    }

    DTBox.prototype.setFooterContent = function() {
        if (this.el.footer) {
            var footer = this.el.footer;
            footer.hours.value = this.hours;
            footer.children[0].children[1].innerText = padded(this.hours, 2);
            footer.minutes.value = this.minutes;
            footer.children[1].children[1].innerText = padded(this.minutes, 2);
            footer.seconds.value = this.seconds;
            footer.children[2].children[1].innerText = padded(this.seconds, 2);
        }
    }

    DTBox.prototype.setInputValue = function() {
        var date = new Date(this.year, this.month, this.day);
        var strings = [];
        if (this.settings.config.showDate) {
            strings.push(renderDate(date, this.settings));
        }
        if (this.settings.config.showTime) {
            var joined = new Date(date.getTime() + this.time);
            strings.push(renderTime(joined, this.settings));
        }
        this.elem.value = strings.join(', ');
    }

    DTBox.prototype.onDateSelected = function (e) {
        var row = e.target.parentNode;
        var date = parseInt(e.target.innerText);
        if (!(row.nextSibling && row.nextSibling.nextSibling) && date < 8) {
            this.month += 1;
        } else if (!(row.previousSibling && row.previousSibling.previousSibling) && date > 7) {
            this.month -= 1;
        }
        this.day = parseInt(e.target.innerText);
        this.value = new Date(this.year, this.month, this.day);
        this.setInputValue();
        this.setHeaderContent();
        this.setBodyContent();
    }

    /** @param {Event} e */
    DTBox.prototype.onMonthSelected = function (e) {
        var col = 0;
        var row = 2;
        var cell = e.target;
        if (cell.parentNode.nextSibling){
            row = cell.parentNode.previousSibling ? 1: 0;
        }
        if (cell.previousSibling) {
            col = 3;
            if (cell.nextSibling) {
                col = cell.previousSibling.previousSibling ? 2 : 1;
            }
        }
        this.month = 4 * row + col;
        this.bodyType = "DAYS";
        this.setHeaderContent();
        this.setupBody();
    }

    /** @param {Event} e */
    DTBox.prototype.onYearSelected = function (e) {
        this.year = parseInt(e.target.innerText);
        this.bodyType = "MONTHS";
        this.setHeaderContent();
        this.setupBody();
    }

    /** @param {Event} e */
    DTBox.prototype.onHeaderChange = function (e) {
        var cell = e.target;
        if (cell.previousSibling && cell.nextSibling) {
            var idx = BODYTYPES.indexOf(this.bodyType);
            if (idx < 0 || !BODYTYPES[idx + 1]) {
                return;
            }
            this.bodyType = BODYTYPES[idx + 1];
            this.setupBody();
        } else {
            var sign = cell.previousSibling ? 1 : -1;
            switch (this.bodyType) {
                case "DAYS":
                    this.month += sign * 1;
                    break;
                case "MONTHS":
                    this.year += sign * 1;
                    break;
                case "YEARS":
                    this.year += sign * 10;
            }
            if (this.month > 11 || this.month < 0) {
                this.year += Math.floor(this.month / 11);
                this.month = this.month > 11 ? 0 : 11;
            }
        }
        this.setHeaderContent();
        this.setBodyContent();
    }


    /**
     * @param {HTMLElement} elem 
     * @returns {{left:number, top:number}}
     */
    function getOffset(elem) {
        var box = elem.getBoundingClientRect();
        var left = window.pageXOffset !== undefined ? window.pageXOffset : 
            (document.documentElement || document.body.parentNode || document.body).scrollLeft;
        var top = window.pageYOffset !== undefined ? window.pageYOffset : 
            (document.documentElement || document.body.parentNode || document.body).scrollTop;
        return { left: box.left + left, top: box.top + top };
    }
    function empty(e) {
        for (; e.children.length; ) e.removeChild(e.children[0]);
    }
    function tryAppendChild(newChild, refNode) {
        try {
            refNode.appendChild(newChild);
            return newChild;
        } catch (e) {
            console.trace(e);
        }
    }

    /** @class */
    function hookFuncs() {
        /** @type {Handlers} */
        this._funcs = {};
    }
    /**
     * @param {string} key 
     * @param {Function} func 
     */
    hookFuncs.prototype.add = function(key, func){
        if (!this._funcs[key]){
            this._funcs[key] = [];
        }
        this._funcs[key].push(func)
    }
    /**
     * @param {String} key 
     * @returns {Function[]} handlers
     */
    hookFuncs.prototype.get = function(key){
        return this._funcs[key] ? this._funcs[key] : [];
    }

    /**
     * @param {Array.<string>} arr 
     * @param {String} string 
     * @returns {Array.<string>} sorted string
     */
    function sortByStringIndex(arr, string) {
        return arr.sort(function(a, b){
            var h = string.indexOf(a);
            var l = string.indexOf(b);
            var rank = 0;
            if (h < l) {
                rank = -1;
            } else if (l < h) {
                rank = 1;
            } else if (a.length > b.length) {
                rank = -1;
            } else if (b.length > a.length) {
                rank = 1;
            }
            return rank;
        });
    }

    /**
     * Remove keys from array that are not in format
     * @param {string[]} keys 
     * @param {string} format 
     * @returns {string[]} new filtered array
     */
    function filterFormatKeys(keys, format) {
        var out = [];
        var formatIdx = 0;
        for (var i = 0; i<keys.length; i++) {
            var key = keys[i];
            if (format.slice(formatIdx).indexOf(key) > -1) {
                formatIdx += key.length;
                out.push(key);
            }
        }
        return out;
    }

    /**
     * @template {StringNumObj} FormatObj
     * @param {string} value 
     * @param {string} format 
     * @param {FormatObj} formatObj 
     * @param {function(Object.<string, hookFuncs>): null} setHooks 
     * @returns {FormatObj} formatObj
     */
    function parseData(value, format, formatObj, setHooks) {
        var hooks = {
            canSkip: new hookFuncs(),
            updateValue: new hookFuncs(),
        }
        var keys = sortByStringIndex(Object.keys(formatObj), format);
        var filterdKeys = filterFormatKeys(keys, format);
        var vstart = 0; // value start
        if (setHooks) {
            setHooks(hooks);
        }

        for (var i = 0; i < keys.length; i++) {
            var key = keys[i];
            var fstart = format.indexOf(key);
            var _vstart = vstart; // next value start
            var val = null;
            var canSkip = false;
            var funcs = hooks.canSkip.get(key);

            vstart = vstart || fstart;

            for (var j = 0; j < funcs.length; j++) {
                if (funcs[j](formatObj)){
                    canSkip = true;
                    break;
                }
            }
            if (fstart > -1 && !canSkip) {
                var sep = null;
                var stop = vstart + key.length;
                var fnext = -1;
                var nextKeyIdx = i + 1;
                _vstart += key.length; // set next value start if current key is found

                // get next format token used to determine separator
                while (fnext == -1 && nextKeyIdx < keys.length){
                    var nextKey = keys[nextKeyIdx];
                    nextKeyIdx += 1;
                    if (filterdKeys.indexOf(nextKey) === -1) {
                        continue;
                    }
                    fnext = nextKey ? format.indexOf(nextKey) : -1; // next format start
                }
                if (fnext > -1){
                    sep = format.slice(stop, fnext);
                    if (sep) {
                        var _stop = value.slice(vstart).indexOf(sep);
                        if (_stop && _stop > -1){
                            stop = _stop + vstart;
                            _vstart = stop + sep.length;
                        }
                    }
                }
                val = parseInt(value.slice(vstart, stop));

                var funcs = hooks.updateValue.get(key);
                for (var k = 0; k < funcs.length; k++) {
                    val = funcs[k](val, formatObj, vstart, stop);
                }
            }
            formatObj[key] = { index: vstart, value: val };
            vstart = _vstart; // set next value start
        }
        return formatObj;
    }

    /**
     * @param {String} value 
     * @param {DTS} settings 
     * @returns {Date} date object
     */
    function parseDate(value, settings) {
        /** @type {{yyyy:number=, yy:number=, mm:number=, dd:number=}} */
        var formatObj = {yyyy:null, yy:null, mm:null, dd:null};
        var format = ((settings.dateFormat) || '').toLowerCase();
        if (!format) {
            throw new TypeError('dateFormat not found (' + settings.dateFormat + ')');
        }
        var formatObj = parseData(value, format, formatObj, function(hooks){
            hooks.canSkip.add("yy", function(data){
                return data["yyyy"].value;
            });
            hooks.updateValue.add("yy", function(val){
                return 100 * Math.floor(new Date().getFullYear() / 100) + val;
            });
        });
        var year = formatObj["yyyy"].value || formatObj["yy"].value;
        var month = formatObj["mm"].value - 1;
        var date = formatObj["dd"].value;
        var result = new Date(year, month, date);
        return result;
    }

    /**
     * @param {String} value 
     * @param {DTS} settings 
     * @returns {Number} time in milliseconds <= (24 * 60 * 60 * 1000) - 1
     */
    function parseTime(value, settings) {
        var format = ((settings.timeFormat) || '').toLowerCase();
        if (!format) {
            throw new TypeError('timeFormat not found (' + settings.timeFormat + ')');
        }

        /** @type {{hh:number=, mm:number=, ss:number=, a:string=}} */
        var formatObj = {hh:null, mm:null, ss:null, a:null};
        var formatObj = parseData(value, format, formatObj, function(hooks){
            hooks.updateValue.add("a", function(val, data, start, stop){
                return value.slice(start, start + 2);
            });
        });
        var hours = formatObj["hh"].value;
        var minutes = formatObj["mm"].value;
        var seconds = formatObj["ss"].value;
        var am_pm = formatObj["a"].value;
        var am_pm_lower = am_pm ? am_pm.toLowerCase() : am_pm;
        if (am_pm && ["am", "pm"].indexOf(am_pm_lower) > -1){
            if (am_pm_lower == 'am' && hours == 12){
                hours = 0;
            } else if (am_pm_lower == 'pm') {
                hours += 12;
            }
        }
        var time = hours * 60 * 60 * 1000 + minutes * 60 * 1000 + seconds * 1000;
        return time;
    }

    /**
     * @param {Date} value 
     * @param {DTS} settings 
     * @returns {String} date string
     */
    function renderDate(value, settings) {
        var format = settings.dateFormat.toLowerCase();
        var date = value.getDate();
        var month = value.getMonth() + 1;
        var year = value.getFullYear();
        var yearShort = year % 100;
        var formatObj = {
            dd: date < 10 ? "0" + date : date,
            mm: month < 10 ? "0" + month : month,
            yyyy: year,
            yy: yearShort < 10 ? "0" + yearShort : yearShort
        };
        var str = format.replace(settings.dateFormatRegEx, function (found) {
            return formatObj[found];
        });
        return str;
    }

    /**
     * @param {Date} value 
     * @param {DTS} settings 
     * @returns {String} date string
     */
    function renderTime(value, settings) {
        var Format = settings.timeFormat;
        var format = Format.toLowerCase();
        var hours = value.getHours();
        var minutes = value.getMinutes();
        var seconds = value.getSeconds();
        var am_pm = null;
        var hh_am_pm = null;
        if (format.indexOf('a') > -1) {
            am_pm = hours >= 12 ? 'pm' : 'am';
            am_pm = Format.indexOf('A') > -1 ? am_pm.toUpperCase() : am_pm;
            hh_am_pm = hours == 0 ? '12' : (hours > 12 ? hours%12 : hours);
        }
        var formatObj = {
            hh: am_pm ? hh_am_pm : (hours < 10 ? "0" + hours : hours),
            mm: minutes < 10 ? "0" + minutes : minutes,
            ss: seconds < 10 ? "0" + seconds : seconds,
            a: am_pm,
        };
        var str = format.replace(settings.timeFormatRegEx, function (found) {
            return formatObj[found];
        });
        return str;
    }

    /**
     * checks if two dates are equal
     * @param {Date} date1 
     * @param {Date} date2 
     * @returns {Boolean} true or false
     */
    function isEqualDate(date1, date2) {
        if (!(date1 && date2)) return false;
        return (date1.getFullYear() == date2.getFullYear() && 
                date1.getMonth() == date2.getMonth() && 
                date1.getDate() == date2.getDate());
    }

    /**
     * @param {Number} val 
     * @param {Number} pad 
     * @param {*} default_val 
     * @returns {String} padded string
     */
    function padded(val, pad, default_val) {
        var default_val = default_val || 0;
        var valStr = '' + (parseInt(val) || default_val);
        var diff = Math.max(pad, valStr.length) - valStr.length;
        return ('' + default_val).repeat(diff) + valStr;
    }

    /**
     * @template X
     * @template Y
     * @param {X} obj 
     * @param {Y} objDefaults 
     * @returns {X|Y} merged object
     */
    function setDefaults(obj, objDefaults) {
        var keys = Object.keys(objDefaults);
        for (var i=0; i<keys.length; i++) {
            var key = keys[i];
            if (!Object.prototype.hasOwnProperty.call(obj, key)) {
                obj[key] = objDefaults[key];
            }
        }
        return obj;
    }


    window.dtsel = Object.create({},{
        DTS: { value: DTS },
        DTObj: { value: DTBox },
        fn: {
            value: Object.defineProperties({}, {
                empty: { value: empty },
                appendAfter: {
                    value: function (newElem, refNode) {
                        refNode.parentNode.insertBefore(newElem, refNode.nextSibling);
                    },
                },
                getOffset: { value: getOffset },
                parseDate: { value: parseDate },
                renderDate: { value: renderDate },
                parseTime: {value: parseTime},
                renderTime: {value: renderTime},
                setDefaults: {value: setDefaults},
            }),
        },
    });
})();
