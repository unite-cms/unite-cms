import * as ObjectHash from 'object-hash';

export default {
    init(message) {
        let forms = document.querySelectorAll('form:not([data-disable-before-unload-message])');
        forms.forEach((form) => {

            // We need to know if the form was submitted. If so, we don't need to show the message later.
            form.addEventListener('submit', function(){
                form.wasSubmitted = true;
            }, false);

            form.calculateData = function(){
                let data = [];
                for(let value of new FormData(form).entries()) { data.push(value); }
                return ObjectHash(data);
            };
            form.initialFormData = form.calculateData();
        });

        if(forms.length > 0) {
            window.onbeforeunload = function(event){
                let changeFound = false;

                // for each form we check that it was not submitted and if data has changed.
                forms.forEach((form) => {
                    if(form.wasSubmitted !== true && form.initialFormData !== form.calculateData()) { changeFound = true; }
                });

                // If at least one form has changed values and was not submitted, we show a confirmation message.
                if(changeFound) {
                    event.preventDefault();
                    return message;
                }
            };
        }
    }
};