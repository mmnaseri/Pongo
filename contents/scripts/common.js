/**
 * User: milad
 * Date: 6/12/11
 * Time: 19:47
 * Common JavaScript Tools
 */

if (!window.Common) {

    Common = {
        host:"localhost",
        root:document.root ? document.root : "/",
        locale:"en_US",
        dir:"ltr",
        servicePrefix:"service",
        initializers:[],
        staticRender:false,
        setRoot:function (root) {
//        if (!window.x) {
//            window.x = [];
//        }
//        window.x.push(root);
            document.root = root;
            Common.root = root;
        },
        initPage:function () {
            for (var i = 0; i < Common.initializers.length; i++) {
                var initializer = Common.initializers[i];
                initializer();
            }
        },
        addInitializer:function (initializer) {
            Common.initializers.push(initializer);
        },
        getAbsolutePath:function (path) {
            path = "/" + Common.root + "/" + path;
            path = path.replace(/\/\/+/g, "/");
            return path;
        },
        getServiceUrl:function (service, gateway) {
            return Common.getAbsolutePath(Common.servicePrefix + "/" + Common.locale + "/" + service + "/" + gateway);
        },
        image:function (src) {
            var exec = /src=('|")(.*?)\1/igm.exec(src);
            return src.replace(exec[2], Common.getAbsolutePath(exec[2]));
        },
        uniqid:function (prefix) {
            if (!prefix) {
                prefix = "";
            }
            var seed = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "0", "a", "b",
                "c", "d", "e", "f"];
            var id = prefix;
            for (var i = 0; i < 32; i++) {
                id += seed[Math.floor(Math.random() * seed.length)];
            }
            return id;
        },
        getEventTarget:function (e) {
            if (!e) {
                e = window.event;
            }
            var target = null;
            if (e.target) {
                target = e.target;
            } else if (e.srcElement) {
                target = e.srcElement;
            }
            if (target && target.nodeType == 3) {
                target = target.parentNode;
            }
            return target;
        },
        attachHandler:function (element, event, handler) {
            if (typeof(element) == 'string') {
                element = document.getElementById(element);
            }
            if (element.addEventListener) {
                element.addEventListener(event, handler, false);
            } else if (element.attachEvent) {
                element.attachEvent('on' + event, handler);
            }
        },
        detachHandler:function (element, event, handler) {
            if (typeof(element) == 'string') {
                element = document.getElementById(element);
            }
            if (element.removeEventListener) {
                element.removeEventListener(event, handler, false);
            } else if (element.detachEvent) {
                element.detachEvent('on' + event, handler);
            }
        },
        log:function (message) {
            if (!window.log) {
                window.log = [];
            }
            window.log.push(message);
        },
        handleLinks:function (node) {
            if (!node) {
                return;
            }
            var pongo = "pongo:";
            if (node.nodeName.toLowerCase() == "a" && node.href && node.href.substring(0, pongo.length) == pongo) {
                node.url = node.href;
                node.href = Common.getAbsolutePath(Common.locale + "/" + node.href.substring(pongo.length));
                node.onclick = function (e) {
                    if (!e) e = window.event;
                    e.returnValue = false;
                    if (e.preventDefault) {
                        e.preventDefault();
                    }
                    var url = Common.getEventTarget(e).url.substring("pongo:".length);
                    Page.navigate(url, Common.locale);
                    return false;
                };
            } else {
                node = node.firstChild;
                while (node) {
                    Common.handleLinks(node);
                    node = node.nextSibling;
                }
            }
        },
        setDirections:function (node, dir) {
            return;
            if (!node) {
                return;
            }
            if (!dir) {
                dir = Common.dir;
            }
            if (node.directionAware && node.style) {
                node.style.direction = dir;
            }
            node = node.firstChild;
            while (node) {
                Common.setDirections(node);
                node = node.nextSibling;
            }
        },
        getWindowSize:function () { //Courtesy of http://www.howtocreate.co.uk/tutorials/javascript/browserwindow
            var width = 0, height = 0;
            if (typeof(window.innerWidth) == 'number') {
                //Non IE
                width = window.innerWidth;
                height = window.innerHeight;
            }
            if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
                //IE 6+ in 'standards compliant mode'
                width = Math.max(document.documentElement.clientWidth, width);
                height = Math.max(document.documentElement.clientHeight, height);
            }
            if (document.body && ( document.body.clientWidth || document.body.clientHeight )) {
                //IE 4 compatible
                width = Math.max(document.body.clientWidth, width);
                height = Math.max(document.body.clientHeight, height);
            }
            return {
                width:width,
                height:height
            };
        },
        canvas:{
            init:function () {
                if (!window.canvas) {
                    window.canvas = {};
                    window.canvas.showing = 0;
                    window.canvas.id = "";
                }
            },
            show:function () {
                Common.canvas.init();
                window.canvas.showing++;
                if (window.canvas.showing != 1) {
                    return;
                }
                window.canvas.id = Common.uniqid("canvas-");
                var canvas = document.createElement("div");
                canvas.id = window.canvas.id;
                canvas.style.backgroundColor = "black";
                canvas.style.opacity = "0.6";
                canvas.style.filter = "alpha (opacity=60)";
                canvas.style.position = "absolute";
                canvas.style.left = "0";
                canvas.style.top = "0";
                var windowSize = Common.getWindowSize();
                canvas.style.width = windowSize.width + "px";
                canvas.style.height = windowSize.height + "px";
                canvas.style.zIndex = "3000";
                document.body.insertBefore(canvas, document.body.firstChild);
            },
            hide:function () {
                Common.canvas.init();
                window.canvas.showing--;
                if (window.canvas.showing != 0) {
                    return;
                }
                window.canvas.showing = false;
                Common.canvas.remove();
            },
            remove:function () {
                Common.log('attempting to remove canvas');
                var canvas = window.canvas.id.asElement();
                if (canvas.parentNode) {
                    canvas.parentNode.removeChild(canvas);
                    return;
                }
                setTimeout(function () {
                    Common.canvas.remove();
                }, 100);
            }
        },
        includeJS:function (js) {
            if (!js) {
                return;
            }
            if (js.pop) {
                for (var i = 0; i < js.length; i++) {
                    Common.includeJS(js[i]);
                }
                return;
            }
            if (Common.agent() != "firefox") {
                Ajax.getAsync(js, function (response) {
                    eval(response);
                });
            } else {
                var script = document.createElement("script");
                script.setAttribute("type", "text/javascript");
                script.setAttribute("src", js);
                document.getElementsByTagName('head')[0].appendChild(script);
            }
        },
        includeCSS:function (css) {
            if (!css) {
                return;
            }
            if (css.pop) {
                for (var i = 0; i < css.length; i++) {
                    Common.includeCSS(css[i]);
                }
                return;
            }
            var style = document.createElement("link");
            style.setAttribute('type', 'text/css');
            style.setAttribute('rel', 'stylesheet');
            style.setAttribute('href', css);
            document.getElementsByTagName('head')[0].appendChild(style);
        },
        inlineCSS:function (css) {
            if (css.pop) {
                for (var i = 0; i < css.length; i++) {
                    Common.includeCSS(css[i]);
                }
                return;
            }
            var style = document.createElement("style");
            style.setAttribute('type', 'text/css');
            style.innerHTML = css;
            document.getElementsByTagName('head')[0].appendChild(style);
        },
        hasClassName:function (element, className) {
            if (!element.className) {
                return false;
            }
            var c = element.className.split(' ');
            for (var i = 0; i < c.length; i++) {
                if (c[i] == className) {
                    return true;
                }
            }
            return false;
        },
        addClassName:function (element, className) {
            if (!Common.hasClassName(element, className)) {
                element.className = (element.className ? element.className : "") + " " + className;
            }
        },
        removeClassName:function (element, className) {
            if (!element.className) {
                return false;
            }
            var c = element.className.split(' ');
            var has = false;
            for (var i = 0; i < c.length; i++) {
                if (c[i] == className) {
                    has = true;
                    c[i] = "";
                    break;
                }
            }
            if (has) {
                element.className = c.join(" ");
            }
        },
        agent:function () {
            if (navigator.userAgent.indexOf('Firefox') != -1) {
                return "firefox";
            } else if (navigator.userAgent.indexOf('Chrome') != -1) {
                return "chrome";
            } else if (navigator.userAgent.indexOf('Safari') != -1) {
                return "safari";
            } else {
                return "unknown";
            }
        },
        comparison:{
            lt:function (a, b) {
                return a < b;
            },
            gt:function (a, b) {
                return a > b;
            }
        },
        onReady:function (target, action) {
            if (!target) {
                setTimeout(function () {
                    Common.onReady(target, action);
                }, 100);
            } else {
                action();
            }
        },
        clearElements:function (node) {
            while (node.firstChild) {
                node.removeChild(node.firstChild);
            }
        },
        copyElements:function (source, target) {
            var node = source.firstChild;
            while (node) {
                target.appendChild(node);
                node = node.nextSibling;
            }
        },
        moveElements:function (source, target) {
            var node = source.firstChild;
            while (node) {
                source.removeChild(node);
                target.appendChild(node);
                node = node.nextSibling;
            }
        },
        setHtml:function (node, html) {
            var container = document.createElement("div");
            container.innerHTML = html;
            Common.moveElements(container, node);
            document.getElementsByTagName('head')[0].appendChild(container);
            container.parentNode.removeChild(container);
        },
        scripts:function (node) {
            if (!node) {
                return;
            }
            if (node.nodeName.toLowerCase() == 'script') {
                Common.script(node.innerHTML)
            } else {
                node = node.firstChild;
                while (node) {
                    Common.scripts(node);
                    node = node.nextSibling;
                }
            }
        },
        script:function (src) {
            src = src.replace(/^\s*(.*?)\s*$/m, "$1");
            if (src.length > 4 && src.substring(0, 4) == "<!--") {
                src = src.substring(4);
            }
            if (src.length > 3 && src.substring(src.length - 3) == "-->") {
                src = src.substring(0, src.length - 3);
            }
            src = src.split("\n");
            for (var i = 0; i < src.length; i++) {
                if (src[i].indexOf("//") != -1) {
                    src[i] = src[i].substring(0, src[i].indexOf("//"));
                }
            }
            src = src.join("\n");
            eval(src);
        },
        watch:{
            init:function () {
                if (!window.watches) {
                    window.watches = [];
                }
                if (!window.watchInterval) {
                    window.watchInterval = -1;
                }
            },
            add:function (getter, action) {
                Common.watch.init();
                var previous = getter();
                window.watches.push({
                    getter:getter,
                    action:action,
                    previous:previous
                });
                return window.watches.length;
            },
            remove:function (index) {
                Common.watch.init();
                if (index < 0 || index >= window.watches.length) {
                    return;
                }
                if (window.watches.length == 0) {
                    window.watches = [];
                }
                for (var i = index; i < window.watches.length - 1; i++) {
                    window.watches[i] = window.watches[i + 1];
                }
                window.watches.length--;
            },
            monitor:function () {
                Common.watch.init();
                if (window.watchInterval != -1) {
                    return;
                }
                window.watchInterval = setInterval(function () {
                    for (var i = 0; i < window.watches.length; i++) {
                        var watch = window.watches[i];
                        var current = watch.getter();
                        if (current != watch.previous) {
                            watch.action(i, watch.previous, current);
                            watch.previous = current;
                        }
                    }
                }, 100);
            }
        }
    };

    window.Common = Common;

    Object.prototype.asElement = function () {
        if (!this.length) {
            return this;
        }
        return document.getElementById(this);
    };

    Common.addInitializer(Common.watch.monitor);

    Common.attachHandler(window, 'load', Common.initPage);

}