<template>
    <div class="actions">
        <button class="uk-button uk-button-default actions-dropdown" type="button" v-html="feather.icons['more-horizontal'].toSvg()"></button>
        <div uk-dropdown="mode: click; pos: bottom-right; offset: 5">
            <ul class="uk-nav uk-dropdown-nav">
                <li v-for="action in actions"><a :href="action.url" :class="action.class ? action.class : ''">
                    <span class="uk-margin-small-right" v-html="action.icon"></span>{{ action.name }}</a>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>

    import feather from 'feather-icons';

    export default {
        data() {
            return {
                feather: feather,
            }
        },
        computed: {
            actions() {
                return [
                    { url: 'foo', icon: feather.icons['edit'].toSvg({ width: 24, height: 16 }), name: 'Update content' }
                ];
            }
        }
    }
</script>

<style scoped lang="scss">
    .actions {
        display: inline-block;

        .uk-button:not(.uk-button-text):not(.uk-button-link) {
            border: none;
            background: none;
            box-shadow: none;
        }
    }
</style>