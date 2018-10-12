<template>
    <div class="uk-inline uk-width-1-1">
        <a class="uk-form-icon uk-form-icon-flip" v-on:click="copyKey" v-html="feather.icons['copy'].toSvg({ width: 16, height: 16 })"></a>
        <input readonly="readonly" class="uk-input uk-width-1-1" :value="value">
    </div>
</template>

<script>

    import feather from 'feather-icons';
    import UIkit from 'uikit';

    export default {
        data() {
            return {
                value: this.token,
                feather: feather
            };
        },
        props: [
            'token',
            'successMessage'
        ],
        methods: {
            copyKey: function() {

                let input = this.$el.getElementsByTagName('input')[0];
                input.select();
                document.execCommand('Copy');
                window.getSelection().removeAllRanges();

                UIkit.notification({
                    message: '<div class="uk-inline uk-text-nowrap">' + feather.icons['copy'].toSvg({ width: 24, height: 24 }) + ' ' + this.successMessage + '</div>',
                    status: 'primary',
                    pos: 'top-center',
                    timeout: 1000
                });

            }
        }
    };
</script>

<style lang="scss">
    unite-cms-core-api-token-field {
        display: block;
    }
</style>