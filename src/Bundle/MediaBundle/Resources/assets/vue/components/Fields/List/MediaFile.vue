<template>
  <div>
    <div class="uk-text-center" v-for="(value, delta) in values" :class="{'uk-margin-small-bottom': !isLastValue(value)}">

      <a :href="'#modal-media-file-image' + id + '_' + delta" uk-toggle v-if="value.preview">
        <img style="height: 50px; width: auto;" :src="value.preview" />
      </a>

      <div :id="'modal-media-file-image' + id + '_' + delta" class="uk-flex-top" uk-modal v-if="value.preview">
        <div class="uk-modal-dialog uk-width-auto uk-margin-auto-vertical">
          <button class="uk-modal-close-outside" type="button" uk-close></button>
          <img :src="value.preview" />
        </div>
      </div>

      <br v-if="value.preview" />
      <span class="uk-text-meta" v-if="value.filename">{{ value.filename }}</span>

    </div>
  </div>
</template>
<script>
  import _abstract from "../../../../../../../AdminBundle/Resources/assets/vue/components/Fields/List/_abstract";
  export default {
      extends: _abstract
  }
</script>
