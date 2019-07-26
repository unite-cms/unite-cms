<template>
    <button v-on:click.prevent="select" v-html="feather.icons[checked ? 'check-circle' : 'circle'].toSvg({ width: 24, height: 24 })"></button>
</template>

<script>

    import BaseField from '../Base/AbstractRowField';
    import feather from 'feather-icons';
    import UIkit from 'uikit';

    export default {
        extends: BaseField,
        data() {
            return {
                checked: false,
                feather: feather
            }
        },
        mounted: function(){
            let findModal = (element) => {
                if(element.hasAttribute('uk-modal')) {
                    return element;
                }
                if(!element.parentElement) {
                    return null;
                }
                return findModal(element.parentElement);
            };
            let modal = findModal(this.$el);
            if(modal) {
                UIkit.util.on(modal, 'beforeshow', () => {
                    this.checked = false;
                });
            }
        },
        methods: {
            select() {

                // For the moment, we only support single element selection.
                if(!this.checked) {
                    this.checked = true;

                    window.UniteCMSEventBus.$emit('contentSelected', [ {
                        contentType: this.settings.contentType,
                        view: this.settings.view,
                        row: this.row
                    } ]);
                }
                else {
                    this.checked = false;
                }
            }
        }

    }
</script>

<style scoped lang="scss">
    button {
        display: inline-block;
        cursor: pointer;
        opacity: 0.75;
        width: 30px;

        &:hover {
            opacity: 1;
        }
    }
</style>
