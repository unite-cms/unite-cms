<template>
  <section ref="offcanvas" id="app-navigation" uk-offcanvas="overlay: true">
    <div class="uk-offcanvas-bar">
      <button class="uk-offcanvas-close uk-hidden@m" type="button" uk-close></button>
      <div class="uk-flex uk-flex-column uk-height-1-1">

        <template v-if="viewGroups.length > 0">
          <div class="uk-margin-large uk-hidden@m"></div>
          <button class="view-group-toggle uk-button uk-button-default uk-box-shadow-hover-small" type="button">{{ group }} <icon :width="22" :height="22" name="chevron-down" /></button>
          <div ref="viewGroupSelect" uk-dropdown="mode: click; pos: bottom-justify; delay-hide: 0">
            <ul class="uk-nav uk-dropdown-nav">
              <li v-for="viewGroup in viewGroups" :class="{ 'uk-active': group === viewGroup }"><a href="#" @click.prevent="selectGroup(viewGroup)">{{ viewGroup }}</a></li>
            </ul>
          </div>
          <hr />
        </template>

        <ul class="uk-nav uk-nav-default uk-flex-1">

          <li v-if="contentViews.length > 0" class="uk-nav-header">{{ $t("navigation.content_types.headline") }}</li>
          <li v-for="view in contentViews"><router-link :to="viewRoute(view)"><icon name="layers" class="uk-margin-small-right" /> {{ view.name }}</router-link></li>

          <li v-if="singleContentViews.length > 0" class="uk-nav-header">Single Content Types</li>
          <li v-for="view in singleContentViews"><router-link :to="viewRoute(view)"><icon name="user" class="uk-margin-small-right" /> {{ view.name }}</router-link></li>

          <li v-if="userViews.length > 0" class="uk-nav-header">User</li>
          <li v-for="view in userViews"><router-link :to="viewRoute(view)"><icon name="settings" class="uk-margin-small-right" /> {{ view.name }}</router-link></li>
        </ul>

        <hr />
        <ul class="uk-iconnav uk-flex-center uk-margin-medium-bottom">
          <li v-if="$unite.permissions.QUERY_EXPLORER"><router-link to="/explorer" uk-tooltip="GraphQL explorer"><icon name="globe" /></router-link></li>
          <li v-if="$unite.permissions.SCHEMA"><router-link to="/schema" uk-tooltip="Schema"><icon name="code" /></router-link></li>
          <li v-if="$unite.permissions.LOGS"><router-link to="/logs" uk-tooltip="Logs"><icon name="activity" /></router-link></li>

          <li v-if="userAdminView"><router-link  :uk-tooltip="'Update user&#58; ' + user.username" :to="viewRoute(userAdminView) + '/' + user.id + '/update'"><icon name="user" /></router-link></li>
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
        data() {
          return {
            group: null,
          }
        },
        watch: {
            '$route'(){
                UIkit.offcanvas(this.$refs.offcanvas).hide();

                if(this.$route.params.viewGroup) {
                  this.group = this.$route.params.viewGroup;
                }

                if(!this.group && this.viewGroups.length > 0) {
                    this.group = this.viewGroups[0];
                }
            },
            viewGroups(groups) {
                if(!this.group && groups.length > 0) {
                    this.group = groups[0];
                }
            },
        },
        methods: {
            inGroup(view) {
                return !this.group || this.group === '_all_' || view.groups.indexOf(this.group) >= 0;
            },

            selectGroup(group) {
                this.group = group;

                if(this.$refs.viewGroupSelect) {
                    UIkit.dropdown(this.$refs.viewGroupSelect).hide();
                }
            },

            viewRoute(view) {
              let route = '/' + (view.category === 'single_content' ? 'setting' : view.category);
              return route + '/' + (this.group || '_all_') + '/' + view.id;
            },

            logout() {
                User.$emit('logout', {}, () => {
                    this.$router.push('/login');
                })
            }
        },
        computed: {
            viewGroups() {
                let groups = [];

                Object.values(this.$unite.adminViews).forEach((view) => {
                    view.groups.forEach((group) => {
                        if(groups.indexOf(group) === -1) {
                            groups.push(group);
                        }
                    });
                });
                return groups;
            },
            contentViews() {
                return Object.values(this.$unite.adminViews).filter(view => view.category === 'content' && this.inGroup(view));
            },
            userViews() {
                return Object.values(this.$unite.adminViews).filter(view => view.category === 'user' && this.inGroup(view));
            },
            singleContentViews() {
                return Object.values(this.$unite.adminViews).filter(view => view.category === 'single_content' && this.inGroup(view));
            },
            user() {
                return User.user;
            },
            userAdminView() {
                let views = Object.values(this.$unite.adminViews).filter(view => view.category === 'user' && view.type === this.user.type);
                return views.length > 0 ? views[0] : null;
            }
        }
    }
</script>
<style scoped lang="scss">
  .uk-offcanvas-bar {
    padding-bottom: 0;
  }

  .view-group-toggle {
    position: relative;

    .uk-icon {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      opacity: 0.75;
    }
  }
</style>