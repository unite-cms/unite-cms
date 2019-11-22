<template>
  <form-row :domID="domID" :field="field">
    <multi-field :field="field" :val="val" @addRow="val.push(today)" @removeRow="removeByKey" :span-row="false" v-slot:default="multiProps">
      <div class="uk-flex uk-flex-middle">
        <div class="uk-flex-1 date-picker-input">
          <date-picker v-if="field.list_of" :id="domID" input-class="uk-input" v-model="val[multiProps.rowKey]" :language="$t('field.date')" format="d MMMM yyyy" />
          <date-picker v-else :id="domID" input-class="uk-input" v-model="val" :language="$t('field.date')" format="d MMMM yyyy" />
        </div>
        <div class="date-picker-separator">:</div>
        <div>
          <input pattern="\d{1,2}" class="uk-input uk-form-width-xsmall uk-text-center" placeholder="00" :value="hours[multiProps.rowKey || 0]" @input="setTime(multiProps.rowKey, arguments, false)" />
        </div>
        <div class="date-picker-separator">:</div>
        <div>
          <input pattern="\d{1,2}" class="uk-input uk-form-width-xsmall uk-text-center" placeholder="00" :value="minutes[multiProps.rowKey || 0]" @input="setTime(multiProps.rowKey, arguments, true)" />
        </div>
      </div>
    </multi-field>
  </form-row>
</template>
<script>
    import _abstract from "./_abstract";
    import FormRow from './_formRow';
    import MultiField from './_multiField';
    import DatePicker from 'vuejs-datepicker';

    export default {

        // Static query methods for unite system.
        queryData(field) { return field.id },
        normalizeData(inputData, field) { return inputData; },

        // Vue properties for this component.
        extends: _abstract,
        components: { DatePicker, MultiField, FormRow },
        computed: {
            today() {
                let today = new Date();
                today.setHours(0);
                today.setMinutes(0);
                today.setSeconds(0);
                return today;
            },
            hours() {
                return this.values.map((date) => {
                    return date ? (new Date(date).getHours() || null) : null;
                });
            },
            minutes() {
                return this.values.map((date) => {
                    return date ? (new Date(date).getMinutes() || null) : null;
                });
            }
        },
        methods: {
            setTime(key, args, minutes = true) {
                let date = (this.field.list_of ? this.val[key] : this.val) || this.today;
                date = new Date(date);

                if(minutes) {
                    date.setMinutes(args[0].target.value);
                } else {
                    date.setHours(args[0].target.value);
                }

                if(this.field.list_of) {
                    this.$set(this.val, key, date);
                } else {
                    this.val = date;
                }
            }
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