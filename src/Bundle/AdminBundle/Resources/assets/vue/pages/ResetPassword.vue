<template>
  <div class="uk-background-secondary uk-height-viewport uk-flex uk-flex-center uk-flex-middle">
    <div class="uk-card uk-card-default uk-padding" style="max-width: 500px;">
      <form class="uk-form">

        <h1>{{ $t('reset_password.headline', { type }) }}</h1>
        <p>{{ $t('reset_password.text', { type }) }}</p>

        <alerts-list />

        <template v-if="!success">

          <div class="uk-margin">
            <label>{{ $t('reset_password.labels.username') }}</label>
            <input type="text" class="uk-input" v-model="username" />
          </div>

          <div class="uk-margin">
            <button class="uk-button uk-button-primary" :disabled="loading || !valid" @click.prevent="submit">{{ $t('reset_password.actions.submit') }}</button>
          </div>

          <div class="uk-overlay-default uk-position-cover" v-if="loading">
            <div uk-spinner class="uk-position-center"></div>
          </div>
        </template>

      </form>

      <div class="uk-text-small uk-text-right uk-margin-small-top">
        <router-link class="uk-button-text" to="/login">{{ $t('reset_password.actions.login') }}</router-link>
      </div>

    </div>
  </div>
</template>

<script>

    import Alerts from '../state/Alerts';
    import AlertsList from '../components/Alerts';
    import gql from 'graphql-tag';

    export default {
        data() {
            return {
                loading: false,
                success: false,
                username: '',
            }
        },
        components: {AlertsList},
        computed: {
            type() {
                return 'Admin';
            },
            valid() {
                return this.username.length > 0;
            }
        },
        methods: {
            submit() {
                Alerts.$emit('clear');
                this.loading = true;
                this.success = false;

                this.$apollo.mutate({
                    mutation: gql`mutation($type: String!, $username: String!) {
                        unite {
                            emailPasswordResetRequest(type: $type, username: $username)
                        }
                    }`,
                    variables: {
                        type: this.type,
                        username: this.username
                    }
                }).then((data) => {

                    if(data.data.unite.emailPasswordResetRequest) {
                        Alerts.$emit('push', 'success', this.$t('reset_password.success'));
                        this.success = true;

                    } else {
                        Alerts.$emit('push', 'danger', this.$t('reset_password.error'));
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }

                }).catch((e) => {
                    Alerts.$emit('push', 'danger', this.$t('reset_password.error'));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }).finally(() => { this.loading = false });
            }
        }
    }
</script>
