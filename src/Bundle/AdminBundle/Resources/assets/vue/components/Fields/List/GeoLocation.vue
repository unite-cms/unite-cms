<template>
  <div>
    <div v-for="value in values" class="uk-flex uk-flex-middle">
      <a v-if="value.latitude && value.longitude" @click.prevent="openAddress = value" class="uk-icon-button uk-icon-button-small uk-margin-small-right">
        <icon name="map" :width="12" :height="12" />
      </a>
      <span v-if="value.display_name" class="uk-text-nowrap">
        {{ value.display_name }}
        <template v-if="value.stairs_number"> / {{ value.stairs_number }}</template>
        <template v-if="value.door_number"> / {{ value.door_number }}</template>
      </span>
    </div>
    <modal v-if="openAddress" @hide="openAddress = null" :title="$t('field.geoLocation.modal.headline', openAddress)" :overflow-auto="false">
      <l-map style="height: 70vh; width: 100%" :center="[openAddress.latitude, openAddress.longitude]" :zoom="14">
        <l-tile-layer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" :attribution="mapAttribution" />
        <l-marker :lat-lng="[openAddress.latitude, openAddress.longitude]" />
      </l-map>
    </modal>
  </div>
</template>
<script>
  import _abstract from "./_abstract";
  import Icon from '../../Icon';
  import Modal from "../../Modal";

  import {LMap, LTileLayer, LMarker } from 'vue2-leaflet';
  import { Icon as LeafLetIcon } from 'leaflet'
  import 'leaflet/dist/leaflet.css'

  delete LeafLetIcon.Default.prototype._getIconUrl;

  LeafLetIcon.Default.mergeOptions({
      iconRetinaUrl: require('leaflet/dist/images/marker-icon-2x.png'),
      iconUrl: require('leaflet/dist/images/marker-icon.png'),
      shadowUrl: require('leaflet/dist/images/marker-shadow.png')
  });

  export default {
      extends: _abstract,
      components: { Icon, Modal, LMap, LTileLayer, LMarker },
      computed: {
          mapAttribution() {
              return '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';
          }
      },
      data() {
          return {
              openAddress: null,
          };
      }
  }
</script>
