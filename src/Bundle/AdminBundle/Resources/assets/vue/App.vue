<template>
  <div class="uk-flex uk-flex-column">
    <slot name="mobile-navbar"><app-mobile-navbar v-if="hasUserInformation" /></slot>
    <slot name="navigation"><app-navigation v-if="hasUserInformation" /></slot>
    <section id="app-main" class="uk-background-muted uk-flex uk-flex-column">
      <alerts v-if="hasUserInformation" />
      <div class="uk-flex-1">
        <router-view></router-view>
      </div>
      <div class="uk-overlay-default uk-position-cover" v-if="hasUserInformation && !$unite.loaded">
        <div uk-spinner class="uk-position-center"></div>
      </div>
    </section>
  </div>
</template>
<script>
    import UserState from "./state/User";
    import AppNavigation from "./components/AppNavigation";
    import AppMobileNavbar from "./components/AppMobileNavbar";
    import Alerts from "./components/Alerts";

    export default {
        components: {Alerts, AppMobileNavbar, AppNavigation},
        computed: {
            hasUserInformation() {
                return UserState.hasUserInformation;
            }
        }
    }
</script>
