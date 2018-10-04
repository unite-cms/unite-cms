<template>
    <div>
        <component :is="headerComponent" :title="labels.title" :createLabel="labels.create" :createUrl="urls.create"></component>

        <component :is="contentComponent"
                   :rows="rows"
                   :fields="fields"
                   :sort="sort"
                   :selectable="selectable"
        ></component>

        <p v-if="error" class="uk-text-center">
            <span v-html="feather.icons['alert-triangle'].toSvg({width: 80, height: 80})"></span><br /><br />
            {{ error }}<br/><br/>
            <button class="uk-button" v-on:click.prevent="load">{{ labels.retry }}</button>
        </p>
        <div v-if="loading" class="uk-text-center" style="position: absolute; top: 0; right: 0; bottom: 0; left: 0; background: rgba(255,255,255,0.75);"><div style="position: absolute; top: 50%; margin-top: -15px;" uk-spinner></div></div>
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
            let fields = {
                id: {
                    label: "Id",
                    type: "id"
                },
                headline: {
                    label: "Headline",
                    type: "text"
                },
                image: {
                    label: 'Image',
                    type: 'image'
                },
                content: {
                    label: 'Content',
                    type: 'textarea'
                },
                created: {
                    label: 'Created at',
                    type: 'date'
                },
                updated: {
                    label: 'Updated',
                    type: 'date'
                }
            };

            return {
                headerComponent: typeof this.$options.headerComponent !== 'undefined' ? this.$options.headerComponent : BaseViewHeader,
                footerComponent: typeof this.$options.footerComponent !== 'undefined' ? this.$options.footerComponent : BaseViewFooter,
                contentComponent: typeof this.$options.contentComponent !== 'undefined' ? this.$options.contentComponent : BaseViewContent,
                dataFetcher: uniteViewDataFetcher.create({
                    endpoint: bag.urls.api,
                    settings: bag.settings
                }, Object.keys(fields).map((identifier) => {
                    return this.$uniteCMSViewFields.resolveFieldQueryFunction(fields[identifier].type)(identifier, fields[identifier]);
                })),
                loading: false,
                error: null,
                rows: [],
                limit: 10,
                page: 1,
                total: 0,
                autoUpdateFields: [],
                columns: bag.columns,
                selectable: bag.select.is_mode_none ? null : (bag.select.is_mode_single ? 'SINGLE' : 'MULTIPLE'),
                urls: bag.urls,
                labels: {
                    title: "Tags",
                    retry: "Retry",
                    create: "Create"
                },
                fields: fields,
                sort: bag.settings.sort,
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
                this.dataFetcher.sort(this.sort.field, this.sort.asc).fetch(page)
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
            }
        }
    }
</script>

<style scoped>

</style>
