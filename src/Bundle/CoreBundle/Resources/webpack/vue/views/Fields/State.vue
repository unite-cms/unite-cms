<template>
    <span class="uk-label" :class="stateCategory">{{ stateLabel }}</span>
</template>

<script>
    import BaseField from '../Base/AbstractRowField';

    export default {
        extends: BaseField,
        computed: {
            stateCategory() {
                return 'uk-label-' + (this.field.settings.places[this.value] ? this.field.settings.places[this.value].category : 'notice');
            },
            stateLabel() {
                return this.field.settings.places[this.value] ? this.field.settings.places[this.value].label : this.value;
            }
        }
    }
</script>

<style scoped lang="scss">
    .uk-label {
        white-space: nowrap;
    }
</style>
