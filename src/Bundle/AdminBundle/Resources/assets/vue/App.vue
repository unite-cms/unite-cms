<template>
  <div class="uk-height-1-1 uk-flex uk-flex-column">
    <slot name="mobile-navbar"><app-mobile-navbar v-if="hasUserInformation" /></slot>
    <slot name="navigation"><app-navigation v-if="hasUserInformation" /></slot>
    <section id="app-main" class="uk-height-1-1">
      <alerts />
      <router-view></router-view>
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
