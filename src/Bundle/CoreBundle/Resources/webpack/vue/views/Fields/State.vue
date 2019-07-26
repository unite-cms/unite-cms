<template>
    <span class="uk-label" :class="stateCategory">{{ stateLabel }}</span>
</template>

<script>
    import BaseField from '../Base/AbstractRowField';

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
    .uk-label {
        white-space: nowrap;
    }
</style>
