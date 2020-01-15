
import Vue from 'vue';

export const Form = new Vue({
    methods: {
        checkHTML5Valid(event) {
            this.$emit('checkHTML5Valid', event);
        }
    }
});
export default Form;
