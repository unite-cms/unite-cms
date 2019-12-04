<template>
  <div>
    <div uk-alert :class="'uk-alert-' + alert.type" v-for="alert in (alerts !== null ? alerts : globalAlerts)">
      <a class="uk-alert-close" uk-close></a>
      <p>{{ alert.message }}</p>
    </div>
  </div>
</template>

<script>
    import Alerts from "../state/Alerts";

    export default {
        props: {
            alerts: {
                type: Array,
                default() { return null; }
            },
        },
        computed: {
            globalAlerts() {
                return Alerts.globalErrors;
            }
        }
    }
</script>

<style scoped lang="scss">
  .uk-alert {
    &:last-of-type {
      margin-bottom: 0;
    }
  }
</style>
