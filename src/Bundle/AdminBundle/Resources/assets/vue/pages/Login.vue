<template>
  <div class="uk-background-secondary uk-height-viewport uk-flex uk-flex-center uk-flex-middle">
    <div class="uk-card uk-card-default uk-padding" style="max-width: 500px;">
      <form class="uk-form">

        <h1>{{ $t('login.headline') }}</h1>

        <alerts-list />

        <div class="uk-margin">
          <label>{{ $t('login.labels.username') }}</label>
          <input type="text" class="uk-input" v-model="username" />
        </div>
        <div class="uk-margin">
          <label>{{ $t('login.labels.password') }}</label>
          <input type="password" class="uk-input" v-model="password" />
        </div>
        <div class="uk-margin">
          <button class="uk-button uk-button-primary" :disabled="loading || !valid" @click.prevent="login">{{ $t('login.actions.submit') }}</button>
        </div>

        <div class="uk-overlay-default uk-position-cover" v-if="loading">
          <div uk-spinner class="uk-position-center"></div>
        </div>
      </form>

      <div class="uk-text-small uk-text-right uk-margin-small-top">
        <router-link class="uk-button-text" to="/reset-password">{{ $t('login.actions.reset_password') }}</router-link>
      </div>
    </div>
  </div>
</template>

<script>

    import User from '../state/User';
    import Alerts from '../state/Alerts'
    import AlertsList from '../components/Alerts';

    export default {
        data() {
            return {
                loading: false,
                username: '',
                password: ''
            }
        },
        components: {AlertsList},
        computed: {
            valid() {
                return this.username.trim().length > 0 && this.password.trim().length > 0;
            }
        },
        methods: {
            login() {
                this.loading = true;
                Alerts.$emit('clear');
                User.$emit('login',
                    {
                        username: this.username,
                        password: this.password,
                    },
                    (data) => {  this.$router.push('/'); },
                    Alerts.apolloErrorHandler,
                    () => { this.loading = false; }
                );
            }
        }
    }
</script>
