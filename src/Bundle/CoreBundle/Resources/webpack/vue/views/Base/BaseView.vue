<template>
    <div>
        <component :is="headerComponent" :title="labels.title" :createLabel="labels.create" :createUrl="createUrl"></component>
        <component :is="contentComponent" :rows="rows"></component>
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

            return {
                headerComponent: typeof this.$options.headerComponent !== 'undefined' ? this.$options.headerComponent : BaseViewHeader,
                footerComponent: typeof this.$options.footerComponent !== 'undefined' ? this.$options.footerComponent : BaseViewFooter,
                contentComponent: typeof this.$options.contentComponent !== 'undefined' ? this.$options.contentComponent : BaseViewContent,
                dataFetcher: uniteViewDataFetcher.create({
                    endpoint: bag.urls.api,
                    settings: bag.settings
                }),
                loading: false,
                error: null,
                rows: [],
                limit: 10,
                page: 1,
                total: 0,
                createUrl: bag.urls.create,
                labels: {
                    title: "Tags",
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
        methods: {
            load(page = null) {

                this.error = null;
                this.loading = true;

                try {
                    this.dataFetcher.fetch(page).then(
                        (data) => {
                            this.page = data.page;
                            this.total = data.total;
                            this.rows = data.result;
                            this.$refs.footer.$emit('goto', this.page);
                        },
                        (error) => { this.error = error; },
                    );
                } catch(err) {
                    this.error = "An error occurred, while trying to fetch data.";
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>

<style scoped>

</style>