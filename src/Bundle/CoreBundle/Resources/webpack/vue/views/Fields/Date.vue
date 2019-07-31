<template>
    <time v-if="mode === 'date'" class="uk-text-meta">{{ value|date }}</time>
    <time v-else-if="mode === 'datetime'" class="uk-text-meta">{{ value|dateFull }}</time>
    <time v-else class="uk-text-meta" :title="value|dateFull" uk-tooltip>{{ value|dateFromNow }}</time>
</template>

<script>
    import BaseField from '../Base/AbstractRowField';

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
        extends: BaseField,

        methods: {
            /**
             * @inheritdoc
             */
            filterQuery(identifier, field) {
                return null;
            },
        }
    }
</script>

<style scoped lang="scss">
    time {
        cursor: pointer;
        white-space: nowrap;
    }
</style>
