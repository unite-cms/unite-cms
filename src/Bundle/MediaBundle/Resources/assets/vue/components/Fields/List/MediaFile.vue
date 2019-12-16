<template>
  <div>
    <div class="uk-position-relative" v-for="value in values" :class="{'uk-margin-small-bottom': !isLastValue(value)}">

      <template v-if="value.preview">
        <div uk-lightbox>
          <a :href="value.preview" :data-caption="title(value)" class="uk-box-shadow-hover-large uk-border-rounded uk-overflow-hidden uk-background-cover uk-panel uk-flex uk-flex-center uk-flex-middle" :style="{ backgroundImage: `url(${value.preview})` }">
            <div class="uk-overlay uk-position-bottom">
              <p class="uk-text-meta uk-text-left">{{ title(value) }}</p>
            </div>
          </a>
        </div>

        <div class="uk-position-top-right">
          <a v-if="value.url" class="uk-button-light uk-icon-button uk-icon-button-small" :href="value.url" target="_blank"><icon name="external-link" :width="12" :height="12" /></a>
        </div>
      </template>

      <template v-else>
        <span class="uk-text-meta">{{ title(value) }}</span>
        <a v-if="value.url" class="uk-button-light uk-icon-button uk-icon-button-small" :href="value.url" target="_blank"><icon name="external-link" :width="12" :height="12" /></a>
      </template>

    </div>
  </div>
</template>
<script>

  import _abstract from "@unite/admin/Resources/assets/vue/components/Fields/List/_abstract";
  import Icon from "@unite/admin/Resources/assets/vue/components/Icon";

  export default {
      extends: _abstract,
      components: { Icon },
      methods: {
        title(value) {
          return value.filename || value.id || '';
        }
      }
  }
</script>
<style scoped lang="scss">

  .uk-position-top-right {
    padding: 5px;
    z-index: 1;
  }

  .uk-border-rounded {
    background-color: black;
    height: 90px;
    min-width: 160px;

    .uk-overlay {
      padding: 8px;
    }

    .uk-text-meta {
      color: white;
      font-size: 0.75rem;
      line-height: 100%;
    }
  }
</style>