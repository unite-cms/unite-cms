<template>
    <section class="uk-position-relative uk-padding">
        <div class="uk-flex-middle" uk-grid v-if="dashboard">
            <basic-section :title="$t('dashboard.basic.title')" :data="dashboard" />
        </div>

        <loading-overlay v-if="$apollo.loading" />
    </section>
</template>

<script>
    import ViewHeader from "./_header";
    import _abstract from "./_abstract";
    import BasicSection from "../Dashboard/Sections/Basic";
    import gql from 'graphql-tag';
    import LoadingOverlay from "../LoadingOverlay";

    export default {
        extends: _abstract,
        components: { ViewHeader, BasicSection, LoadingOverlay },
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
