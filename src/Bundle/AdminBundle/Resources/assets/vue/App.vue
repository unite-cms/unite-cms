<template>
  <div>
    <section>
      <router-link to="/">Dashboard</router-link>
      <router-link v-if="!isAuthenticated" to="/login">Login</router-link>
      <a v-if="isAuthenticated" @click="logout">Logout</a>
    </section>
    <section class="uk-container">
      <router-view></router-view>
    </section>
  </div>
</template>
<script>

  import UserState from "./state/User";

  export default {
    methods: {
        logout() {
            UserState.$emit('logout', {}, () => {
                this.$router.push('/login');
            })
        }
    },
    computed: {
        isAuthenticated() {
            return UserState.isAuthenticated;
        }
    }
  }
</script>
