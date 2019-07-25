<template></template>
<script>

    import ViewHeader from './ViewHeader';
    import ViewFooter from './ViewFooter';
    import ViewAlerts from './ViewAlerts';
    import AbstractHeaderField from './AbstractHeaderField';
    import AbstractRowField from './AbstractRowField';
    import { createConfig } from './ViewConfig';

    export default {
        data() {
            return {
                config: createConfig(this.parameters),
                alerts: [],
                rows: this.initialRows,
            }
        },
        mounted() {
            this.load();
        },
        components: {
            ViewHeader, ViewAlerts, ViewFooter
        },
        props: {
            parameters: {
                type: String,
                required: true,
            },
            initialRows: {
                type: Array,
                default: () => [],
            },
        },
        computed: {
            /**
             * Returns all currently visible fields.
             * @returns {Array}
             */
            visibleFields() {
                return this.config.fields;
            }
        },

        methods: {

            load() {
                this.alerts = [];
                this.config.loadRows()
                    .then(rows => this.rows = rows)
                    .catch((error) => {
                        this.alert(error, 'danger', {
                            label: this.config.t('Reload'),
                            callback: this.load,
                        })
                    });
            },

            /**
             * Returns the actual header component for this field.
             *
             * @param field
             */
            getHeaderFieldComponent(field) {
                return AbstractHeaderField;
            },

            /**
             * Returns the actual row component for this field.
             *
             * @param field
             */
            getRowFieldComponent(field) {
                return AbstractRowField;
            },

            /**
             * Show an alert to the user.
             */
            alert(message, type = 'danger', action) {

                if(["danger", "warning", "success"].indexOf(type) < 0) {
                    type = "danger";
                }
                this.alerts.push({
                    type: type,
                    message: message,
                    action: action || null,
                });
            }
        },

        watch: {
            'config.sort': {
                deep: true,
                handler: function() { this.load(); }
            },
            'config.showOnlyDeletedContent': {
                deep: true,
                handler: function() { this.load(); }
            },
            'config.page': {
                deep: true,
                handler: function() { this.load(); }
            },
        }
    }
</script>
<style scoped lang="scss">
    .loading {
        z-index: 100;
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background: rgba(255,255,255,0.5);

        > div {
            position: absolute;
            top: 50%;
            left: 40%;
            margin-top: -15px;
            width: 20%;
        }
    }
</style>
