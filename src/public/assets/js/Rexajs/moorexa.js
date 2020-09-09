/**
 * @package Moorexa JS
 * @author Amadi Ifeanyi
 * @version 0.0.1
 * @description Helper JS library for moorexa
 */

if (!Element.prototype.prepend) {
    Element.prototype.prepend = function(elem) {
        this.insertBefore(elem, this.firstElementChild);
    }
}

if (!Element.prototype.get) {
    Element.prototype.get = function(elem) {
        if (this.hasAttribute(elem))
        {
            return this.getAttribute(elem);
        }
        return this.querySelector(elem);
    }
}

if (!Element.prototype.all) {
    Element.prototype.all = function(elem) {
        return this.querySelectorAll(elem);
    }
}

if (!Element.prototype.set) {
    Element.prototype.set = function(attr, val) {
        return this.setAttribute(attr, val);
    }
}

// Event trigger
function eventTrigger(eventType, eventName, callback) {
    if (eventName.addEventListener) {
        return eventName.addEventListener(eventType, callback);
    } else if (eventName.attachEvent) {
        return eventName.attachEvent(eventType, callback);
    } else {
        var attach = "on" + eventType;
        return eventName[attach] = callback;
    }
}

if (!Element.prototype.trigger)
{
    Element.prototype.trigger = function(event)
    {
        var ev;

        try
        {
            if (this.dispatchEvent && CustomEvent)
            {
                ev = new CustomEvent(event, {detail : event + ' fired!'});
                this.dispatchEvent(ev);
            }
            else
            {
                throw "CustomEvent Not supported";
            }
        }
        catch(e)
        {
            if (document.createEvent)
            {
                ev = document.createEvent('HTMLEvents');
                ev.initEvent(event, true, true);

                this.dispatchEvent(event);
            }
            else
            {
                ev = document.createEventObject();
                ev.eventType = event;
                this.fireEvent('on'+event.eventType, event);
            }
        }
    };
}

if (!Element.prototype.on)
{
    Element.prototype.on = function(event, callback)
    {
        Event(this, event, callback);
    };
}

// Listen for an event
function Event(ele, event, callback) {
    if (ele.addEventListener) {
        ele.addEventListener(event, callback);
    } else if (ele.attachEvent) {
        ele.attachEvent(event, callback);
    } else if (ele['on' + event]) {
        ele['on' + event] = callback;
    } else {
        return false;
    }
}

// capitalize the first alphabet in every word
if (!String.prototype.capitalize)
{
    String.prototype.capitalize = function()
    {
        var str = this.toString(),
            exp = str.split(" ");
        var string = "";

        for( var st in exp)
        {
            var frs = exp[st].substr(0,1).toUpperCase(),
                other = exp[st].substr(1),
                cap = frs + other;

            string += cap;
        }

        return string;
    }
}


// AlertController
var AlertController = {
    message: '',
    buttons : '',
    create: function(obj) {
        this.message = obj.content;

        var alertwrapper = document.querySelector('*[data-alert="wrapper"]');

        if (alertwrapper === null) {
            alertwrapper = document.createElement('div');
            alertwrapper.setAttribute('data-alert', 'wrapper');

            alertwrapper.innerHTML = '<div class="alert-overlay">\
                <div class="alert-card">\
                    <div class="card-header" data-alert="header"></div>\
                    <div class="card-content" data-alert="content">\
                        <p></p>\
                    </div>\
                    <div class="card-button" align="left" data-alert="buttons">\
                        <button name="ok" type="button" data-alert="ok">Ok</button>\
                    </div>\
                </div>\
            </div>';

            document.body.appendChild(alertwrapper);
        }
        var header = document.querySelector('*[data-alert="header"]');
        var content = document.querySelector('*[data-alert="content"]');
        var alertbtns = document.querySelector('*[data-alert="buttons"]');

        if ('button' in obj)
        {
            if (typeof obj.button == 'object')
            {
                alertbtns.innerHTML = "";
                
                for ( var btn in obj.button)
                {
                    var btnele = document.createElement('button');
                    btnele.name = btn.toLowerCase();
                    btnele.type = 'button';
                    btnele.setAttribute('data-alert', btn);
                    btnele.innerText = btn.capitalize();

                    alertbtns.appendChild(btnele);
                }
            }

            this.buttons = obj.button;
        }

        header.innerHTML = obj.title;
        content.innerHTML = '<p>' + obj.content + '</p>';

        return this;
    },
    present: function() {
        
        if (this.message !== "") {
            var alertoverlay = document.querySelector('.alert-overlay'),
                alertcard = document.querySelector('.alert-card');

            alertoverlay.style.display = 'block';

            setTimeout(function() {
                alertcard.style.transform = 'translateY(0px)';
                alertcard.style.opacity = '1';
            }, 100);

            if (this.buttons !== "")
            {
                var alertbtns = document.querySelector('*[data-alert="buttons"]'),
                    alertchilds = alertbtns.children;
                
                var buttons = this.buttons;

                [].forEach.call(alertchilds, function(ele){
                    Event(ele, 'click', function(){
                        alertcard.style.transition = 'transform 0.3s ease-in-out, opacity 0.5s ease-in-out';
                        alertcard.style.opacity = '0';
                        alertcard.style.transform = 'translateY(-100vh)';

                        setTimeout(function() {
                            alertoverlay.style.display = 'none';
                            alertcard.removeAttribute('style');
                        }, 500); 

                        var da = ele.getAttribute('data-alert');
                        buttons[da].call(this);
                    });
                });
            }
            else
            {
                var okalert = document.querySelector('*[data-alert="ok"]');

                Event(okalert, 'click', function() {

                    alertcard.style.transition = 'transform 0.3s ease-in-out, opacity 0.5s ease-in-out';
                    alertcard.style.opacity = '0';
                    alertcard.style.transform = 'translateY(-100vh)';

                    setTimeout(function() {
                        alertoverlay.style.display = 'none';
                        alertcard.removeAttribute('style');
                    }, 500);
                });
            }
        } else {
            console.log('Failed to display alert. Message empty!');
        }

        return this;
    },

    dismiss: function(){
        var alertcard = document.querySelector('.alert-card');
        alertcard.style.transition = 'transform 0.3s ease-in-out, opacity 0.5s ease-in-out';
        alertcard.style.opacity = '0';
        alertcard.style.transform = 'translateY(-100vh)';

        setTimeout(function() {
            alertoverlay.style.display = 'none';
            alertcard.removeAttribute('style');
        }, 500);
    }
};

// loading controller
var LoadingController = {
    duration: '',
    enableDismiss: false,
    titleTimer: false,

    create: function(obj) {
        var loaderwrapper = document.querySelector('.mor-loader-wrapper');
        if (loaderwrapper == null) {
            loaderwrapper = document.createElement('div');
            loaderwrapper.className = 'mor-loader-wrapper';
            loaderwrapper.innerHTML = '<div class="mor-loader">\
                <div class="mor-loader-inner" align="center">\
                    <span class="loading-circle"></span>\
                    <span class="loading-text"></span>\
                </div>\
            </div>';

            document.body.appendChild(loaderwrapper);
        }

        var loaderinner = document.querySelector('.mor-loader-inner');

        if ('title' in obj) {
            loaderinner.lastElementChild.innerText = obj.title;
        }


        if ('duration' in obj) {
            this.duration = parseInt(obj.duration);
        }

        if ('enableDismiss' in obj) {
            this.enableDismiss = Boolean(obj.enableDismiss);
        }

        return this;
    },

    present: function() {

        var morloader = document.querySelector('.mor-loader');

        if (morloader)
        {
            morloader.style.display = 'block';

            if (this.duration !== "") {
                setTimeout(function() {
                    document.querySelector('.mor-loader').style.display = 'none';
                }, this.duration);
            }

            if (this.enableDismiss !== false) {
                var morloader = document.querySelector('.mor-loader');

                Event(morloader, 'click', function() {
                    this.style.display = 'none';
                });
            }    
        }
        
    },

    title: function(title, delay = false) {
        var loaderinner = document.querySelector('.mor-loader-inner');

        if (title != "") {
            if (delay !== false) {
                this.titleTimer = parseInt(delay);

                setTimeout(function() {
                    loaderinner.lastElementChild.innerText = title;
                }, parseInt(delay));
            } else {
                loaderinner.lastElementChild.innerText = title;
            }

        }
    },

    dismiss: function(delay = false) {
        var morloader = document.querySelector('.mor-loader');

        if (morloader)
        {

        if (delay === false) {
            if (this.titleTimer == false) {
                morloader.style.display = 'none';
            } else {
                setTimeout(function() {
                    setTimeout(function() {
                        morloader.style.display = 'none';
                    }, 200);
                }, this.titleTimer);
            }
        } else {

            if (this.titleTimer == false) {
                setTimeout(function() {
                    morloader.style.display = 'none';
                }, parseInt(delay));
            } else {
                setTimeout(function() {
                    setTimeout(function() {
                        morloader.style.display = 'none';
                    }, parseInt(delay));
                }, this.titleTimer);

            }

        }

        }

    }
};

// ModalController
var ModalController = {
    push : function(obj)
    {
        var modalwrapper = document.querySelector('.mor-modal-wrapper');

        if (modalwrapper == null)
        {
            modalwrapper = document.createElement('div');
            modalwrapper.className = 'mor-modal-wrapper';

            modalwrapper.innerHTML = '\
                <div class="mor-modal-container">\
                    <div class="mor-modal-container-inner">\
                        <div class="mor-js-modal-body">\
                        </div>\
                        <div class="mor-modal-buttons" style="display:none">\
                            <button name="save" class="btn mor-btn" data-modal-action="other" style="display: none;">Save</button>\
                            <button name="save" class="btn mor-btn" data-modal-action="cancel">Close</button>\
                        </div>\
                    </div>\
                </div>';

            document.body.appendChild(modalwrapper);
        }
        else
        {
            modalwrapper = document.querySelector('.mor-modal-wrapper');
        }

        var http = HTTP,
            loader = LoadingController.create({
                duration: 800000,
                enableDismiss: false,
                content: 'Please wait'
            });

        Event(modalwrapper, 'click', function(e){

            if (e.target.className == modalwrapper.className || e.target.className == 'mor-modal-container')
            {
                var modalcontainer = modalwrapper.querySelector('.mor-modal-container-inner');
                    modalcontainer.style.opacity = '0';
                    modalcontainer.style.transform = 'translateY(-150px)';

                    setTimeout(function(){
                        modalwrapper.style.display = 'none';
                    },400);
            }
        });


        if (typeof obj == 'object')
        {   
            if (typeof obj.page == 'string')
            {

            var query = $url + obj.page || "";
                query += '?modal=true&modalText=loadingFromModal&from=js';

            }

            var modalcontainer = modalwrapper.querySelector('.mor-modal-container-inner');
            var btn = modalwrapper.querySelector('*[data-modal-action="other"]');

            if ('cancel' in obj)
            {
                if (obj.cancel == false)
                {
                    btn.parentNode.style.display = "none";
                    btn.nextElementSibling.style.display = "none";
                }
                else
                {
                    btn.parentNode.style.display = "block";
                }
            }
            else
            {
                btn.parentNode.style.display = "block";
            }

            if ('button' in obj)
            {
                var form = document.createElement('form');
                form.method = obj.method || 'post';
                form.name = "modalform";
                form.enctype = 'multipart/form-data';
                
                btn.parentNode.style.display = "block";

                var buttonName = "";

                if (typeof obj.button != 'object')
                {
                    btn.name = obj.button; 
                    buttonName = obj.button;   
                }
                else
                {
                    for(var ob in obj.button)
                    {
                        btn.name = ob;
                        buttonName = ob;
                    }
                }
                
                btn.innerText = buttonName.capitalize();
                btn.style.display = 'inline-block';
                form.innerHTML = modalcontainer.innerHTML;
                modalcontainer.innerHTML = "";
                modalcontainer.appendChild(form);

                if ('auto' in obj)
                {
                    var btn = modalwrapper.querySelector('*[data-modal-action="other"]');
                    Event(btn, 'click', function(e){
                        e.preventDefault();

                        var fb = document.forms['modalform'];
                        var formdata = new FormData();

                        var _continue = true;

                        for (var f = 0; f < fb.length; f++)
                        {
                            if (fb[f].nodeName != 'BUTTON')
                            {
                                if (fb[f].hasAttribute('required'))
                                {
                                    if (fb[f].value.length <= 0)
                                    {
                                        _continue = false;
                                        break;
                                    }

                                }
                                formdata.append('modal['+fb[f].name+']', fb[f].value || fb[f].innerHTML);
                            }
                        }

                        if (_continue === true)
                        {
                            formdata.append('modal[__data__]', JSON.stringify(obj.data) || "");  

                            var http = HTTP;
                            loader.present();

                            var alertctrl = AlertController;

                            $http.post($url + 'moorexa/updateModal', formdata, function($res){
                                
                                loader.dismiss();
                                $res = JSON.parse($res);

                                if (typeof obj.button == 'object')
                                {
                                    obj.button[buttonName].call(this, $res);
                                }
                                
                                if ($res.status == 'success')
                                {
                                    alertctrl.create({
                                        title : 'Success',
                                        content : 'Information submitted successfully'
                                    });

                                    alertctrl.present();

                                    if ('clear' in obj)
                                    {
                                        for (var f = 0; f < fb.length; f++)
                                        {
                                            if (fb[f].nodeName != 'BUTTON')
                                            {
                                                if(fb[f].value)
                                                {
                                                    fb[f].value = "";   
                                                }
                                                else
                                                {
                                                    fb[f].innerHTML = "";
                                                }
                                            }
                                        }
                                    }
                                }
                                else
                                {
                                    if ("reason" in $res)
                                    {
                                        alertctrl.create({
                                            title : 'Failed!',
                                            content : 'Data submission failed. '+ $res.reason
                                        });
                                    }
                                    else
                                    {
                                        alertctrl.create({
                                            title : 'Failed!',
                                            content : 'Data submission failed. Try again please!'
                                        });   
                                    }
                                    

                                    alertctrl.present();
                                }
                            });
                        }
                        else
                        {
                            var alertctrl = AlertController.create({
                                            title : 'Error',
                                            content : 'All fields are required!'
                            });

                            alertctrl.present();
                        }
                        // handle form submission here
                        return false;
                    });
                }
            }

            loader.present();

            var modalbody = modalwrapper.querySelector('.mor-js-modal-body');

            if ('class' in obj)
            {
                var cn = modalbody.className;

                if (cn.indexOf(obj.class) <= 0)
                {
                    modalbody.className += ' '+obj.class;
                }
            }
            
            if (typeof obj.page == 'string')
            {
                if ('data' in obj)
                {
                    var formdata = new FormData();

                    if ('method' in obj)
                    {
                        if (obj.method.toLowerCase() == 'post')
                        {
                            formdata.append('modal[data]', JSON.stringify(obj.data));   
                            $http.post(query, formdata, __callback); 
                        }
                        else
                        {
                            query += "&modal="+JSON.stringify(obj.data);
                            $http.get(query, __callback);
                        }
                    }
                    else
                    {
                        formdata.append('modal[data]', JSON.stringify(obj.data));   
                        $http.post(query, formdata, __callback);    
                    }
                    
                }
                else
                {
                    $http.get(query, __callback);
                }
            }
            else
            {
                modalbody.innerHTML = obj.page.innerHTML; 
                modalwrapper.style.display = 'block';

                setTimeout(function(){
                    modalcontainer.style.opacity = '1';
                    modalcontainer.style.transform = 'translateY(0px)';

                    setTimeout(function(){
                        loader.dismiss();
                    },200);
                },300);  
            }

            function __callback($res)
            {
                modalbody.innerHTML = $res;
                modalwrapper.style.display = 'block';

                setTimeout(function(){
                    modalcontainer.style.opacity = '1';
                    modalcontainer.style.transform = 'translateY(0px)';

                    setTimeout(function(){
                        loader.dismiss();
                    },200);
                },300);
            }


            var cancel = modalwrapper.querySelector('*[data-modal-action="cancel"]');

            Event(cancel, 'click', function(e){
                e.preventDefault();

                var modalcontainer = modalwrapper.querySelector('.mor-modal-container-inner');
                    modalcontainer.style.opacity = '0';
                    modalcontainer.style.transform = 'translateY(-150px)';

                    setTimeout(function(){
                        modalwrapper.style.display = 'none';
                    },400);

                return false;
            });
            
        }
        
    }
};

// current url..
var script = document.querySelector('*[data-moorexa-appurl]');
var $url = script != null ? script.getAttribute('data-moorexa-appurl') : false;


// Hide alerts
var hidealert2 = function() {
    var alcls = document.querySelector('*[data-cancel="confirm"]');
    var dataoverlay = document.querySelector('*[data-overlay="remove"]');

    if (alcls !== null) {
        Event(alcls, 'click', function(e) {
            e.preventDefault();
            var http = HTTP;
            $http.get($url + 'moorexa?clear_cache_olddata=ajax', function($res){
                
            });
            document.querySelector('.confirm-delete').style.display = 'none';
        });
    }


    if (dataoverlay !== null) {
        Event(dataoverlay, 'click', function(d) {
            d.preventDefault();

            document.querySelector('.alert-overlay2').style.display = "none";
            document.body.removeChild(document.querySelector('.alert-overlay2'));
        })
    }
}();


var morinput = document.querySelectorAll('.mor-input');

var morfailedrequests = document.querySelector('.mor-failed-requests');

if (morfailedrequests !== null) {
    Event(morfailedrequests, 'click', function() {

        if (this.hasAttribute('data-clicked')) {
            this.style.bottom = "-35px";
            this.lastElementChild.style.height = '0px';
            this.removeAttribute('data-clicked');
        } else {
            this.style.bottom = "0px";
            this.lastElementChild.style.height = "300px";
            this.setAttribute('data-clicked', true);
        }
    });
}

function __morform()
{
    var morformgroup = document.querySelectorAll('.mor-form-group');

    if (morformgroup !== null)
    {
        if (morformgroup.length > 0) {
            [].forEach.call(morformgroup, function(ele) {
                var label = ele.querySelector('label');
                var group = ele.querySelector('input') || ele.querySelector('select') || ele.querySelector('textarea');
                var span = document.createElement('div');
                span.className = "mor-form-group-span";
                var clearfix = document.createElement('div');
                clearfix.className = 'clearfix';

                if (label !== null)
                {
                    ele.insertBefore(clearfix, label.nextElementSibling);    
                }


                var hoverdiv = document.createElement('div');
                hoverdiv.className = 'mor-hover-expand';

                var hoverdivinner = document.createElement('div');
                hoverdivinner.className = 'mor-hover-expand-middle';

                if (label !== null)
                {
                    var labelstyle = window.getComputedStyle(label);
                    var labelsize = labelstyle['fontSize'] || 0;
                }


                // get data-info
                var datainfo = ele.querySelectorAll('*[data-info]');

                if (datainfo.length > 0) {
                    [].forEach.call(datainfo, function(di) {

                        var chd = document.createElement('var');
                        chd.className = 'mor-question';

                        var input = di; 

                        if (di !== null)
                        {
                            var center = di.offsetHeight / 2 - 10;
                        }

                        var div = document.createElement('div');
                        div.style.position = 'relative';
                        div.style.top = '0';
                        div.style.left = '0';

                        chd.style.top = center + 'px';

                        di.parentNode.insertBefore(div, di);

                        var mortooltip = document.createElement('div');
                        mortooltip.className = 'mor-tooltip tooltip-right tooltip-top tooltip-hide';

                        div.appendChild(di);
                        div.appendChild(chd);
                        div.appendChild(mortooltip);

                        var top = di.offsetTop / 2;

                        var p = document.createElement('p');

                        var patt = input.hasAttribute('pattern') ? input.getAttribute('pattern') : ".*";

                        var regxp = new RegExp(patt, 'g');

                        if (di.type == 'text') {
                            p.innerText = di.getAttribute('data-info') || 'This is a text field.';

                            Event(input, 'focus', function() {
                                chd.className = 'mor-question';
                            });


                            Event(input, 'blur', ___text__);
                            Event(input, 'change', ___text__);
                            Event(input, 'keyup', ___text__);
                            Event(input, 'input', ___text__);

                            function ___text__() {

                                var val = input.value;

                                if (val.length > 0 && val.match(regxp) && val.trim() != "") {
                                    chd.className = 'mor-question input-valid';
                                } else {
                                    chd.className = 'mor-question input-not-valid';
                                }
                            }
                        } else if (di.type == 'number') {
                            p.innerText = input.getAttribute('data-info') || 'This field can only accept digits 0-9.';

                            Event(input, 'keyup', function(e) {
                                if (e.key == "Backspace") {
                                    input.setAttribute('data-keyup', e.target.value);
                                }

                                if (e.key.match(/[0-9]/)) {
                                    input.setAttribute('data-keyup', e.target.value);
                                } else {
                                    input.value = input.value.replace(/[^0-9]/, 'x');
                                    input.value = input.getAttribute('data-keyup');
                                }

                            });


                            Event(input, 'focus', function() {
                                chd.className = 'mor-question';
                            });


                            Event(input, 'change', __number__);
                            Event(input, 'blur', __number__);
                            Event(input, 'keyup', __number__);
                            Event(input, 'input', __number__);

                            function __number__() {

                                var val = input.value;
                                // check
                                if (val.match(/[0-9]/) && val.trim() != "") {
                                    chd.className = 'mor-question input-valid';
                                } else {
                                    chd.className = 'mor-question input-not-valid';
                                }

                            }
                        } else if (di.nodeName == "TEXTAREA") {
                            p.innerText = input.getAttribute('data-info') || 'This is a text field and can accept any character.';

                            Event(input, 'focus', function() {
                                chd.className = 'mor-question';
                            });


                            Event(input, 'change', __textarea__);
                            Event(input, 'blur', __textarea__);
                            Event(input, 'keyup', __textarea__);
                            Event(input, 'input', __textarea__);

                            function __textarea__() {

                                var val = input.value;

                                // check
                                if (val.length > 1 && val.match(regxp) && val.trim() != "") {
                                    chd.className = 'mor-question input-valid';
                                } else {
                                    chd.className = 'mor-question input-not-valid';
                                }
                            }
                        } else if (di.nodeName == "SELECT") {
                            p.innerText = input.getAttribute('data-info') || 'This is a dropdown please select from the options below.';

                            Event(input, 'focus', function() {
                                chd.className = 'mor-question';
                            });


                            Event(input, 'change', __select__);
                            Event(input, 'blur', __select__);
                            Event(input, 'input', __select__);

                            function __select__() {

                                var val = input.value;

                                // check
                                if (val.length > 0 && val.match(regxp) && val.trim() != "") {
                                    chd.className = 'mor-question input-valid';
                                } else {
                                    chd.className = 'mor-question input-not-valid';
                                }
                            }
                        } else if (di.type == "password") {
                            p.innerText = input.getAttribute('data-info') || 'This is a password field. Enter your secret key.';

                            Event(input, 'focus', function() {
                                chd.className = 'mor-question';
                            });


                            Event(input, 'change', __password__);
                            Event(input, 'blur', __password__);
                            Event(input, 'keyup', __password__);
                            Event(input, 'input', __password__);

                            function __password__() {

                                var val = input.value;

                                // check
                                if (val.length > 0 && val.match(regxp) && val.trim() != "") {
                                    chd.className = 'mor-question input-valid';
                                } else {
                                    chd.className = 'mor-question input-not-valid';
                                }
                            }
                        } else if (di.type == "email") {
                            p.innerText = input.getAttribute('data-info') || 'This is an email field. eg. hello@moorexa.com';

                            Event(input, 'focus', function() {
                                chd.className = 'mor-question';
                            });


                            Event(input, 'change', __email__);
                            Event(input, 'keyup', __email__);
                            Event(input, 'blur', __email__);
                            Event(input, 'input', __email__);

                            function __email__() {

                                var val = input.value;

                                // check
                                if (val.match(/([^@]+)+[@]\w{2,}[.]\w{2,}/) && val.match(regxp) && val.trim() != "") {
                                    chd.className = 'mor-question input-valid';
                                } else {
                                    chd.className = 'mor-question input-not-valid';
                                }
                            }
                        } else {
                            if (input.hasAttribute('type')) {
                                p.innerText = input.getAttribute('data-info') || 'This is a ' + input.type + ' field.';

                                Event(input, 'focus', function() {
                                    chd.className = 'mor-question';
                                });


                                Event(input, 'change', __other__);
                                Event(input, 'blur', __other__);
                                Event(input, 'keyup', __other__);
                                Event(input, 'input', __other__);

                                function __other__() {

                                    var val = input.value;

                                    // check
                                    if (val.length > 0 && val.match(regxp) && val.trim() != "") {
                                        chd.className = 'mor-question input-valid';
                                    } else {
                                        chd.className = 'mor-question input-not-valid';
                                    }
                                }
                            }

                        }

                        mortooltip.appendChild(p);

                        Event(input, 'change', function() {

                            if (input.value == "") {
                                chd.className = 'mor-question';
                            }
                        });


                        Event(chd, 'mouseover', function() {
                            if (chd.className !== 'mor-question') {
                                chd.setAttribute('data-overstate', chd.className);
                                chd.className = 'mor-question';
                            }
                        });

                        Event(mortooltip, 'click', function() {
                            if (chd.hasAttribute('data-overstate')) {
                                chd.className = chd.getAttribute('data-overstate');
                                chd.removeAttribute('data-overstate');
                            }

                            mortooltip.style.opacity = '0';
                            mortooltip.style.transform = 'translateX(-40px)';

                            setTimeout(function() {
                                mortooltip.style.display = 'none';
                            }, 1000);

                            chd.removeAttribute('data-click-tooltip');

                        });

                        Event(chd, 'click', function() {

                            var morttip = document.querySelectorAll('.mor-tooltip');
                            var timer;

                            [].forEach.call(morttip, function(mt) {
                                if (chd.hasAttribute('data-overstate')) {
                                    chd.className = chd.getAttribute('data-overstate');
                                    chd.removeAttribute('data-overstate');
                                }



                                if (mt.hasAttribute('data-active') && mt !== mortooltip) {
                                    mt.removeAttribute('data-active');
                                    mt.style.opacity = '0';
                                    mt.style.transform = 'translateX(-40px)';
                                    timer = setTimeout(function() {
                                        mt.style.display = 'none';

                                    }, 400);
                                    chd.removeAttribute('data-click-tooltip');
                                }


                            });



                            if (!chd.hasAttribute('data-click-tooltip')) {
                                chd.setAttribute('data-click-tooltip', true);

                                mortooltip.style.display = 'block';
                                mortooltip.setAttribute('data-active', true);

                                setTimeout(function() {

                                    mortooltip.style.opacity = '1';
                                    mortooltip.style.transform = 'translateX(0px)';
                                    //clearTimeout(timer);

                                }, 100);

                            } else {
                                if (chd.hasAttribute('data-overstate')) {
                                    chd.className = chd.getAttribute('data-overstate');
                                    chd.removeAttribute('data-overstate');
                                }

                                mortooltip.style.opacity = '0';
                                mortooltip.style.transform = 'translateX(-40px)';
                                mortooltip.removeAttribute('data-active');

                                setTimeout(function() {
                                    mortooltip.style.display = 'none';
                                }, 1000);

                                chd.removeAttribute('data-click-tooltip');
                            }

                        });

                    });
                }



                hoverdiv.appendChild(hoverdivinner);

                ele.appendChild(hoverdiv);

                var errorspan = "mor-form-group-span-error";

                if (label !== null)
                {
                    if (group.hasAttribute('id')) {
                        label.setAttribute('for', group.getAttribute('id'));
                    } else {
                        group.id = group.name;
                        label.setAttribute('for', group.name);
                    }
                }

                var hg = 0;
                var ofh = 0;

                if (group)
                {
                    hg = group.offsetHeight - 10;
                    ofh = group.offsetTop + group.offsetHeight;

                    var cptstyle = window.getComputedStyle(group);

                    var borderbottom = cptstyle['borderBottomWidth'] || 0;
                    var width = cptstyle['width'] || 100;

                    var shift = 0;

                    if (borderbottom !== 0) {
                        group.style.borderBottomWidth = borderbottom;
                        shift = parseInt(borderbottom);
                    }

                    if (borderbottom !== 0 && parseInt(borderbottom) !== 0) {
                        hoverdivinner.style.height = borderbottom;
                        span.style.height = borderbottom;
                    }

                    var marginBottom = cptstyle['marginBottom'] || 0;

                    marginBottom = parseInt(marginBottom);

                    span.style.bottom = marginBottom + 'px';

                    if (label !== null)
                    {
                        if (group.type !== "file" || group.type.toUpperCase() != "FILE") {
                            label.style.top = hg + 'px';
                        }
                    }


                    ele.appendChild(span);

                    if (group) {
                        group.setAttribute('placeholder', '');

                        if (group.value.length > 0) {
                            label.style.top = '0px';
                            span.style.width = "100%";
                        }
                    }

                    group.addEventListener('focus', animateformgroup);

                    group.addEventListener('blur', function() {

                        if (group.value == "") {
                            if (group.type !== "file" || group.type.toUpperCase() != "FILE") {

                                if (label != null)
                                {
                                    label.style.top = hg + 'px';
                                    label.style.float = "none";
                                    label.style.marginLeft = "0px";
                                    label.style.transform = 'scale(1)';
                                }

                                if (group.hasAttribute('required')) {
                                    span.className = errorspan;
                                } else {
                                    span.style.width = "0%";
                                }

                            }
                        }

                    });

                    group.addEventListener('change', function() {

                        if (group.value.length > 0) {
                            animateformgroup();
                        } else {
                            if (group.hasAttribute('required')) {
                                span.className = errorspan;
                            } else {
                                span.style.width = "0%";
                            }

                            if (label != null)
                            {
                                label.style.float = "none"; 
                            }   

                        }
                    });

                    group.addEventListener('mouseover', function() {
                        hoverdivinner.style.width = "100%";
                    });

                    group.addEventListener('mouseout', function() {
                        hoverdivinner.style.width = "0%";
                    });
                }


                function animateformgroup() {
                    span.className = "mor-form-group-span";

                    if (label != null)
                    {
                        label.style.top = '10px';
                        label.style.float = "left";

                        if (labelsize !== 0) {
                            var sz = parseInt(labelsize) / 2;
                            label.style.marginLeft = "-" + (sz) + "px";
                        }

                        label.style.transform = 'scale(0.8)';
                    }
                    span.style.width = "100%";

                    var morttip = document.querySelectorAll('.mor-tooltip');
                    var timer;

                    if (morttip.length > 0) {
                        [].forEach.call(morttip, function(mt) {

                            var chd = mt.previousElementSibling;

                            if (chd.hasAttribute('data-overstate')) {
                                chd.className = chd.getAttribute('data-overstate');
                                chd.removeAttribute('data-overstate');
                            }

                            if (mt.hasAttribute('data-active')) {
                                mt.removeAttribute('data-active');
                                mt.style.opacity = '0';
                                mt.style.transform = 'translateX(-40px)';
                                timer = setTimeout(function() {
                                    mt.style.display = 'none';

                                }, 400);
                                chd.removeAttribute('data-click-tooltip');
                            }


                        });
                    }

                }
            });
        }
    }
}

__morform();

// data load end
var loadend = document.querySelectorAll('*[data-loadend]');
if (loadend.length > 0) {
    // load 
    [].forEach.call(loadend, function(le) {

        var data = le.getAttribute('data-loadend');

        // match
        var pattern = /(.*)+[(]+(.*)+[)]/;
        var match = pattern.exec(data);

        if (match !== null && match.length == 3) {
            var func = match[1],
                args = match[2].split(',');

            if (args.length > 0) {
                args.map(function(ele, ind, arr) {
                    if (ele == 'this') {
                        arr[ind] = le;
                    } else if (ele.match(/[0-9]/)) {
                        arr[ind] = parseInt(ele);
                    } else {

                        if (ele != "") {
                            arr[ind] = eval(ele);

                            if (arr[ind].match(/^[.]+(.*)/)) {
                                arr[ind] = document.querySelector(arr[ind]);
                            } else if (arr[ind].match(/^[#]+(.*)/)) {
                                arr[ind] = document.querySelector(arr[ind]);
                            }
                        }
                    }
                });

                if (typeof window[func] != 'undefined')
                {
                    window[func].apply(this, args);  
                }
            }



        }
    });
}

var modalbtns = document.querySelector('*[data-modal="form-btn"]');
if (modalbtns !== null)
{
    var li = modalbtns.querySelectorAll('li');
    var modalform = document.querySelectorAll('.modal-form');

    [].forEach.call(li, function(ele) {

        ele.addEventListener('click', function() {
            var text = this.innerText.toLowerCase().trim();
            var other = text == 'register' ? 'login' : 'register';
            if (!this.hasAttribute('class')) {

                var notactive = document.querySelector('.modal-form.' + text);
                var active = document.querySelector('.modal-form.' + other);

                active.setAttribute('style', 'top:-400px; opacity:0;');

                setTimeout(function() {
                    notactive.style.top = '0px';
                    notactive.style.opacity = '1';

                    [].forEach.call(li, function(elez) {
                        if (elez.hasAttribute('class')) {
                            elez.removeAttribute('class');
                        }
                    });

                    ele.setAttribute('class', 'active');

                }, 400);

                setTimeout(function() {
                    active.setAttribute('style', 'top:400px; opacity:0;');
                }, 900);
            }
        });
    });

    var modalclose = document.querySelector('.modal-close-btn');
    modalclose.addEventListener('click', function() {
        var accountmodal = document.querySelector('.mor-account-modal');
        var modalwrapper = document.querySelector('.mor-modal-wrapper');

        modalwrapper.style.opacity = '0';

        setTimeout(function() {
            accountmodal.style.display = "none";
        }, 700);
    });
}

function loaddatasrc()
{
    var imgs = document.querySelectorAll('img');

    if (imgs.length > 0)
    {
        [].forEach.call(imgs, function(e){

            if(e.hasAttribute('data-src'))
            {
                var src = e.getAttribute('data-src');
                e.src = src;
            }
        });
    }
    
    // javascripts
    var js = document.querySelectorAll('script');

    if (js.length > 0)
    {
        [].forEach.call(js, function(e){

            if(e.hasAttribute('data-src') && e.type != 'text/deffered')
            {
                var src = e.getAttribute('data-src');
                e.src = src;
                e.type = 'text/javascript';
            }
        });
    }
}


function loadtextdeff()
{
    var deffered = document.querySelectorAll('*[type="text/deffered"]');
    if (deffered.length > 0)
    {
        [].forEach.call(deffered, function(def){

            var script = document.createElement("script");
            script.type = "text/javascript";

            if (def.hasAttribute("data-src"))
            {
                script.src = def.getAttribute("data-src");
            }

            if (def.innerText.length > 0)
            {
                script.innerHTML = def.innerHTML;
            }

            script.async = def.hasAttribute("async");

            var body = document.body;
            body.insertBefore(script, body.lastElementChild.nextElementSibling);

        });
    }
}


function formalert()
{
    var __messagewrapper = document.querySelectorAll('.__message-wrapper');

    if (__messagewrapper.length > 0)
    {
        [].forEach.call(__messagewrapper, function(ele){
            setTimeout(function(){
                ele.style.visibility = "visible";
            },300);
        });
        
    }
}

// use this instead of document.querySelector or document.querySelectorAll
var selector = {
    get : function(attr){
        return document.querySelector(attr);
    },
    all : function(attr){
        return document.querySelectorAll(attr);
    }
};

function getFormdata(attr)
{
    var form = selector.get(attr);

    if (form != null)
    {
        var ele = {}, sel = form.all('select'), inp = form.all('input'), text = form.all('textarea'), btn = form.all('button');

        if (sel.length > 0)
        {
            [].forEach.call(sel, (e) => {
                ele[e.name] = e.value.trim();
            });
        }

        if (inp.length > 0)
        {
            [].forEach.call(inp, (e) => {
                ele[e.name] = e.value.trim();
            });
        }

        if (text.length > 0)
        {
            [].forEach.call(text, (e) => {
                ele[e.name] = e.value.trim();
            });
        }

        if (btn.length > 0)
        {
            [].forEach.call(btn, (e) => {
                if (e.value.length > 0)
                {
                    ele[e.name] = e.value.trim();
                }
            });
        }

        return ele;
    }

    return null;
}

// create forin loop
function forin(object, callback)
{
    for (var key in object)
    {
        callback.call(object, key, object[key]);
    }
}

// build json
function buildJson(object)
{
    var json = {};
    for (var x in object)
    {
        json[x] = object[x];
    }

    json = JSON.stringify(json, null, 3);

    return JSON.parse(json);
}