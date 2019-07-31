<template></template>
<script>

    import { createConfig } from './ViewConfig';

    import ViewHeader from './ViewHeader';
    import ViewFooter from './ViewFooter';
    import ViewAlerts from './ViewAlerts';

    import AbstractHeaderField from './AbstractHeaderField';
    import SortableHeaderField from './SortableHeaderField';

    import ActionsRowField from './ActionsRowField';
    import SortRowField from './SortRowField';
    import SelectRowField from './SelectRowField';

    export default {
        data() {
            return {
                searchQuery: '',
                config: createConfig(this.parameters, this.$uniteCMSViewFields),
                alerts: [],
                rows: Object.assign([], this.initialRows),
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
                return this.config.sort.sortable && !this.config.showOnlyDeletedContent && this.searchQuery.length === 0;
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
                return this.config.loadRows()
                    .then(rows => this.rows = Object.assign([], rows))
                    .catch((error) => {
                        this.alert(error, 'danger', {
                            label: this.config.t('Reload'),
                            callback: this.load,
                        })
                    });
            },

            update(row, data) {
                return this.config.updateRow(row, data)
                    .catch((error) => {
                        this.load();
                        this.alert(error, 'danger');
                    });
            },

            updateSort(row, index) {
                if(this.canDrag) {
                    let sortData = {};
                    sortData[this.config.sort.field] = index;
                    return this.update(row, sortData);
                }
            },

            search(term) {
                this.searchQuery = term || '';
                this.config.setSearchFilter(this.searchQuery);
                return this.load();
            },

            /**
             * Returns the actual header component for this field.
             *
             * @param field
             */
            getHeaderFieldComponent(field) {

                if(this.alterHeaderFieldComponent(field)) {
                    return this.alterHeaderFieldComponent(field);
                }

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
                
                if(this.alterRowFieldComponent(field)) {
                    return this.alterRowFieldComponent(field);
                }

                switch(field.type) {
                    case '_actions': return ActionsRowField;
                    case '_sort': return SortRowField;
                    case '_select': return SelectRowField;
                    default: return this.$uniteCMSViewFields.resolve(field.type);
                }
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
            },

            alterRowFieldComponent(field) {
                return null;
            },

            alterHeaderFieldComponent(field) {
                return null;
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
            position: fixed;
            top: 50%;
            left: 40%;
            margin-top: -15px;
            width: 20%;
        }
    }
</style>
<style lang="scss">
    @media (min-width: 960px) {
        .unite-main-menu + .unite-main-section {
            .unite-view {
                .loading {
                    > div {
                        margin-left: 155px;
                    }
                }
            }
        }
    }

    @media (min-width: 1600px) {
        .unite-main-menu + .unite-main-section {
            .unite-view {
                .loading {
                    > div {
                        margin-left: 200px;
                    }
                }
            }
        }
    }
</style>