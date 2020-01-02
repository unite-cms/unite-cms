<template>
  <section ref="offcanvas" id="app-navigation" uk-offcanvas="overlay: true">
    <div class="uk-offcanvas-bar">
      <button class="uk-offcanvas-close uk-hidden@m" type="button" uk-close></button>
      <div class="uk-flex uk-flex-column uk-height-1-1">

        <template v-if="viewGroups.length > 0">
          <div class="uk-margin-large uk-hidden@m"></div>
          <button class="view-group-toggle uk-button uk-button-default uk-box-shadow-hover-small" type="button">
            <icon class="fix-line-height uk-margin-small-right" :name="viewGroupIcon(group)" />
            {{ group }}
            <icon class="dropdown-icon" :width="22" :height="22" name="chevron-down" />
          </button>
          <div ref="viewGroupSelect" uk-dropdown="mode: click; pos: bottom-justify; delay-hide: 0">
            <ul class="uk-nav uk-dropdown-nav">
              <li v-for="viewGroup in viewGroups" :class="{ 'uk-active': group === viewGroup }"><a href="#" @click.prevent="selectGroup(viewGroup)">
                <icon class="uk-margin-small-right" :name="viewGroupIcon(viewGroup)" />
                {{ viewGroup }}
              </a></li>
            </ul>
          </div>
          <hr />
        </template>

        <ul class="uk-nav uk-nav-default uk-flex-1">
          <li v-for="view in views"><router-link :to="viewRoute(view)"><icon :name="viewIcon(view)" class="uk-margin-small-right" /> {{ view.name }}</router-link></li>
        </ul>

        <hr />
        <ul class="uk-iconnav uk-flex-center uk-margin-medium-bottom">
          <li v-if="$unite.permissions.QUERY_EXPLORER"><router-link to="/explorer" uk-tooltip="GraphQL explorer"><icon name="globe" /></router-link></li>
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
                    this.group = groups[0].name;
                }
            },
        },
        methods: {
            inGroup(view) {
                return !this.group || this.group === '_all_' || (view.groups.filter((group) => { return group.name === this.group}).length > 0);
            },

            selectGroup(group) {
                this.group = group;

                if(this.$refs.viewGroupSelect) {
                    UIkit.dropdown(this.$refs.viewGroupSelect).hide();
                }
            },

            viewGroupIcon(group) {

                let icon = 'layers';

                Object.values(this.$unite.adminViews).forEach((view) => {
                    view.groups.forEach((g) => {
                        if(g.name === group && g.icon) {
                            icon = g.icon;
                        }
                    });
                });

                return icon;
            },

            viewRoute(view) {
              let route = '/' + (view.category === 'single_content' ? 'setting' : view.category);
              return route + '/' + (this.group || '_all_') + '/' + view.id;
            },

            viewIcon(view) {

                if(view.icon) {
                  return view.icon;
                }

                switch (view.category) {
                    case 'user': return 'users';
                    case 'single_content': return 'settings';
                    case 'dashboard': return 'grid';
                    default: return 'layers';
                }
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
                        if(groups.indexOf(group.name) === -1) {
                            groups.push(group.name);
                        }
                    });
                });
                return groups;
            },
            views() {
                return Object.values(this.$unite.adminViews).filter(view => { return ['dashboard', 'content', 'single_content', 'user'].indexOf(view.category) >= 0 }).filter(this.inGroup);
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

    .uk-icon.dropdown-icon {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      opacity: 0.75;
    }
  }
</style>