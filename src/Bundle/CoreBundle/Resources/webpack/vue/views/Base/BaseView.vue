<template>
    <div>
        <component :deleted="deleted"
                   :is="headerComponent"
                   :selectable="selectable"
                   :title="labels.title"
                   :subTitle="labels.subTitle"
                   :embedded="embedded"
                   :createLabel="labels.create"
                   :sortable="sort.sortable && !selectable && !deleted.showDeleted"
                   :actions="actions"
                   :createUrl="urls.create"
                   :allowCreate="allowCreate"
                   @search="onSearch"></component>

        <div v-if="error" class="unite-table-error uk-alert-danger uk-flex uk-flex-middle">
            <div v-html="feather.icons['alert-triangle'].toSvg({width: 24, height: 24})"></div>
            <div class="uk-flex-1 uk-padding-small">{{ error }}</div>
            <button class="uk-button uk-button-danger" v-on:click.prevent="reload">{{ labels.retry }}</button>
        </div>

        <component :is="contentComponent"
                   :rows="rows"
                   :fields="fields"
                   :sort="sort"
                   :selectable="selectable"
                   :updateable="!deleted.showDeleted"
                   :embedded="embedded"
                   :urls="urls"
                   :settings="settings"
                   :dataFetcher="dataFetcher"
                   @updateRow="onRowUpdate"
                   @updateSort="onUpdateSort"></component>

        <div v-if="loading" class="loading uk-text-center"><div uk-spinner></div></div>

        <component
                :is="footerComponent"
                ref="footer"
                @change="load"
                :total="total"
                :limit="limit"></component>
    </div>
</template>

<script>

    import BaseViewHeader from './BaseViewHeader.vue';
    import BaseViewFooter from './BaseViewFooter.vue';
    import BaseViewContent from './BaseViewContent.vue';

    import uniteViewDataFetcher from '../../../js/uniteViewDataFechter.js';

    import feather from 'feather-icons';

    export default {
        data() {
            let bag = JSON.parse(this.parameters);
            let fields = bag.settings.fields;
            let error = null;
            let fieldQuery = [];
            let filterQuery = [];
            let actions = bag.settings.actions;
            let sort = bag.settings.sort || {
                field: null,
                asc: true
            };

            try {
                fieldQuery = Object.keys(fields).map((identifier) => {
                    return this.$uniteCMSViewFields.resolveFieldQueryFunction(fields[identifier].type)(identifier, fields[identifier], this.$uniteCMSViewFields);
                });
                filterQuery = Object.keys(fields).map((identifier) => {
                    return this.$uniteCMSViewFields.resolveFilterQueryFunction(fields[identifier].type)(identifier, fields[identifier], this.$uniteCMSViewFields);
                });
            } catch (e) {
                error = e;
            }

            let selectable = bag.select.is_mode_none ? null : (bag.select.is_mode_single ? 'SINGLE' : 'MULTIPLE');
            if(selectable) {
                fields = Object.assign({}, {
                    _selectable: {
                        type: "selectrow",
                        settings: {
                            contentType: bag.settings.contentType,
                            view: bag.settings.view
                        }
                    }
                }, fields);
            }

            return {
                headerComponent: typeof this.$options.headerComponent !== 'undefined' ? this.$options.headerComponent : BaseViewHeader,
                footerComponent: typeof this.$options.footerComponent !== 'undefined' ? this.$options.footerComponent : BaseViewFooter,
                contentComponent: typeof this.$options.contentComponent !== 'undefined' ? this.$options.contentComponent : BaseViewContent,
                dataFetcher: uniteViewDataFetcher.create({
                    endpoint: bag.urls.api,
                    csrf_token: bag.csrf_token,
                    settings: bag.settings
                }, fieldQuery, filterQuery),
                loading: false,
                error: error,
                rows: [],
                sort: sort,
                actions: actions,
                initialSort: Object.assign({}, sort),
                limit: 10,
                page: 1,
                total: 0,
                autoUpdateFields: [],
                fields: fields,
                selectable: selectable,
                urls: bag.urls,
                hasTranslations: bag.settings.hasTranslations,
                allowCreate: false,
                deleted: {
                    hasDeleted: false,
                    showDeleted: false
                },
                labels: {
                    title: bag.title,
                    subTitle: bag.subTitle,
                    retry: "Reload",
                    create: "Create"
                },
                embedded: bag.settings.embedded || false,
                feather: feather,
                settings: bag.settings
            }
        },
        props: ['parameters'],
        created: function() {

            // only load, if there is no initial error.
            if(!this.error) {
                this.load();
            }
        },
        watch: {
            rows: {

                // Update autoUpdateFields when they change. For example if a row was reordered using drag & drop in
                // BaseViewContent, this handler will save the changes to the server.
                handler(rows, oldRows) {
                    let updates = [];

                    this.autoUpdateFields.forEach((field) => {
                        rows.filter((row, delta) => {
                            if (typeof row[field] !== 'undefined' && typeof oldRows[delta][field] !== 'undefined') {
                                return row[field] !== oldRows[delta][field];
                            }
                        }).forEach((row) => {
                            updates.push(this.dataFetcher.update(row.id, { sortField: row[field] }));
                        });
                    });

                    if(updates.length > 0) {
                        this.loading = true;
                        Promise.all(updates)
                            .then(() => {}, (error) => { this.error = 'API Error: ' + error; })
                            .catch(() => { this.error = "An error occurred, while trying to fetch data."; })
                            .finally(() => { this.loading = false; });
                    }
                },
                deep: true
            },
            deleted: {
                handler(deleted) {

                    // When coming back from deleted screen reset sortable.
                    if(!deleted.showDeleted && this.sort.sortable) {
                        this.sort.field = this.initialSort.field;
                        this.sort.asc = this.initialSort.asc;
                    }

                    this.dataFetcher.withDeleted(deleted.showDeleted);
                    this.load();
                },
                deep: true
            }
        },
        methods: {
            reload() { this.load(); },
            load(page) {
                this.error = null;
                this.loading = true;

                this.dataFetcher.sort(this.sort).fetch(page)
                    .then(
                        (data) => {

                            this.rows = data.result.result.map((row) => {
                                let deleted = !(row.deleted == null);
                                row._actions = {
                                    delete: row._permissions.DELETE_CONTENT && !deleted,
                                    delete_definitely: row._permissions.DELETE_CONTENT && deleted,
                                    recover: row._permissions.UPDATE_CONTENT && deleted,
                                    translations: row._permissions.UPDATE_CONTENT && !deleted && this.hasTranslations,
                                    revisions: row._permissions.UPDATE_CONTENT && !deleted,
                                    update: row._permissions.UPDATE_CONTENT && !deleted
                                };
                                return row;
                            });

                            this.allowCreate = data.result._permissions.CREATE_CONTENT;
                            this.page = data.result.page;
                            this.total = data.result.total;
                            this.deleted.hasDeleted = data.deleted.total > 0;
                            this.$refs.footer.$emit('goto', this.page);
                        },
                        (error) => { this.error = 'API Error: ' + error; })
                    .catch(() => { this.error = "An error occurred, while trying to fetch data."; })
                    .finally(() => { this.loading = false; });
            },
            onSearch(term) {
                this.dataFetcher.search(term);
                this.load(1);
            },
            onUpdateSort(sort) {
                this.sort = sort;
                this.load();
            },
            onRowUpdate(update) {
                this.dataFetcher.update(update.id, update.data).then(
                    (data) => {
                        let rowToUpdate = this.rows.filter((row) => { return row.id === update.id });
                        if(rowToUpdate.length > 0) {
                            ['updated'].concat(Object.keys(update.data)).forEach((field) => {
                                rowToUpdate[0][field] = data[field];
                            });
                        }
                    },
                    (error) => { this.error = 'API Error: ' + error; })
                    .catch(() => { this.error = "An error occurred, while trying to fetch data."; })
                    .finally(() => { this.loading = false; });
            }
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
            margin-top: -15px;
        }
    }
</style>
