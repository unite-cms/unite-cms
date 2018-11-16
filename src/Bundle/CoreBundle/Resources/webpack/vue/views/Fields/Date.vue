<template>
    <div class="view-field view-field-date fixed-width">
        <time v-if="mode === 'date'" class="uk-text-meta">{{ value|date }}</time>
        <time v-else-if="mode === 'datetime'" class="uk-text-meta">{{ value|dateFull }}</time>
        <time v-else class="uk-text-meta" :title="value|dateFull" uk-tooltip>{{ value|dateFromNow }}</time>
    </div>
</template>

<script>
    import BaseField from '../Base/BaseField.vue';

    export default {
        data() {
          let mode = 'fromnow';

          if(typeof date === 'string' && date.indexOf('-') > 0) {
              mode = 'date';

              if(date.indexOf(':') > 0) {
                  mode = 'datetime';
              }
          }

          return {
              mode: mode,
          }
        },
        extends: BaseField
    }
</script>

<style scoped lang="scss">
    .view-field-date {
        time {
            cursor: pointer;
            white-space: nowrap;
        }
    }
</style>
