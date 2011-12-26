/**
 * User: Mohammad Milad Naseri
 * Date: Jan 13, 2011
 * Time: 2:12:15 AM
 */

if (!window.Service) {

    Service = {
        url:function (service, gateway, args) {
            if (!args) {
                args = {};
            }
            var url = Common.getServiceUrl(service, gateway);
            url += "&";
            var data = "";
            for (var arg in args) {
                data += arg + "=" + encodeURI(JSON.stringify(args[arg])) + "&";
            }
            if (data.length > 0) {
                data = data.substring(0, data.length - 1);
            }
            url += data;
            return url;
        },
        call:function (service, gateway, args, callback) {
            if (!args) {
                args = {};
            }
            if (!args.currentUrl) {
                args.currentUrl = Common.url;
            }
            var result = null;
            var data = "";
            var asynchronous = typeof(callback) != 'undefined';
            var request = Ajax.getXmlHttpRequest();
            if (asynchronous) {
                request.onreadystatechange = function () {
                    if (request.readyState == 4 && request.status == 200) {
                        if (typeof(callback) != 'boolean') {
                            callback(service, gateway, args, request, Service.preprocess(request.responseText));
                        } else {
                            Service.preprocess(request.responseText);
                        }
                    }
                };
            }
            var serviceUrl = Common.getServiceUrl(service, gateway);
            request.open("POST", serviceUrl, asynchronous);
            request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            for (var arg in args) {
                data += arg + "=" + encodeURI(JSON.stringify(args[arg])) + "&";
            }
            if (data.length > 0) {
                data = data.substring(0, data.length - 1);
            }
            request.send(data);
            if (!asynchronous) {
                if (request.status == 200) {
                    result = eval("(" + request.responseText + ")");
                }
                return result;
            } else {
                return true;
            }
        },
        preprocess:function (text) {
            if (typeof(text) == 'string') {
                text = text.replace(/^\s+/, "").replace(/\s+$/, "");
            } else {
                text = JSON.stringify(text);
            }
            if (text.length > 0 && text.charAt(0) == '{' && text.charAt(text.length - 1) == '}') {
                text = JSON.parse(text);
                if (text['isException']) {
                    alert(text.message + "\n" + text.trace);
                    return true;
                }
                return false;
            }
        }
    };

    window.Service = Service;

}