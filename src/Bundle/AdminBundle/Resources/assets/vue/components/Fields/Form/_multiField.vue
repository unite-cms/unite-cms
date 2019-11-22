<template>
  <div v-if="field.list_of">
    <div class="uk-margin-small" v-for="(row, key) in val">
      <div class="uk-inline" :class="{ 'span-row' : spanRow, 'remove-button-outside' : removeButtonOutside }">
        <slot :row-value="row" :row-key="key"></slot>
        <a class="uk-form-icon uk-text-danger" :class="{ 'uk-form-icon-flip' : removeButtonFlip }" @click.prevent="removeRow(key)"><icon name="x" /></a>
      </div>
    </div>
    <a @click.prevent="addRow" class="uk-icon-button uk-button-light uk-icon-button-small"><icon name="plus" /></a>
  </div>
  <div v-else>
    <slot :row-value="val" :row-key="null"></slot>
  </div>
</template>
<script>
    import Icon from "../../Icon";
    export default {
        components: { Icon },
        props: {
            val: {},
            spanRow: {
                type: Boolean,
                default: true
            },
            removeButtonOutside: {
                type: Boolean,
                default: true
            },
            removeButtonFlip: {
                type: Boolean,
                default: true
            },
            field: Object
        },
        methods: {
            addRow() {
                this.$emit('addRow');
            },
            removeRow(key) {
                this.$emit('removeRow', key);
            }
        }
    }
</script>
<style scoped lang="scss">
  .uk-inline {
    &.span-row {
      display: block;
    }

    &.remove-button-outside {
      padding-right: 40px;
    }
  }
</style>
