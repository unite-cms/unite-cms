<template>
  <div class="uk-background-secondary uk-height-viewport uk-flex uk-flex-center uk-flex-middle">
    <div class="uk-card uk-card-default uk-padding">
      <form class="uk-form">
        <div class="uk-alert uk-alert-danger" v-for="error in errors">{{ error }}</div>

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

    export default {
        data() {
            return {
                errors: [],
                loading: false,
                username: '',
                password: '',
            }
        },
        methods: {
            login() {
                this.loading = true;
                this.errors = [];
                User.$emit('login',
                    {
                        type: 'Admin',
                        username: this.username,
                        password: this.password,
                    },
                    (data) => {  this.$router.push('/'); },
                    (errors) => { this.errors = errors; },
                    () => { this.loading = false; }
                );
            }
        }
    }
</script>
