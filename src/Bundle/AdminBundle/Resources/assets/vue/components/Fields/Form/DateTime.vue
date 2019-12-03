<template>
  <form-row :domID="domID" :field="field">
    <multi-field :field="field" :val="val" @addRow="val.push(today)" @removeRow="removeByKey" :span-row="false" v-slot:default="multiProps">
      <div class="uk-flex uk-flex-middle">

        <div class="uk-flex-1 date-picker-input">
          <date-picker :required="field.required" :id="domID" input-class="uk-input" :value="values[multiProps.rowKey || 0]" @input="setDate(arguments, multiProps.rowKey)" :language="$t('field.date')" format="d MMMM yyyy" />
        </div>

        <div class="date-picker-separator">:</div>

        <div>
          <input pattern="\d{1,2}" class="uk-input uk-form-width-xsmall uk-text-center" placeholder="00" :value="hours[multiProps.rowKey || 0]" @input="setTime(arguments, multiProps.rowKey, false)" />
        </div>

        <div class="date-picker-separator">:</div>

        <div>
          <input pattern="\d{1,2}" class="uk-input uk-form-width-xsmall uk-text-center" placeholder="00" :value="minutes[multiProps.rowKey || 0]" @input="setTime(arguments, multiProps.rowKey, true)" />
        </div>

        <div v-if="!field.list_of && !field.required && val" class="uk-margin-small-left">
          <a class="uk-icon-link uk-text-danger" @click.prevent="setValue(null)"><icon name="x" /></a>
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
    import Icon from "../../Icon";

    export default {

        // Static query methods for unite system.
        queryData(field, unite, depth) { return field.id },
        normalizeQueryData(queryData, field, unite) { return queryData; },
        normalizeMutationData(formData, field, unite) { return formData; },

        // Vue properties for this component.
        extends: _abstract,
        components: { DatePicker, MultiField, FormRow, Icon },
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
            setDate(args, key) {
                let date = (this.field.list_of ? this.val[key] : this.val) || this.today;
                let prevMinutes = date.getMinutes();
                let prevHours = date.getHours();
                date = new Date(args[0]);
                date.setMinutes(prevMinutes);
                date.setHours(prevHours);

                if(this.field.list_of) {
                    this.$set(this.val, key, date);
                } else {
                    this.val = date;
                }
            },
            setTime(args, key, minutes = true) {
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
