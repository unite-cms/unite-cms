<template>
    <span @click="select">
        <button v-html="feather.icons[checked ? 'check-circle' : 'circle'].toSvg({ width: 24, height: 24 })"></button>
    </span>
</template>

<script>

    import BaseField from '../Base/AbstractRowField';
    import feather from 'feather-icons';
    import UIkit from 'uikit';

    export default {
        FIELD_WIDTH_COLLAPSED: true,
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
                        contentType: this.config.contentType,
                        view: this.config.view,
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
<style lang="scss" scoped>
    span {
        cursor: pointer;

        button {
            padding: 0;
            background: none;
            border: none;
            outline: none;
            height: 30px;
            width: 40px;
            line-height: 30px;
            display: block;
            text-align: center;
            margin: 0;
            cursor: pointer;
            opacity: 0.75;

            &:hover {
                opacity: 1;
            }
        }
    }
</style>
