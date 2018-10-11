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
                let value = this.row[this.identifier];
                return 'uk-label-' + (this.fieldConfig.places[value] ? this.fieldConfig.places[value].category : 'notice');
            },
            stateLabel() {
                let value = this.row[this.identifier];
                return this.fieldConfig.places[value] ? this.fieldConfig.places[value].label : value;
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
