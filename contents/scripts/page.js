/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (9/12/11, 14:35)
 */

if (!window.Page) {

    Page = {
        info:function (href) {
            var locale, url;
            if (href.indexOf('#!') != -1) {
                href = href.substring(href.indexOf('#!') + 2);
                locale = href.substring(0, href.indexOf(":"));
                href = href.substring(href.indexOf(":") + 1);
                url = href;
            } else {
                url = "";
                locale = "en_US";
            }
            return {
                url:url,
                locale:locale
            };
        },
        navigate:function (url, locale, data) {
            var href = window.location.href;
            if (!url || !locale) {
                var info = Page.info(href);
                if (!locale) {
                    locale = Common.locale ? Common.locale : info.locale;
                }
                if (!url) {
                    url = Common.url ? Common.url : info.url;
                }
            }
            if (Common.staticRender) {
                window.location.href = Common.getAbsolutePath(locale + "/" + url);
                return;
            }
            if (window.isNavigating) {
                return;
            }
            window.isNavigating = true;
            Common.canvas.show();
            Common.locale = locale;
            Common.url = url;
            href = window.location.href;
            if (href.indexOf("#!") != -1) {
                href = href.substring(0, href.indexOf("#!"));
            }
            href += "#!" + Common.locale + ":" + url;
            window.location.href = href;
            var head = document.getElementsByTagName('head')[0];
            Common.clearElements(head);
            Common.setHtml(head, "<title>" + document.title + "</title>" +
                "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />");
            Common.onReady(Common && document.page, function () {
                if (typeof(document.page) == 'string') {
                    document.page = document.page.asElement();
                }
                var body = document.page;
                body.innerHTML = Common.image(Ajax.loaders.large);
                Service.call('page', 'get', {
                    url:url,
                    data:data ? data : null
                }, function (s, g, a, response, error) {
                    Common.canvas.show();
                    if (!error) {
                        response = JSON.parse(response.responseText);
                        document.title = response.title;
                        Common.includeJS(response['scripts']);
                        Common.includeCSS(response['styles']);
                        if (response['favicon'] == "") {
                            response['favicon'] = "/contents/images/favicon.ico";
                        }
                        var icon = document.createElement("link");
                        icon.setAttribute('rel', 'shortcut icon');
                        icon.setAttribute('href', Common.getAbsolutePath(response['favicon']));
                        head.appendChild(icon);
                        body.innerHTML = response.body;
                        body.directionAware = true;
                        Common.setHtml(head, head.innerHTML + response['head']);
                        Common.scripts(body);
                    }
                    Common.canvas.show();
                    Service.call('theme', 'scripts', {
                        url:url
                    }, function (s, g, a, response) {
                        Page.media(JSON.parse(response.responseText));
                        Common.canvas.hide();
                    });
                    Common.canvas.show();
                    Service.call('theme', 'styles', {
                        url:url
                    }, function (s, g, a, response) {
                        Page.media(JSON.parse(response.responseText));
                        Common.canvas.hide();
                    });
                    Common.canvas.hide();
                    Common.canvas.hide();
                    Common.onReady(Common && Common.url, function () {
                        Widget.reload();
                    });
                    window.isNavigating = false;
                });
            });
        },
        media:function (media) {
            for (var i = 0; i < media.length; i++) {
                if (media[i].type == 'script') {
                    if (media[i].mode == 'inline') {
                        eval(media[i].content);
                    } else {
                        Common.includeJS(media[i].src);
                    }
                } else {
                    if (media[i].mode == 'inline') {
                        Common.inlineCSS(media[i].content);
                    } else {
                        Common.includeCSS(media[i].src);
                    }
                }
            }
        }
    };

    window.Page = Page;

}