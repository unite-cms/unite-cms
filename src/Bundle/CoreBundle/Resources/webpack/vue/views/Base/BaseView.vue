<template>
    <div>
        <component :is="headerComponent" :title="labels.title" :subTitle="labels.subTitle" :createLabel="labels.create" :createUrl="urls.create"></component>

        <component :is="contentComponent"
                   :rows="rows"
                   :fields="fields"
                   :sort="sort"
                   :selectable="selectable"
                   @updateRow="onRowUpdate"
        ></component>

        <p v-if="error" class="uk-text-center">
            <span v-html="feather.icons['alert-triangle'].toSvg({width: 80, height: 80})"></span><br /><br />
            {{ error }}<br/><br/>
            <button class="uk-button" v-on:click.prevent="load">{{ labels.retry }}</button>
        </p>
        <div v-if="loading" class="loading uk-text-center"><div uk-spinner></div></div>
        <component :is="footerComponent" ref="footer" @change="load" :total="total" :limit="limit"></component>
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
                    settings: bag.settings
                }, Object.keys(bag.fields).map((identifier) => {
                    return this.$uniteCMSViewFields.resolveFieldQueryFunction(bag.fields[identifier].type)(identifier, bag.fields[identifier]);
                })),
                loading: false,
                error: null,
                rows: [],
                sort: {
                    field: null,
                    asc: true
                },
                limit: 10,
                page: 1,
                total: 0,
                autoUpdateFields: [],
                fields: bag.fields,
                selectable: bag.select.is_mode_none ? null : (bag.select.is_mode_single ? 'SINGLE' : 'MULTIPLE'),
                urls: bag.urls,
                labels: {
                    title: bag.title,
                    subTitle: bag.subTitle,
                    retry: "Retry",
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
            sort: {
                handler(sort, oldSort) {
                    this.load();
                },
                deep: true
            }
        },
        methods: {
            load(page = null) {

                this.error = null;
                this.loading = true;
                this.dataFetcher.sort(this.sort).fetch(page)
                    .then(
                        (data) => {
                            this.page = data.page;
                            this.total = data.total;
                            this.rows = data.result;
                            this.$refs.footer.$emit('goto', this.page);
                        },
                        (error) => { this.error = 'API Error: ' + error; })
                    .catch(() => { this.error = "An error occurred, while trying to fetch data."; })
                    .finally(() => { this.loading = false; });
            },
            onRowUpdate(update) {
                this.dataFetcher.update(update.id, update.data).then(
                    (data) => {},
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
