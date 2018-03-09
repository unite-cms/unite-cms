<template>
    <div class="uk-placeholder uk-padding-small">
        <div v-if="prototype" v-html="prototype"></div>
        <button uk-close class="uk-modal-close-default" v-on:click.prevent="removeRow"></button>
    </div>
</template>

<script>
    export default {
        data() {
            return {};
        },
        props: [
            'delta',
            'prototype'
        ],
        methods: {
            removeRow() {
                this.$emit('remove', { delta: this.delta});
            }
        }
    };
</script>

<style lang="scss" scoped>
</style>