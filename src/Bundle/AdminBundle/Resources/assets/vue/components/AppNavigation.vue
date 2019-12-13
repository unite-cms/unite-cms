<template>
  <section ref="offcanvas" id="app-navigation" uk-offcanvas="overlay: true">
    <div class="uk-offcanvas-bar">
      <button class="uk-offcanvas-close uk-hidden@m" type="button" uk-close></button>
      <div class="uk-flex uk-flex-column uk-height-1-1">
        <ul class="uk-nav uk-nav-default uk-flex-1">

          <li v-if="contentViews.length > 0" class="uk-nav-header">{{ $t("navigation.content_types.headline") }}</li>
          <li v-for="view in contentViews"><router-link :to="'/content/' + view.id"><icon name="layers" class="uk-margin-small-right" /> {{ view.name }}</router-link></li>

          <li v-if="singleContentViews.length > 0" class="uk-nav-header">Single Content Types</li>
          <li v-for="view in singleContentViews"><router-link :to="'/setting/' + view.id"><icon name="user" class="uk-margin-small-right" /> {{ view.name }}</router-link></li>

          <li v-if="userViews.length > 0" class="uk-nav-header">User</li>
          <li v-for="view in userViews"><router-link :to="'/user/' + view.id"><icon name="settings" class="uk-margin-small-right" /> {{ view.name }}</router-link></li>
        </ul>

        <hr />
        <ul class="uk-iconnav uk-flex-center uk-margin-medium-bottom">
          <li v-if="$unite.permissions.QUERY_EXPLORER"><router-link to="/explorer" uk-tooltip="GraphQL explorer"><icon name="globe" /></router-link></li>
          <li v-if="$unite.permissions.SCHEMA"><router-link to="/schema" uk-tooltip="Schema"><icon name="code" /></router-link></li>
          <li v-if="$unite.permissions.LOGS"><router-link to="/logs" uk-tooltip="Logs"><icon name="activity" /></router-link></li>

          <li v-if="userAdminView"><router-link  :uk-tooltip="'Update user&#58; ' + user.username" :to="'/user/' + userAdminView.id + '/' + user.id + '/update'"><icon name="user" /></router-link></li>
          <li><a  uk-tooltip="Logout" class="uk-text-danger" @click="logout"><icon name="log-out" /></a></li>
        </ul>
      </div>
      <div class="uk-overlay-default uk-position-cover" v-if="!$unite.loaded">
        <div uk-spinner class="uk-position-center"></div>
      </div>
    </div>
  </section>
</template>

<script>
    import UIkit from "uikit";
    import User from "../state/User"
    import Icon from "./Icon";

    export default {
        components: {Icon},
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
            contentViews() {
                return Object.values(this.$unite.adminViews).filter(view => view.category === 'content');
            },
            userViews() {
                return Object.values(this.$unite.adminViews).filter(view => view.category === 'user');
            },
            singleContentViews() {
                return Object.values(this.$unite.adminViews).filter(view => view.category === 'single_content');
            },
            user() {
                return User.user;
            },
            userAdminView() {
                let views = this.userViews.filter(view => view.type === this.user.type);
                return views.length > 0 ? views[0] : null;
            }
        }
    }
</script>
<style scoped lang="scss">
  .uk-offcanvas-bar {
    padding-bottom: 0;
  }
</style>