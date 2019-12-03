<template>
  <form-row :domID="domID" :field="field">
    <multi-field :field="field" :val="val" @addRow="addRow" @removeRow="removeByKey" v-slot:default="multiProps">
      <div class="uk-flex uk-flex-middle">
        <div class="uk-flex-1 geo-input">

          <google-places v-if="provider === 'google'" class="uk-search uk-search-default" v-bind="googleOptions()" @placechanged="setGoogleValue(arguments, multiProps.rowKey)">
            <span class="uk-search-icon-flip" uk-search-icon></span>
            <input class="uk-search-input" type="search" placeholder="" :value="geoValues[multiProps.rowKey || 0]" />
          </google-places>

          <places v-else class="uk-input" :options="algoliaOptions()" :required="field.non_null" :id="domID" :value="geoValues[multiProps.rowKey || 0]" @change="setAlgoliaValue(arguments, multiProps.rowKey)" />

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
  import GooglePlaces from 'vue-google-places/src/VueGooglePlaces'
  import Places from 'vue-places/src/Places';
  import {removeIntroSpecType} from "../../../plugins/unite";

  const GoogleTypeMap = {
      locality: 'CITY',
      sublocality: 'SUBLOCALITY',
      street_address: 'STREET_ADDRESS',
      route: 'STREET_ADDRESS',
  };
  const AlgoliaTypeMap = {
      city: 'CITY',
      address: 'STREET_ADDRESS',
  };

  const getProviderType = function(type, providerMap) {
      return Object.keys(providerMap).find(key => providerMap[key] === type);
  };

  export default {

      // Static query methods for unite system.
      queryData(field, unite, depth) { return `${ field.id } {
        provided_by,
        id,
        type,
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
      normalizeQueryData(queryData, field, unite) { return queryData; },
      normalizeMutationData(formData, field, unite) { return removeIntroSpecType(formData); },

      // Vue properties for this component.
      extends: _abstract,
      components: { MultiField, FormRow, Places, GooglePlaces },
      computed: {
          geoValues() {
              return this.values.map(val => val.display_name);
          },
          stairsNumber() {
              return this.values.map(val => val.stairs_number);
          },
          doorNumber() {
              return this.values.map(val => val.door_number);
          },
          provider() {
              if(this.field.config.google) {
                  return 'google';
              }

              return 'algolia';
          }
      },
      methods: {

          // Because of an internal issue with vue-places, this is not possible as computed value.
          algoliaOptions() {
              let config = {
                  language: this.$i18n.locale,
              };

              if(this.field.config.algolia) {
                  config = Object.assign(config, {
                      countries: this.field.config.algolia.countries,
                      type: getProviderType(this.field.config.algolia.type, AlgoliaTypeMap),
                      appId: this.field.config.algolia.appId,
                      apiKey: this.field.config.algolia.apiKey,
                  });
              }

              return config;
          },

          googleOptions() {
              let config = {};

              if(this.field.config.google) {
                  config = Object.assign(config, {
                      apiKey: this.field.config.google.apiKey,
                      country: this.field.config.google.countries ? this.field.config.google.countries.join(':') : null,
                      types: this.field.config.google.type ? `(${ getProviderType(this.field.config.google.type, AlgoliaTypeMap) })` : null,
                  });
              }

              return config;
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
              return value && value.type === 'STREET_ADDRESS';
          },

          shouldEditDoorNumber(value) {
              return value && value.type === 'STREET_ADDRESS';
          },

          setAlgoliaValue(args, key) {

              if(!args[0].hit) {
                  return;
              }

              let value = this.values[key || 0];
              let type = AlgoliaTypeMap[args[0].type] || null;

              if(type === 'STREET_ADDRESS' && !args[0].hit.is_highway) {
                  type = 'SUBLOCALITY';
              }

              let normalizedAddress = {
                  provided_by: 'Algolia',
                  id: args[0].hit.objectID,
                  type: type,
                  display_name: args[0].value,
                  latitude: args[0].latlng.lat,
                  longitude: args[0].latlng.lng,
                  bound_south: null,
                  bound_west: null,
                  bound_north: null,
                  bound_east: null,
                  street_number: null,
                  street_name: type === 'STREET_ADDRESS' ? args[0].name : null,
                  postal_code: type === 'STREET_ADDRESS' ? args[0].postcode : null,
                  locality: type === 'CITY' ? (args[0].hit.village ? args[0].administrative : args[0].name) : args[0].city,
                  sub_locality: args[0].hit.village ? args[0].hit.village[0] : null,
                  country_code: args[0].countryCode,
                  stairs_number: type === 'STREET_ADDRESS' ? value.stairs_number : null,
                  door_number: type === 'STREET_ADDRESS' ? value.door_number : null,
              };
              this.setValue([normalizedAddress], key);
          },

          setGoogleValue(args, key) {

              let value = this.values[key || 0];

              let type = GoogleTypeMap[args[0].place.types[0]] || null;

              let normalizedAddress = {
                  provided_by: 'Google',
                  id: args[0].place_id,
                  type: type,
                  display_name: args[0].formatted_address,
                  latitude: args[0].latitude,
                  longitude: args[0].longitude,
                  bound_south: null,
                  bound_west: null,
                  bound_north: null,
                  bound_east: null,
                  street_number: args[0].street_number,
                  street_name: args[0].route,
                  postal_code: args[0].postal_code,
                  locality: args[0].locality,
                  sub_locality: args[0].place.vicinity || null,
                  country_code: args[0].country_code,
                  stairs_number: type === 'STREET_ADDRESS' ? value.stairs_number : null,
                  door_number: type === 'STREET_ADDRESS' ? value.door_number : null,
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

    .uk-search-default {
      width: 100%;
    }
  }

  .geo-separator {
    padding: 0 0.5vw;
    min-width: 5px;
    width: 10px;
  }

</style>
