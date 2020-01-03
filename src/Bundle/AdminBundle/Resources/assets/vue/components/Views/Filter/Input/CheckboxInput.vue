<template>
    <div class="uk-margin uk-grid-small uk-child-width-auto uk-grid uk-height-1-1 uk-flex uk-flex-middle">
        <label><input class="uk-radio" type="radio" name="checkbox-radios" :checked="value === 'true'" @change="change('true')"> {{ $t('content.list.filter.checkbox_true') }}</label>
        <label><input class="uk-radio" type="radio" name="checkbox-radios" :checked="value === 'false'" @change="change('false')"> {{ $t('content.list.filter.checkbox_false') }}</label>
        <label><input class="uk-radio" type="radio" name="checkbox-radios" :checked="!value" @change="change(null)"> {{ $t('content.list.filter.checkbox_null') }}</label>
    </div>
</template>

<script>
    export default {
        props: {
            value: String
        },
        methods: {
            change(value) {
                this.$emit('input', value);
            }
        }
    }
</script>
