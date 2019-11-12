
import Vue from 'vue';

export const Alerts = new Vue({
    data() {
        return {
            alerts: []
        }
    },
    created() {

        this.$on('push', (type, message) => {
            this.alerts.push({ type, message });
        });

        this.$on('clear', () => {
            this.alerts = [];
        });
    },
    methods: {
        apolloErrorHandler(error) {
            this.$emit('push', 'danger', error);
        }
    }
});
export default Alerts;
