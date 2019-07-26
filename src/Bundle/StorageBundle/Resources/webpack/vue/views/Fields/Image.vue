<template>
    <div class="view-field view-field-image">
        <a :href="'#' + modalId" uk-toggle v-if="value" class="uk-inline-clip uk-box-shadow-small uk-box-shadow-hover-medium" :style="{ backgroundImage: `url(${thumbnail})` }"></a>
        <div class="uk-flex-top" :id="modalId" uk-modal>
            <div class="uk-modal-dialog uk-margin-auto-vertical">
                <button class="uk-modal-close-outside" type="button" uk-close></button>
                <img :src="fullImage" />
            </div>
        </div>
    </div>
</template>

<script>
    import BaseField from '../../../../../../CoreBundle/Resources/webpack/vue/views/Base/AbstractRowField';

    export default {
        extends: BaseField,
        methods: {

            /**
             * @inheritdoc
             */
            fieldQuery(identifier, field) {
                return BaseField.methods.fieldQuery(identifier) + ' { url }';
            },
        },
        computed: {

            modalId() {
                return 'modal-' + this._uid;
            },

            thumbnail() {
                return this.row.get(this.field.identifier, {}).url || null;
            },
            fullImage() {
                return this.row.get(this.field.identifier, {}).url || null;
            }
        }
    }
</script>

<style scoped lang="scss">
    .uk-modal-dialog {
        width: 900px;
        text-align: center;
        background: black;

        img {
            max-height: 80vh;
        }
    }

    .view-field-image {
        margin: -10px 0;
        height: 50px;
        width: 50px;
        text-align: center;

        &:last-child {
            margin-bottom: -10px;
        }

        .uk-inline-clip {
            width: 100%;
            height: 100%;
            border-radius: 3px;
            border: 1px solid white;
            margin: -1px 0 0 -1px;
            background: #989898;
            text-align: center;
            display: block;
            background-size: cover;
            background-position: center center;

            img {
                height: 100%;
                width: auto;
                max-width: none;
                transform: translateX(-50%);
                position: relative;
                left: 50%;
            }
        }
    }
</style>

<style lang="scss">

    @import "../../../../../node_modules/uikit/src/scss/variables";

    .unite-grid-view-item {
        > div.uk-card.uk-card-default {
            .view-field.view-field-image {
                width: 100%;
                max-width: none;
                height: auto;
                text-align: center;
                padding: 0;

                .uk-inline-clip {
                    width: auto;
                    border-radius: 0;
                    border: none;
                    box-shadow: none;
                    margin: 0;
                    height: 150px;
                    background: $global-secondary-background;
                    background-size: cover;
                    background-position: center center;
                }

                @media (max-width: $breakpoint-small) {
                    min-height: 120px;

                    .uk-inline-clip {
                        height: auto;
                        min-height: 120px;
                    }
                }
            }
        }
    }
</style>