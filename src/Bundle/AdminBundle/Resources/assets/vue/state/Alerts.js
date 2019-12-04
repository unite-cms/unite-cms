
import Vue from 'vue';
import i18n from "../plugins/i18n";

const GLOBAL_CATEGORIES = [
    'global',
    'internal',
    'graphql',
    'network',
];

export const Alerts = new Vue({
    data() {
        return {
            alerts: []
        }
    },
    created() {

        this.$on('push', (type, message, category = 'global', extensions = {}) => {
            this.alerts.push({ type, message, category, extensions });
        });

        this.$on('clear', () => {
            this.alerts = [];
        });
    },

    computed: {
        globalErrors() {
            return this.alerts.filter((alert) => {
                return GLOBAL_CATEGORIES.indexOf(alert.category) >= 0;
            });
        },
        validationErrors() {
            return this.alerts.filter((alert) => {
                return alert.category === 'validation';
            });
        },
        violations() {
            let violations = [];
            this.validationErrors.forEach((error) => {
                error.extensions.violations.forEach((violation) => {
                    violations.push({
                        type: 'danger',
                        message: violation.message,
                        path: violation.path.split('][').map(p => p.replace('[', '').replace(']', '')),
                    });
                });
            });
            return violations;
        }
    },

    methods: {
        apolloErrorHandler(error) {

            if(error.graphQLErrors) {
                error.graphQLErrors.forEach(graphQLError => {
                    this.$emit('push', 'danger', graphQLError.message, graphQLError.extensions.category, graphQLError.extensions);
                });
            }

            if (error.networkError) {
                let message = error.networkError.message;
                switch (error.networkError.statusCode) {
                    case 500: message = i18n.t('network_error.500'); break;
                    case 401: message = i18n.t('network_error.401'); break;
                }
                this.$emit('push', 'danger', message, 'network', {
                    statusCode: error.networkError.statusCode
                });
            }
        },
        violationsForPrefix(prefix = '') {
            return this.violations.filter((violation) => {
                return violation.path.length > 0 && violation.path[0] === prefix;
            });
        },
        violationsWithoutPrefix(prefix = []) {
            return this.violations.filter((violation) => {
                return violation.path.length > 0 && prefix.indexOf(violation.path[0]) < 0;
            });
        }
    }
});
export default Alerts;
