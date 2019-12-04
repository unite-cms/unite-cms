<template>
  <content-detail :loading="loading || $apollo.loading" @submit="submit">
    <h1 class="uk-card-title">{{ $t('content.user_invite.headline', { contentTitle, view }) }}</h1>
    <textarea-field v-model="text" :field="{ required: true, name: 'text' }" />
    <button slot="footer" class="uk-button uk-button-primary" type="submit">{{ $t('content.user_invite.actions.submit') }}</button>
  </content-detail>
</template>

<script>
    import gql from 'graphql-tag';
    import Icon from "../../components/Icon";
    import Alerts from "../../state/Alerts";
    import AlertsList from '../../components/Alerts';
    import Route from "../../state/Route";
    import ContentDetail from './_detail';
    import FormRow from '../../components/Fields/Form/_formRow';
    import Wysiwyg from "../../components/Fields/Form/Wysiwyg";
    import TextareaField from "../../components/Fields/Form/Textarea";

    export default {
        components: {Icon, ContentDetail, AlertsList, FormRow, TextareaField, Wysiwyg},
        data(){
            return {
                errors: [],
                loading: false,
                user: {},
                text: 'This is an invitation to unite cms. Please click the following link to accept the invitation'
            }
        },
        computed: {
            view() {
                return this.$unite.adminViews[this.$route.params.type];
            },
            contentTitle() {
                return this.view.contentTitle(this.formData);
            }
        },
        apollo: {
            user: {
                fetchPolicy: 'network-only',
                query() {
                    return gql`query($id: ID!) {
                        get${ this.view.type }(id: $id) {
                            username
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
                Alerts.$emit('clear');
                this.loading = true;
                this.$apollo.mutate({
                    mutation: gql`mutation($type: String!, $username: String!, $text: String) {
                        unite {
                            emailInviteRequest(type: $type, username: $username, text: $text)
                        }
                    }`,
                    variables: {
                        type: this.view.type,
                        username: this.user.username,
                        text: this.text
                    }
                }).then((data) => {
                    if(data.data.unite.emailInviteRequest) {
                      Route.back({ updated: this.$route.params.id });
                      Alerts.$emit('push', 'success', this.$t('content.user_invite.success', { contentTitle: this.contentTitle, view: this.view }));
                    } else {
                        Alerts.$emit('push', 'danger', this.$t('content.user_invite.error', { view: this.view, contentTitle: this.contentTitle }));
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                }).finally(() => { this.loading = false }).catch((e) => {
                    Alerts.$emit('push', 'danger', this.$t('content.user_invite.error', { view: this.view, contentTitle: this.contentTitle }));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            },
            fieldViolations(prefix) {
                return Alerts.violationsForPrefix(prefix);
            }
        }
    }
</script>
