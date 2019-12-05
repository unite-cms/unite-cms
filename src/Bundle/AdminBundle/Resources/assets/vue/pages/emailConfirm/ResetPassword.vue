<template>
  <div class="uk-background-secondary uk-height-viewport uk-flex uk-flex-center uk-flex-middle">
    <div class="uk-card uk-card-default uk-padding" style="max-width: 600px;">
      <div v-if="tokenExpired">
        <alerts-list :alerts="{ message: $t('email_confirm.reset_password.token_expired'), type: 'danger' }" />
      </div>
      <form class="uk-form" v-else>

        <h1>{{ $t('email_confirm.reset_password.headline', { type }) }}</h1>
        <p>{{ $t('email_confirm.reset_password.text', { type }) }}</p>
        <alerts-list />

        <template v-if="!success">
          <div class="uk-margin">
            <label>{{ $t('email_confirm.reset_password.labels.username') }}</label>
            <input disabled type="text" class="uk-input" :value="username" />
          </div>
          <div class="uk-margin">
            <label>{{ $t('email_confirm.reset_password.labels.password') }}</label>
            <input type="password" class="uk-input" v-model="password" />
          </div>
          <div class="uk-margin">
            <label>{{ $t('email_confirm.reset_password.labels.password_repeat') }}</label>
            <input type="password" class="uk-input" v-model="password_repeat" />
          </div>
          <div class="uk-margin">
            <button class="uk-button uk-button-primary" :disabled="loading || !valid" @click.prevent="signup"><label>{{ $t('email_confirm.reset_password.actions.submit') }}</label></button>
          </div>

          <div class="uk-overlay-default uk-position-cover" v-if="loading">
            <div uk-spinner class="uk-position-center"></div>
          </div>
        </template>

      </form>
    </div>
  </div>
</template>

<script>

    import Alerts from '../../state/Alerts';
    import User from '../../state/User';
    import AlertsList from '../../components/Alerts';
    import gql from 'graphql-tag';

    export default {
        data() {
            return {
                loading: false,
                success: false,
                password: '',
                password_repeat: '',
            }
        },
        components: {AlertsList},
        computed: {
            tokenInformation() {
                let tokenParts = this.$route.params.token.split('.');
                return JSON.parse(atob(tokenParts[1]));
            },
            tokenExpired() {
                return this.tokenInformation.exp * 1000 <= new Date().getTime();
            },
            username() {
                return this.tokenInformation.username;
            },
            type() {
                return this.tokenInformation.type;
            },
            valid() {
                return this.password.length > 0
                && this.password === this.password_repeat;
            }
        },
        methods: {
            signup() {
                Alerts.$emit('clear');
                this.loading = true;
                this.success = false;

                this.$apollo.mutate({
                    mutation: gql`mutation($token: String!, $password: String!, $username: String!) {
                        unite {
                            emailPasswordResetConfirm(token: $token, password: $password, username: $username)
                        }
                    }`,
                    variables: {
                        token: this.$route.params.token,
                        password: this.password,
                        username: this.username
                    }
                }).then((data) => {

                    if(data.data.unite.emailPasswordResetConfirm) {
                        Alerts.$emit('push', 'success', this.$t('email_confirm.reset_password.success'));
                        this.success = true;
                        User.$emit('login',
                            {
                                username: this.username,
                                password: this.password,
                            },
                            (data) => { console.log(data);  this.$router.push('/'); },
                            Alerts.apolloErrorHandler,
                            () => { this.loading = false; }
                        );

                    } else {
                        Alerts.$emit('push', 'danger', this.$t('email_confirm.reset_password.error'));
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }

                }).catch((e) => {
                    Alerts.$emit('push', 'danger', this.$t('email_confirm.reset_password.error'));
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }).finally(() => { this.loading = false });
            }
        }
    }
</script>
