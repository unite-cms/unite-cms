
import flatpickr from "flatpickr";
import confirmDatePlugin from 'flatpickr/dist/plugins/confirmDate/confirmDate';

import Bowser from "bowser";
require('flatpickr/dist/flatpickr.min.css');

export default {

    replaceDate: true,
    replaceDateTime: true,

    findAndReplaceInputs(wrapper) {
        wrapper.querySelectorAll('input[type="date"], input[type="datetime-local"]').forEach((input) => {
            let isDateTime = input.attributes.type.value === 'datetime-local';
            if((this.replaceDateTime && isDateTime) || (this.replaceDate && !isDateTime)) {
                flatpickr(input, {
                    enableTime: isDateTime,
                    dateFormat: isDateTime ? 'Y-m-dTH:i' : 'Y-m-d',
                    minDate: input.min || null,
                    maxDate: input.max || null,
                    time_24hr: true,
                    "plugins": [new confirmDatePlugin({})]
                });
            }
        });
    },

    init() {

        const browser = Bowser.getParser(window.navigator.userAgent);

        // If a browser supports HTML5 date picker, we don't need ot replace it.
        this.replaceDate = !browser.satisfies({
            // from https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/date#Browser_compatibility
            chrome: ">= 20",
            edge: ">= 12",
            firefox: ">= 57",
            opera: ">= 11",
            mobile: {
                'safari': '> 0',
                'android browser': '> 0'
            },
        });

        this.replaceDateTime = !browser.satisfies({
            // from https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/datetime-local#Browser_compatibility
            chrome: ">= 20",
            edge: ">= 12",
            opera: ">= 11",
            mobile: {
                'safari': '> 0',
                'android browser': '> 0'
            },
        });

        if(!this.replaceDate && !this.replaceDateTime) {
            return;
        }

        // Listen to all dynamic form elements.
        let observer = new MutationObserver((mutationsList) => {
            for(let mutation of mutationsList) {
                mutation.addedNodes.forEach((node) => {
                    if(node instanceof HTMLElement) {
                        this.findAndReplaceInputs(node);
                    }
                });
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });

        // Replace all existing form elements.
        this.findAndReplaceInputs(document.body);
    }
}
