<template>
  <div class="uk-margin">
    <label class="uk-form-label" :for="domID">{{ name }}</label>
    <div class="uk-form-controls">
      <slot></slot>
      <p v-if="field.description" class="uk-text-meta uk-margin-small-top">{{ field.description }}</p>
      <div v-for="alert in alerts" class="uk-alert" :class="'uk-alert-' + alert.level">{{ alert.message }}</div>
    </div>
  </div>
</template>
<script>
    export default {
        props: {
            domID: String,
            field: Object,
            alerts: Array,
        },
        computed: {
            name() {
                return this.field.name.slice(0, 1).toUpperCase() + this.field.name.slice(1);
            },
        }
    }
</script>
