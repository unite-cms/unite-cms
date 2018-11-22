<template>
    <div class="view-field view-field-actions fixed-width">
        <button v-if="actions.length > 0" class="uk-button uk-button-default actions-dropdown" type="button" v-html="feather.icons['more-horizontal'].toSvg()"></button>
        <div v-if="actions.length > 0" uk-dropdown="mode: click; pos: bottom-right; offset: 5;">
            <ul class="uk-nav uk-dropdown-nav">
                <li v-for="action in actions"><a :target="embedded ? '_blank' : '_self'" :href="action.url" :class="action.class ? action.class : ''">
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
                width: 0,
                minWidth: this.initialMinWidth || 50,
                feather: feather,
                allActions: [
                    { key: 'update', icon: feather.icons['edit-2'].toSvg({ width: 24, height: 16 }), name: 'Update' },
                    { key: 'delete', icon: feather.icons['trash-2'].toSvg({ width: 24, height: 16 }), name: 'Delete', class: 'uk-text-danger' },
                    { key: 'delete_definitely', icon: feather.icons['trash-2'].toSvg({ width: 24, height: 16 }), name: 'Delete definitely', class: 'uk-text-danger' },
                    { key: 'recover', icon: feather.icons['refresh-ccw'].toSvg({ width: 24, height: 16 }), name: 'Recover', class: 'uk-text-success' },
                    { key: 'revisions', icon: feather.icons['list'].toSvg({ width: 24, height: 16 }), name: 'Manage versions' },
                    { key: 'translations', icon: feather.icons['globe'].toSvg({ width: 24, height: 16 }), name: 'Manage translations' },
                ],
            }
        },
        props: ['row', 'urls', 'identifier', 'initialMinWidth', 'embedded'],
        mounted() {
            this.width = this.$el.childElementCount > 0 ? this.$el.children[0].offsetWidth : this.$el.offsetWidth;
            this.$on('minWidthChanged', (minWidth) => {
                this.minWidth = minWidth;
            });
        },
        watch: {
            width(width) {
                this.$emit('resized', {
                    identifier: this.identifier,
                    width: width
                });
            }
        },
        computed: {
            style() {
                return {
                    'min-width': this.minWidth + 'px'
                }
            },
            actions() {
                return this.allActions.filter(this.actionIsAllowed).map((action) => {
                    action.url = this.urlForAction(action, this.row);
                    return action;
                });
                return [
                    {url: 'foo', icon: feather.icons['edit'].toSvg({width: 24, height: 16}), name: 'Update content'}
                ];
            }
        },
        methods: {
            urlForAction(action, row){
                if(this.urls[action.key]) {
                    return this.urls[action.key].replace('__id__', row.id);
                }

                return null;
            },
            actionIsAllowed(action) {
                if(!this.row._actions) {
                    return false;
                }
                return !!this.row._actions[action.key];
            }
        }
    }
</script>

<style scoped lang="scss">

    .actions-dropdown {
        width: 65px;
    }

    .actions {
        display: inline-block;

        .uk-button:not(.uk-button-text):not(.uk-button-link) {
            border-color: transparent;
            background-color: transparent;
            box-shadow: none;
        }
    }
</style>