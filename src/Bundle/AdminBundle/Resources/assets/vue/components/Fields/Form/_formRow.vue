<template>
  <div class="uk-margin">
    <label class="uk-form-label" :for="domID">{{ name }}<span class="uk-text-danger" v-if="field.required"> *</span></label>
    <div class="uk-form-controls">
      <slot></slot>
      <p v-if="field.description" class="uk-text-meta uk-margin-small-top">{{ field.description }}</p>
      <alerts :alerts="alerts" />
    </div>
  </div>
</template>
<script>

    import Alerts from '../../Alerts';

    export default {
        components: {Alerts},
        props: {
            domID: String,
            field: Object,
            alerts: Array,
        },
        computed: {
            name() {
                return this.field.name.slice(0, 1).toUpperCase() + this.field.name.slice(1);
            }
        }
    }
</script>
