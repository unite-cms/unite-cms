<template>
  <section ref="offcanvas" id="app-navigation" uk-offcanvas="overlay: true">
    <div class="uk-offcanvas-bar">
      <button class="uk-offcanvas-close uk-hidden@m" type="button" uk-close></button>
      <div class="uk-flex uk-flex-column uk-height-1-1">
        <ul class="uk-nav uk-nav-default uk-flex-1">

          <li v-if="contentTypes.length > 0" class="uk-nav-header">Content Types</li>
          <li v-for="type in contentTypes"><router-link :to="'/content/' + type.id"><icon name="layers" class="uk-margin-small-right" /> {{ type.name }}</router-link></li>

          <li v-if="singleContentTypes.length > 0" class="uk-nav-header">Single Content Types</li>
          <li v-for="type in singleContentTypes"><router-link :to="'/setting/' + type.id" :params="{ type: type }"><icon name="settings" class="uk-margin-small-right" /> {{ type.name }}</router-link></li>

          <li v-if="userTypes.length > 0" class="uk-nav-header">User</li>
          <li v-for="type in userTypes"><router-link :to="'/user/' + type.id" :params="{ type: type }"><icon name="user" class="uk-margin-small-right" /> {{ type.name }}</router-link></li>
        </ul>


        <hr />
        <ul class="uk-iconnav uk-flex-center uk-margin-medium-bottom">
          <li><router-link to="/explorer" uk-tooltip="GraphQL explorer"><icon name="globe" /></router-link></li>
          <li><router-link to="/schema" uk-tooltip="Schema"><icon name="code" /></router-link></li>
          <li><router-link to="/logs" uk-tooltip="Logs"><icon name="activity" /></router-link></li>

          <li><router-link  :uk-tooltip="'Update user&#58; ' + user.username" :to="'/user/' + user.type + '/' + user.id"><icon name="user" /></router-link></li>
          <li><a  uk-tooltip="Logout" class="uk-text-danger" @click="logout"><icon name="log-out" /></a></li>
        </ul>
      </div>

      <div class="uk-overlay-default uk-position-cover" v-if="loading">
        <div uk-spinner class="uk-position-center"></div>
      </div>
    </div>
  </section>
</template>

<script>
    import UIkit from "uikit";
    import User from "../state/User"
    import ContentTypes from '../state/ContentTypes';
    import Icon from "./Icon";

    export default {
        components: {Icon},
        data() {
            return {
                loading: true
            }
        },
        mounted() {
            ContentTypes.$on('loaded', () => { this.loading = false; });
            if(ContentTypes.loaded) {
                this.loading = false;
            }
        },
        watch: {
            '$route'(){
                UIkit.offcanvas(this.$refs.offcanvas).hide();
            }
        },
        methods: {
            logout() {
                User.$emit('logout', {}, () => {
                    this.$router.push('/login');
                })
            }
        },
        computed: {
            user() {
                return User.user;
            },
            contentTypes() {
                return ContentTypes.contentTypes;
            },
            userTypes() {
                return ContentTypes.userTypes;
            },
            singleContentTypes() {
                return ContentTypes.singleContentTypes;
            }
        }
    }
</script>
<style scoped lang="scss">
  .uk-offcanvas-bar {
    padding-bottom: 0;
  }
</style>