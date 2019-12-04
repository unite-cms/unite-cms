<template>
  <div class="uk-background-secondary uk-height-viewport uk-flex uk-flex-center uk-flex-middle">
    <div class="uk-card uk-card-default uk-padding">
      <form class="uk-form">

        <alerts-list />

        <div class="uk-margin">
          <label>Username</label>
          <input type="text" class="uk-input" v-model="username" />
        </div>
        <div class="uk-margin">
          <label>Passwort</label>
          <input type="password" class="uk-input" v-model="password" />
        </div>
        <div class="uk-margin">
          <button class="uk-button uk-button-primary" @click.prevent="login">Login</button>
        </div>

        <div class="uk-overlay-default uk-position-cover" v-if="loading">
          <div uk-spinner class="uk-position-center"></div>
        </div>

      </form>
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
                password: '',
            }
        },
        components: {AlertsList},
        methods: {
            login() {
                this.loading = true;
                Alerts.$emit('clear');
                User.$emit('login',
                    {
                        type: 'Admin',
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
