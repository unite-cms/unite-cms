<template>
  <form-row :domID="domID" :field="field">
    <multi-field :field="field" :val="val" @addRow="addRow" @removeRow="removeByKey" v-slot:default="multiProps">
      <div class="uk-flex uk-flex-middle">
        <div class="uk-flex-1 geo-input">
          <places class="uk-input" :options="algoliaOptions()" :required="field.non_null" :id="domID" :value="geoValues[multiProps.rowKey || 0]" @change="setGeoValue(arguments, multiProps.rowKey)" />
        </div>

        <template v-if="shouldEditStairsNumber(values[multiProps.rowKey || 0])">
          <div class="geo-separator">/</div>
          <div>
            <input class="uk-input uk-form-width-small" :placeholder="$t('field.geoLocation.placeholder.stairs_number')" :value="stairsNumber[multiProps.rowKey || 0]" @input="setStairsNumber(arguments, multiProps.rowKey, false)" />
          </div>
        </template>

        <template v-if="shouldEditDoorNumber(values[multiProps.rowKey || 0])">
          <div class="geo-separator">/</div>
          <div>
            <input class="uk-input uk-form-width-small" :placeholder="$t('field.geoLocation.placeholder.door_number')" :value="doorNumber[multiProps.rowKey || 0]" @input="setDoorNumber(arguments, multiProps.rowKey, true)" />
          </div>
        </template>

      </div>
    </multi-field>
  </form-row>
</template>
<script>
  import _abstract from "./_abstract";
  import FormRow from './_formRow';
  import MultiField from './_multiField';
  import Places from 'vue-places/src/Places';
  import { removeIntroSpecType } from '../../../plugins/unite';

  export default {

      // Static query methods for unite system.
      queryData(field) { return `${ field.id } {
        provided_by,
        id,
        category,
        display_name,
        latitude,
        longitude,
        bound_south,
        bound_west,
        bound_north,
        bound_east,
        street_number,
        street_name,
        postal_code,
        locality,
        sub_locality,
        country_code,
        stairs_number,
        door_number
      }`; },
      normalizeData(inputData, field, unite) {
          return removeIntroSpecType(inputData);
      },

      // Vue properties for this component.
      extends: _abstract,
      components: { MultiField, FormRow, Places },
      computed: {
          geoValues() {
              return this.values.map(val => val.display_name);
          },
          stairsNumber() {
              return this.values.map(val => val.stairs_number);
          },
          doorNumber() {
              return this.values.map(val => val.door_number);
          }
      },
      methods: {

          // Because of an internal issue with vue-places, this is not possible as computed value.
          algoliaOptions() {
              return {
                  language: this.$i18n.locale,
                  countries: this.field.config.countries,
                  type: this.field.config.type,
                  appId: this.field.config.appId,
                  apiKey: this.field.config.apiKey,

              };
          },
          addRow() {
              this.val.push({
                  provided_by: null,
                  id: null,
                  category: null,
                  display_name: null,
                  latitude: null,
                  longitude: null,
                  bound_south: null,
                  bound_west: null,
                  bound_north: null,
                  bound_east: null,
                  street_number: null,
                  street_name: null,
                  postal_code: null,
                  locality: null,
                  sub_locality: null,
                  country_code: null,
                  stairs_number: null,
                  door_number: null,
              });
          },

          shouldEditStairsNumber(value) {
              return value && value.category === 'address';
          },

          shouldEditDoorNumber(value) {
              return value && value.category === 'address';
          },

          setGeoValue(args, key) {

              if(!args[0].hit) {
                  return;
              }

              let value = this.values[key || 0];
              let normalizedAddress = {
                  provided_by: 'Algolia',
                  id: args[0].hit.objectID,
                  category: args[0].type,
                  display_name: args[0].value,
                  latitude: args[0].latlng.lat,
                  longitude: args[0].latlng.lng,
                  bound_south: null,
                  bound_west: null,
                  bound_north: null,
                  bound_east: null,
                  street_number: null,
                  street_name: args[0].type === 'address' ? args[0].name : null,
                  postal_code: args[0].type === 'address' ? args[0].postcode : null,
                  locality: args[0].type === 'city' ? (args[0].hit.village ? args[0].administrative : args[0].name) : args[0].city,
                  sub_locality: args[0].hit.village ? args[0].hit.village[0] : null,
                  country_code: args[0].countryCode,
                  stairs_number: args[0].type === 'address' ? value.stairs_number : null,
                  door_number: args[0].type === 'address' ? value.door_number : null,
              };
              this.setValue([normalizedAddress], key);
          },
          setStairsNumber(args, key) {
              let value = this.values[key || 0] || {};
              value.stairs_number = args[0].target.value;
              this.setValue([value], key);
          },
          setDoorNumber(args, key) {
              let value = this.values[key || 0] || {};
              value.door_number = args[0].target.value;
              this.setValue([value], key);
          },
      }
  }
</script>
<style scoped lang="scss">
  .geo-input {
    max-width: 500px;
  }

  .geo-separator {
    padding: 0 0.5vw;
    min-width: 5px;
    width: 10px;
  }

</style>
