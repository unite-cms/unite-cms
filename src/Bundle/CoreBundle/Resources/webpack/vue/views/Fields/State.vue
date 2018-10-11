<template>
    <div class="view-field view-field-state">
        <span class="uk-label" :class="stateCategory">{{ stateLabel }}</span>
    </div>
</template>

<script>
    import BaseField from '../Base/BaseField.vue';

    export default {
        extends: BaseField,
        computed: {
            stateCategory() {
                return 'uk-label-' + (this.settings.places[this.value] ? this.settings.places[this.value].category : 'notice');
            },
            stateLabel() {
                return this.settings.places[this.value] ? this.settings.places[this.value].label : value;
            }
        }
    }
</script>

<style scoped lang="scss">
    .view-field-state {
        display: inline-block;

        .uk-label {
            white-space: nowrap;
        }
    }
</style>
