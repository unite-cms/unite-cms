<template>
    <section class="uk-position-relative uk-padding">

        <view-header :title="view.name" :show-delete-toggle="false" />

        <div class="uk-flex-middle" uk-grid v-if="dashboard">
            <basic-section :title="$t('dashboard.basic.title')" :data="dashboard" />
        </div>

        <div class="uk-overlay-default uk-position-cover" v-if="$apollo.loading">
            <div uk-spinner class="uk-position-center"></div>
        </div>
    </section>
</template>

<script>
    import ViewHeader from "./_header";
    import _abstract from "./_abstract";
    import BasicSection from "../Dashboard/Sections/Basic";
    import gql from 'graphql-tag';

    export default {
        extends: _abstract,
        components: { ViewHeader, BasicSection },
        data() {
            return {
                dashboard: {}
            }
        },
        apollo: {
            dashboard: {
                fetchPolicy: 'network-only',
                query() { return this.query; },
                update(data) {
                    return data;
                },
            }
        },
        watch: {
            '$route'(route){
                this.$apollo.queries.dashboard.refresh();
            }
        },
        computed: {
            query() {
                return gql`
                    ${ this.view.fragment }
                    query { ... ${ this.view.id }
                }`
            }
        }
    }
</script>
