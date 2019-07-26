<template></template>
<script>

    import { createConfig } from './ViewConfig';

    import ViewHeader from './ViewHeader';
    import ViewFooter from './ViewFooter';
    import ViewAlerts from './ViewAlerts';

    import AbstractHeaderField from './AbstractHeaderField';
    import SortableHeaderField from './SortableHeaderField';
    import AbstractRowField from './AbstractRowField';

    import ActionsRowField from './ActionsRowField';
    import SortRowField from './SortRowField';
    import SelectRowField from './SelectRowField';

    export default {
        data() {
            return {
                config: createConfig(this.parameters, this.$uniteCMSViewFields),
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

            canDrag() {
                return this.config.sort.sortable && !this.config.showOnlyDeletedContent;
            },

            actionsFieldConfig() {
                return {
                    identifier: '_actions',
                    label: this.config.t('Actions'),
                    type: '_actions',
                    virtual: true,
                };
            },

            sortFieldConfig() {
                return {
                    identifier: '_sort',
                    icon: 'arrow-up',
                    type: '_sort',
                    virtual: true,
                };
            },

            selectFieldConfig() {
                return {
                    identifier: '_select',
                    label: this.config.t('Select'),
                    type: '_select',
                    virtual: true,
                };
            },

            /**
             * Returns all currently visible fields + a actions field if available.
             * @returns {Array}
             */
            visibleFields() {
                let fields = Object.assign([] ,this.config.fields.filter((field) => {

                    // Prevent duplicated sortable field.
                    if(this.config.sort.sortable && field.identifier === this.config.sort.field) {
                        return false;
                    }

                    return true;
                }));

                if(!this.config.selectable()) {
                    fields.push(this.actionsFieldConfig);

                    if(this.canDrag) {
                        fields.unshift(this.sortFieldConfig);
                    }
                }

                else {
                    fields.unshift(this.selectFieldConfig);
                }
                
                return fields;
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

            update(row, data) {
                this.config.updateRow(row, data)
                    .catch((error) => {
                        this.load();
                        this.alert(error, 'danger');
                    });
            },

            updateSort(row, index) {
                if(this.canDrag) {
                    let sortData = {};
                    sortData[this.config.sort.field] = index;
                    this.update(row, sortData);
                }
            },

            /**
             * Returns the actual header component for this field.
             *
             * @param field
             */
            getHeaderFieldComponent(field) {
                switch(field.type) {
                    case '_actions': return AbstractHeaderField;
                    case '_sort': return AbstractHeaderField;
                    default: return SortableHeaderField;
                }
            },

            /**
             * Returns the actual row component for this field.
             *
             * @param field
             */
            getRowFieldComponent(field) {
                switch(field.type) {
                    case '_actions': return ActionsRowField;
                    case '_sort': return SortRowField;
                    case '_select': return SelectRowField;
                    default: return this.$uniteCMSViewFields.resolve(field.type);
                }
                return this.$uniteCMSViewFields.resolve(field.type);
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
