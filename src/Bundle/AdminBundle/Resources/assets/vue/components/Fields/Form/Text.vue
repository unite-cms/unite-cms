<template>
  <div class="uk-margin">
    <label class="uk-form-label" :for="domID">{{ name }}</label>
    <div class="uk-form-controls">

      <div v-if="field.list_of">
        <div class="uk-margin-small" v-for="(row, key) in val">
          <div class="uk-inline">
            <a class="uk-form-icon uk-text-danger uk-form-icon-flip" @click.prevent="removeByKey(key)"><icon name="x" /></a>
            <input class="uk-input" :id="domID" type="text" v-model="val[key]" />
          </div>
        </div>
        <a @click.prevent="val.push('')" class="uk-icon-button uk-button-light uk-icon-button-small"><icon name="plus" /></a>
      </div>

      <input v-else class="uk-input" :id="domID" type="text" v-model="val" />

      <p v-if="field.description" class="uk-text-meta uk-margin-small-top">{{ field.description }}</p>
    </div>
  </div>
</template>
<script>
  import _abstract from "./_abstract";
  import Icon from "../../Icon";

  export default {
      extends: _abstract,
      components: { Icon },
      queryData(field) { return field.id },
      normalizeData(inputData, field) { return inputData; }
  }
</script>
<style scoped lang="scss">
  .uk-inline {
    display: block;
  }
</style>