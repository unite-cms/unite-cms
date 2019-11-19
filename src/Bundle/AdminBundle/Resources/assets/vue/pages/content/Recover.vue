<template>
  <content-detail :loading="loading || $apollo.loading" @submit="submit">
    <h1 class="uk-card-title">{{ $t('content.recover.headline', view) }}</h1>
    <button slot="footer" class="uk-button uk-button-primary" type="submit">{{ $t('content.recover.actions.submit') }}</button>
  </content-detail>
</template>

<script>
    import gql from 'graphql-tag';
    import Icon from "../../components/Icon";
    import ContentDetail from './_detail';
    import Alerts from "../../state/Alerts";
    import Route from "../../state/Route";

    export default {
        components: {Icon, ContentDetail},
        data(){
            return {
                content: {},
                loading: false,
            }
        },
        computed: {
            view() {
                return this.$unite.adminViews[this.$route.params.type];
            }
        },
        apollo: {
            content: {
                query() {
                    return gql`query($id: ID!) {
                        get${ this.view.type }(id: $id) {
                            id
                        }
                    }`;
                },
                variables() {
                    return {
                        id: this.$route.params.id,
                    };
                },
                update(data) {
                    return data[`get${ this.view.type }`];
                }
            }
        },
        methods: {
            submit() {
                this.loading = true;
                this.$apollo.mutate({
                    mutation: gql`mutation($id: ID!, $persist: Boolean!) {
                        recover${ this.view.type }(id: $id, persist:$persist) {
                            id
                        }
                    }`,
                    variables: {
                        id: this.$route.params.id,
                        persist: true,
                    }
                }).then((data) => {
                    Route.back({ updated: this.$route.params.id, deleted: null });
                    Alerts.$emit('push', 'success', this.$t('content.recover.success', this.view));
                }).finally(() => { this.loading = false })
            }
        }
    }
</script>
