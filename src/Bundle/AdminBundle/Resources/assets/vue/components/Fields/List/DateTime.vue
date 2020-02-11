<template>
  <div>
    <template v-for="value in values">
      <span class="uk-text-nowrap">{{ formatDate(value) }}</span>
      <br v-if="!isLastValue(value)" />
    </template>
  </div>
</template>
<script>
  import _abstract from "./_abstract";
  import moment from 'moment';

  export default {
      extends: _abstract,
      computed: {
        format() {
          return this.field.config.format ?
                  this.field.config.format :
                  (this.field.fieldType === 'dateTime' ? 'L LT' : 'L')
        }
      },
      methods: {
        formatDate(value) {

          if(this.field.config.format === 'ago') {
            return moment(value).locale(this.$i18n.locale).fromNow();
          }

          if(this.field.config.format === 'age') {
            return moment().diff(value, 'years', false);
          }

          if(this.field.config.format) {
            this.field.config.format = this.field.config.format.replace('ago', `[${moment(value).locale(this.$i18n.locale).fromNow()}]`);
            this.field.config.format = this.field.config.format.replace('age', `[${moment().diff(value, 'years', false)}]`);
          }

          return moment(value).locale(this.$i18n.locale).format(this.format);
        },
      }
  }
</script>
