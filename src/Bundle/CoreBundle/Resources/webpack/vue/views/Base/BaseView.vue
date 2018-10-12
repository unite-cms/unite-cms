<template>
    <div>
        <component :deleted="deleted"
                   :is="headerComponent"
                   :title="labels.title"
                   :subTitle="labels.subTitle"
                   :createLabel="labels.create"
                   :createUrl="urls.create"></component>

        <div v-if="error" class="unite-table-error uk-alert-danger uk-flex uk-flex-middle">
            <div v-html="feather.icons['alert-triangle'].toSvg({width: 24, height: 24})"></div>
            <div class="uk-flex-1 uk-padding-small">{{ error }}</div>
            <button class="uk-button uk-button-danger" v-on:click.prevent="load">{{ labels.retry }}</button>
        </div>

        <component :is="contentComponent"
                   :rows="rows"
                   :fields="fields"
                   :sort="sort"
                   :selectable="selectable"
                   :updateable="!deleted.showDeleted"
                   :urls="urls"
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
            return {
                headerComponent: typeof this.$options.headerComponent !== 'undefined' ? this.$options.headerComponent : BaseViewHeader,
                footerComponent: typeof this.$options.footerComponent !== 'undefined' ? this.$options.footerComponent : BaseViewFooter,
                contentComponent: typeof this.$options.contentComponent !== 'undefined' ? this.$options.contentComponent : BaseViewContent,
                dataFetcher: uniteViewDataFetcher.create({
                    endpoint: bag.urls.api,
                    csrf_token: bag.csrf_token,
                    settings: bag.settings
                }, Object.keys(bag.settings.fields).map((identifier) => {
                    return this.$uniteCMSViewFields.resolveFieldQueryFunction(bag.settings.fields[identifier].type)(identifier, bag.settings.fields[identifier]);
                })),
                loading: false,
                error: null,
                rows: [],
                sort: bag.settings.sort || {
                    field: null,
                    asc: true
                },
                limit: 10,
                page: 1,
                total: 0,
                autoUpdateFields: [],
                fields: bag.settings.fields,
                selectable: bag.select.is_mode_none ? null : (bag.select.is_mode_single ? 'SINGLE' : 'MULTIPLE'),
                urls: bag.urls,
                hasTranslations: bag.settings.hasTranslations,
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
                feather: feather,
            }
        },
        props: ['parameters'],
        created: function() {
            this.load();
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
                    this.dataFetcher.withDeleted(deleted.showDeleted);
                    this.load();
                },
                deep: true
            }
        },
        methods: {
            load() {
                this.error = null;
                this.loading = true;

                this.dataFetcher.sort(this.sort).fetch()
                    .then(
                        (data) => {

                            // In the future, allowed actions will be returned by the api, allowing to display action
                            // buttons based on content and not only content type. At the moment, we fake this here.
                            this.rows = data.result.result.map((row) => {
                                let deleted = !(row.deleted == null);
                                row._actions = {
                                    delete: !deleted,
                                    delete_definitely: deleted,
                                    recover: deleted,
                                    translations: !deleted && this.hasTranslations,
                                    revisions: !deleted,
                                    update: !deleted
                                };
                                return row;
                            });

                            this.page = data.result.page;
                            this.total = data.result.total;
                            this.deleted.hasDeleted = data.deleted.total > 0;
                            this.$refs.footer.$emit('goto', this.page);
                        },
                        (error) => { this.error = 'API Error: ' + error; })
                    .catch(() => { this.error = "An error occurred, while trying to fetch data."; })
                    .finally(() => { this.loading = false; });
            },
            onUpdateSort(sort) {
                this.sort = sort;
                this.load();
            },
            onRowUpdate(update) {
                this.dataFetcher.update(update.id, update.data).then(
                    (data) => {
                        let rowToUpdate = this.rows.filter((row) => { return row.id === update.id });
                        if(rowToUpdate) {
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
