
export default {
    init() {
        let markFormGroups = function(form, element) {
            if(element === form || element.tagName === 'FORM' || !element) {
                return;
            } else if (element.classList.contains('unite-form-group-container')) {
                let label = form.querySelector('.uk-tab a[href="#' + element.id + '"]');
                if(label) {
                    label.classList.add('group-with-errors');
                }
            }

            return markFormGroups(form, element.parentElement);
        };
        let handleForm = function(form, initial){

            form.querySelectorAll('.uk-tab a.group-with-errors').forEach((label) => {
                label.classList.remove('group-with-errors');
            });

            if(!initial) {
                form.querySelectorAll('*:invalid').forEach((field) => { markFormGroups(form, field) });
            } else {
                form.querySelectorAll('.uk-alert-danger').forEach((field) => { markFormGroups(form, field) })
            }
        };

        let forms = document.querySelectorAll('form');
        forms.forEach((form) => {
            handleForm(form, true);
            form.querySelectorAll('button[type="submit"]').forEach((submit) => {
                submit.addEventListener('click', function(e){ handleForm(form, false); }, true);
            });
            form.addEventListener('submit', function(e){ handleForm(form, false); }, true);
        });
    }
};
