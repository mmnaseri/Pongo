/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (13/12/11, 1:05)
 */

if (!window.Form) {

    Form = {
        render:function (name, target, data) {
            target = target.asElement();
            target.innerHTML = Common.image(Ajax.progressBars.large);
            Common.canvas.show();
            Service.call('forms', 'get', {
                'name':name,
                'url':Page.info(window.location.href).url,
                'data':data
            }, function (s, g, a, response) {
                response = JSON.parse(response.responseText);
                target.innerHTML = response.form;
                //Taking scripts into account
                Common.onReady(Common && Common.url, function () {
                    Common.scripts(target);
                    Common.canvas.hide();
                });
            });
        },
        registerComponent:function (form, component, attribute) {
            form = form.asElement();
            if (!form.data) {
                form.data = [];
            }
            form.data.push({
                element:component.asElement(),
                attribute:attribute
            });
        },
        submit:function (target, form, action, success, failure) {
            form = form.asElement();
            var data = {
                ':submit-form':form.id,
                ':submit-target':target
            };
            for (var i = 0; i < form.data.length; i++) {
                data[form.data[i].element.name] = form.data[i].element[form.data[i].attribute];
            }
            action = action.split("/");
            Service.call(action[0], action[1], data, function (s, g, a, response) {
                response = JSON.parse(response.responseText);
                if (response) {
                    if (success) {
                        success = success.split("/");
                        Service.call(success[0], success[1], data, function (s, g, a, response) {
                            eval(response.responseText);
                        });
                    }
                } else {
                    if (failure) {
                        failure = failure.split("/");
                        Service.call(failure[0], failure[1], data, function (s, g, a, response, errors) {
                            if (!errors) {
                                eval(response.responseText);
                            }
                        });
                    }
                }
            });
        }
    };

    window.Form = Form;

}