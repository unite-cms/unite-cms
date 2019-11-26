<template>
  <content-detail :loading="loading || $apollo.loading" @submit="submit">
    <h1 class="uk-card-title">{{ $t('content.' + this.labelKey + '.headline', { view, contentTitle }) }}</h1>
    <p>{{ $t('content.' + this.labelKey + '.message', { view, contentTitle }) }}</p>
    <button slot="footer" class="uk-button uk-button-danger" type="submit">{{ $t('content.' + this.labelKey + '.actions.submit') }}</button>
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
            labelKey() {
                return '';
            },
            mutation() {
                return {};
            },
            backLinkQuery() {
                return {};
            },

            view() {
                return this.$unite.adminViews[this.$route.params.type];
            },
            contentTitle() {
                return this.view.contentTitle(this.content);
            }
        },
        apollo: {
            content: {
                fetchPolicy: 'network-only',
                query() {
                    return gql`
                    ${ this.view.fragment }
                    query($id: ID!) {
                      get${ this.view.type }(id: $id, includeDeleted: true) {
                        _meta {
                          id
                        }
                        ... ${ this.view.id }
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
                this.$apollo.mutate(this.mutation).then((data) => {
                    Route.back(this.backLinkQuery);
                    Alerts.$emit('push', 'success', this.$t('content.' + this.labelKey + '.success', { view: this.view, contentTitle: this.contentTitle }));
                }).finally(() => { this.loading = false })
            }
        }
    }
</script>
