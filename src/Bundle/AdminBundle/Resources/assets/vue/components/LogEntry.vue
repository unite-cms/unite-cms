<template>
  <code class="log-entry uk-text-small uk-flex uk-flex-middle">
    <span class="number">{{ number }}:</span>
    <span class="uk-label" :class="labelClass">{{ log.level }}</span>
    <span class="uk-label username">{{ log.username }}</span>
    <span class="uk-label created">{{ $d(new Date(log.created), 'full') }}</span>
    <span>{{ log.message }}</span>
  </code>
</template>

<script>
    export default {
        props: {
            number: Number,
            log: Object
        },
        computed: {
            labelClass() {
                switch (this.log.level) {
                  case "WARNING": return 'uk-label-warning';
                  case "ERROR":
                  case "CRITICAL":
                  case "EMERGENCY":
                      return 'uk-label-danger';
                  default: return null;
                }
            }
        },
    }
</script>

<style scoped lang="scss">
  .log-entry {
    background: none;
    color: white;
    height: 25px;
    padding: 0;

    .number {
      font-size: 0.7rem;
      width: 35px;
      text-align: right;
      padding-right: 5px;
      opacity: 0.75;
      margin-right: 5px;
    }

    .uk-label {
      font-size: 0.7rem;
      padding: 0 5px;
      margin-right: 5px;
    }

    .username, .created {
      background: rgba(255,255,255, 0.25);
    }
  }
</style>
