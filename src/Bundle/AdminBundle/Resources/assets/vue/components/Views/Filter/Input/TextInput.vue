<template>
    <input class="uk-input" type="text" :placeholder="$t('content.list.filter.placeholder')" :value="value" @change="change" />
</template>

<script>
    export default {
        props: {
            value: String
        },
        methods: {
            change(event) {
                this.$emit('input', event.target.value);
            }
        }
    }
</script>
