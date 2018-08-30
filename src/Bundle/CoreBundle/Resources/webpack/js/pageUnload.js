import * as ObjectHash from 'object-hash';

export default {
    init(message) {
        let forms = document.querySelectorAll('form');
        forms.forEach((form) => {
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
                forms.forEach((form) => {
                    if(form.initialFormData !== form.calculateData()) { changeFound = true; }
                });
                if(changeFound) {
                    event.preventDefault();
                    return message;
                }
            };
        }
    }
};