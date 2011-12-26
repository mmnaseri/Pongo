/**
 * User: Mohammad Milad Naseri
 * Date: Jan 12, 2011
 * Time: 9:58:05 PM
 * Basic AJAX API
 */

if (!window.Ajax) {

    Ajax = {
        loaders:{
            small:"<img style='display: block; margin-left: auto; margin-right: auto;' class='ajax-loader large'  " +
                "src='" + "/contents/images/ajax-loader-small.gif" + "' alt='[load]' />",
            large:"<img style='display: block; margin-left: auto; margin-right: auto;' class='ajax-loader small' " +
                "src='" + "/contents/images/ajax-loader.gif" + "' alt='[load]' />"
        },
        progressBars:{
            small:"<img style='display: block; margin-left: auto; margin-right: auto;' class='ajax-loader large'  " +
                "src='" + "/contents/images/ajax-loader-small.gif" + "' alt='[load]' />",
            large:"<img style='display: block; margin-left: auto; margin-right: auto;' class='ajax-loader small' " +
                "src='" + "/contents/images/ajax-progress.gif" + "' alt='[load]' />"
        },
        getXmlHttpRequest:function () {
            if (window.XMLHttpRequest) {
                return new XMLHttpRequest();
            } else {// IE6, IE5
                return new ActiveXObject("Microsoft.XMLHTTP");
            }
        },
        getAsync:function (url, callback, method) {
            if (!method) {
                method = "GET";
            }
            var request = Ajax.getXmlHttpRequest();
            request.onreadystatechange = function () {
                if (request.readyState == 4 && request.status == 200) {
                    callback(request.responseText);
                }
            };
            request.open(method, url, true);
            request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            request.send(null);
        },
        getSync:function (url, callback, method) {
            if (!method) {
                method = "GET";
            }
            var request = Ajax.getXmlHttpRequest();
            request.open(method, url, false);
            request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            request.send(null);
            return request.responseText;
        }
    };

    window.Ajax = Ajax;

}