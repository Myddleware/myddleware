/** jquery.onoff - v0.3.6 - 2014-06-23
* https://github.com/timmywil/jquery.onoff
* Copyright (c) 2014 Timmy Willison; Licensed MIT */
!function(a,b){"function"==typeof define&&define.amd?define(["jquery"],b):"object"==typeof exports?b(require("jquery")):b(a.jQuery)}(this,function(a){"use strict";function b(c,d){if(!(this instanceof b))return new b(c,d);if("input"!==c.nodeName.toLowerCase()||"checkbox"!==c.type)return a.error("OnOff should be called on checkboxes");var e=a.data(c,b.datakey);return e?e:(this.options=d=a.extend({},b.defaults,d),this.elem=c,this.$elem=a(c).addClass(d.className),this.$doc=a(c.ownerDocument||document),d.namespace+=a.guid++,c.id||(c.id="onoffswitch"+g++),this.enable(),a.data(c,b.datakey,this),void 0)}var c="over out down up move enter leave cancel".split(" "),d=a.extend({},a.event.mouseHooks),e={};if(window.PointerEvent)a.each(c,function(b,c){a.event.fixHooks[e[c]="pointer"+c]=d});else{var f=d.props;d.props=f.concat(["touches","changedTouches","targetTouches","altKey","ctrlKey","metaKey","shiftKey"]),d.filter=function(a,b){var c,d=f.length;if(!b.pageX&&b.touches&&(c=b.touches[0]))for(;d--;)a[f[d]]=c[f[d]];return a},a.each(c,function(b,c){if(2>b)e[c]="mouse"+c;else{var f="touch"+("down"===c?"start":"up"===c?"end":c);a.event.fixHooks[f]=d,e[c]=f+" mouse"+c}})}a.pointertouch=e;var g=1,h=Array.prototype.slice;return b.datakey="_onoff",b.defaults={namespace:".onoff",className:"onoffswitch-checkbox"},b.prototype={constructor:b,instance:function(){return this},wrap:function(){var b=this.elem,c=this.$elem,d=this.options,e=c.parent(".onoffswitch");e.length||(c.wrap('<div class="onoffswitch"></div>'),e=c.parent().addClass(b.className.replace(d.className,""))),this.$con=e;var f=c.next('label[for="'+b.id+'"]');f.length||(f=a("<label/>").attr("for",b.id).insertAfter(b)),this.$label=f.addClass("onoffswitch-label");var g=f.find(".onoffswitch-inner");g.length||(g=a("<div/>").addClass("onoffswitch-inner").prependTo(f)),this.$inner=g;var h=f.find(".onoffswitch-switch");h.length||(h=a("<div/>").addClass("onoffswitch-switch").appendTo(f)),this.$switch=h},_handleMove:function(a){if(!this.disabled){this.moved=!0,this.lastX=a.pageX;var b=Math.max(Math.min(this.startX-this.lastX,this.maxRight),0);this.$switch.css("right",b),this.$inner.css("marginLeft",100*-(b/this.maxRight)+"%")}},_startMove:function(b){b.preventDefault();var c,d;"pointerdown"===b.type?(c="pointermove",d="pointerup"):"touchstart"===b.type?(c="touchmove",d="touchend"):(c="mousemove",d="mouseup");var e=this.elem,f=this.$elem,g=this.options.namespace,h=this.$switch,i=h[0],j=this.$inner.add(h).css("transition","none");this.maxRight=this.$con.width()-h.width()-a.css(i,"margin-left",!0)-a.css(i,"margin-right",!0)-a.css(i,"border-left-width",!0)-a.css(i,"border-right-width",!0);var k=e.checked;this.moved=!1,this.startX=b.pageX+(k?0:this.maxRight);var l=this,m=this.$doc.on(c+g,a.proxy(this._handleMove,this)).on(d+g,function(){j.css("transition",""),m.off(g),setTimeout(function(){if(l.moved){var a=l.lastX>l.startX-l.maxRight/2;e.checked!==a&&(e.checked=a,f.trigger("change"))}l.$switch.css("right",""),l.$inner.css("marginLeft","")})})},_bind:function(){this._unbind(),this.$switch.on(a.pointertouch.down,a.proxy(this._startMove,this))},enable:function(){this.wrap(),this._bind(),this.disabled=!1},_unbind:function(){this.$doc.add(this.$switch).off(this.options.namespace)},disable:function(){this.disabled=!0,this._unbind()},unwrap:function(){this.disable(),this.$label.remove(),this.$elem.unwrap().removeClass(this.options.className)},isDisabled:function(){return this.disabled},destroy:function(){this.disable(),a.removeData(this.elem,b.datakey)},option:function(b,c){var d,e=this.options;if(!b)return a.extend({},e);if("string"==typeof b){if(1===arguments.length)return void 0!==e[b]?e[b]:null;d={},d[b]=c}else d=b;a.each(d,a.proxy(function(a,b){switch(a){case"namespace":this._unbind();break;case"className":this.$elem.removeClass(e.className)}switch(e[a]=b,a){case"namespace":this._bind();break;case"className":this.$elem.addClass(b)}},this))}},a.fn.onoff=function(c){var d,e,f,g;return"string"==typeof c?(g=[],e=h.call(arguments,1),this.each(function(){d=a.data(this,b.datakey),d?"_"!==c.charAt(0)&&"function"==typeof(f=d[c])&&void 0!==(f=f.apply(d,e))&&g.push(f):g.push(void 0)}),g.length?1===g.length?g[0]:g:this):this.each(function(){new b(this,c)})},a.OnOff=b});