<template>
    <section class="uk-position-relative uk-padding">

        <div class="uk-flex-middle" uk-grid v-if="dashboard">
            <component :is="section.component" v-for="(section, delta) in sections" :key="delta" :data="dashboard" v-bind="section.props" />
        </div>

        <div class="uk-overlay-default uk-position-cover" v-if="$apollo.loading">
            <div uk-spinner class="uk-position-center"></div>
        </div>
    </section>
</template>

<script>
    import _abstract from "./_abstract";
    import Section from "../DashboardSections/Section";
    import gql from 'graphql-tag';

    export default {
        extends: _abstract,
        components: { Section },
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
            },
            sections() {
                return [
                    {
                        component: Section,
                        props: {
                            title: "Welcome to unite cms!"
                        }
                    }
                ];
            }
        }
    }
</script>
