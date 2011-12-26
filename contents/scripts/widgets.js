/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (10/12/11, 15:19)
 */
if (!window.Widget) {

    Widget = {
        render:function (name, target, reference, push) {
            if (typeof(push) != 'boolean') {
                //noinspection JSUnusedAssignment
                push = true;
            }
            Common.canvas.show();
            target.asElement().innerHTML = Common.image(Ajax.loaders.small);
            Service.call('widgets', 'get', {
                name:name,
                reference:reference
            }, function (s, g, a, response) {
                response = JSON.parse(response.responseText);
                Common.includeJS(response.scripts);
                Common.includeCSS(response.styles);
                var element = target.asElement();
                element.className = "widget " + name;
                element.innerHTML = response.content;
                element.directionAware = true;
                if (push) {
                    Widget.register(target, name, reference);
                }
                Common.handleLinks(element);
                Common.canvas.hide();
            });
        },
        reload:function () {
            if (!document.widgets) {
                document.widgets = [];
            }
            for (var i = 0; i < document.widgets.length; i++) {
                var widget = document.widgets[i];
                Widget.render(widget.widget, widget.id, widget.reference, false);
            }
        },
        register:function (id, name, reference) {
            if (!document.widgets) {
                document.widgets = [];
            }
            var element = id.asElement();
            element.widget = name;
            element.reference = reference;
            document.widgets.push(element);
        }
    };

    window.Widget = Widget;

}