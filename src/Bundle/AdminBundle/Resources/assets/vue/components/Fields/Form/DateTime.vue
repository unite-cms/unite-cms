<template>
  <form-row :domID="domID" :field="field" :alerts="violations">
    <multi-field :field="field" :val="val" @addRow="val.push(null)" @removeRow="removeByKey" :span-row="false" v-slot:default="multiProps">
      <div class="uk-flex">
        <div class="uk-margin-small-right">
          <input style="min-width: 150px;" type="date" class="uk-input" :required="field.required" :id="domID" :value="getDate(multiProps.rowKey || 0)" @input="setDate(multiProps.rowKey || 0, arguments)" />
        </div>
        <div v-if="field.fieldType === 'dateTime' && getDate(multiProps.rowKey || 0)">
          <input style="min-width: 100px;" type="time" class="uk-input" :required="field.required" :id="domID" :value="getTime(multiProps.rowKey || 0)" @input="setTime(multiProps.rowKey || 0, arguments)" />
        </div>
      </div>
    </multi-field>
  </form-row>
</template>
<script>
    import _abstract from "./_abstract";
    import FormRow from './_formRow';
    import MultiField from './_multiField';

    import moment from 'moment';

    const normalizeDateTime = function(value, field){

      if(!value) {
        value = field.config.default;
      }

      if(!value) {
        return null;
      }

      let format = 'YYYY-MM-DD';
      if(field.type === 'dateTime') {
        format += 'THH:mm';
      }

      if(Array.isArray(value)) {
        return value.map(d => moment(d, format));
      } else {
        return moment(value, format);
      }
    };

    export default {

        // Static query methods for unite system.
        queryData(field, unite, depth) { return field.id },
        normalizeQueryData(queryData, field, unite) {
          return normalizeDateTime(queryData, field);
        },
        normalizeMutationData(formData, field, unite) {
          if(!formData) { return null; }

          let format = 'YYYY-MM-DD';
          if(field.type === 'dateTime') {
            format += 'THH:mm';
          }

          if(Array.isArray(formData)) {
            return formData.map(d => d.format(format));
          } else {
            return formData.format(format);
          }
        },

        data() {
          let val = this.value;
          val = val || (this.field.list_of ? [] : null);

          return {
            val
          }
        },

        // Vue properties for this component.
        extends: _abstract,
        components: { MultiField, FormRow },
        methods: {
          getDate(delta) {
            return this.values[delta] ? this.values[delta].format('YYYY-MM-DD') : null;
          },
          getTime(delta) {
            return this.values[delta] ? this.values[delta].format('HH:mm') : null;
          },

          setFullDate(delta, date = null, time = null) {
            this.setValue(date ? [moment([date, time].join(' '), 'YYYY-MM-DD HH:mm')] : null, delta);
          },

          setDate(delta, args) {
            this.setFullDate(delta, args[0].target.value, this.getTime(delta));
          },
          setTime(delta, args) {
            this.setFullDate(delta, this.getDate(delta), args[0].target.value);
          },
        }
    }
</script>
<style scoped lang="scss">
  .date-picker-input {
    max-width: 200px;
  }

  .date-picker-separator {
    padding: 0 0.5vw;
    min-width: 5px;
    width: 10px;
    text-align: center;
  }

</style>
