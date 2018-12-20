<template>
    <div class="uk-margin">
        <div class="uk-form-controls">
            <label :for="input_id + '_auto'" class="uk-button" :class="{ 'uk-button-primary': auto, 'uk-button-default': !auto }">
                <input :id="input_id + '_auto'" type="checkbox" class="uk-checkbox" :name="name + '[auto]'" v-model="auto" />
                {{ auto ? autoLabel : autoLabelAlternative }}
                <span v-if="auto" v-html="feather.icons['check'].toSvg({ width: 20, height: 20 })"></span>
            </label>

            <div class="uk-form-custom">
                <input v-if="widgetType === 'Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType'" :disabled="auto" :id="input_id + '_text'" type="text" class="uk-input" :name="name + '[text]'" v-model="text" />
                <textarea v-else :disabled="auto" :id="input_id + '_text'" type="text" class="uk-textarea" :name="name + '[text]'" v-model="text"></textarea>
            </div>
        </div>
    </div>
</template>

<script>

    import feather from 'feather-icons';

    export default {
        data() {
            return {
                input_id: this.name.replace('[', '_').replace(']', '_') + 'url',
                text: this.textValue,
                auto: !!this.autoValue,
                feather: feather,
            }
        },

        props: [
            'autoLabel',
            'autoLabelAlternative',
            'name',
            'textValue',
            'autoValue',
            'widgetType',
        ],

        watch: {
            auto(val) {
                if(!val) {
                    this.text = '';
                }
            }
        }

    }
</script>

<style lang="scss" scoped>
    @import "../../sass/base/variables";

    .uk-form-controls {
        display: flex;
        align-items: flex-start;

        label.uk-button {
            position: relative;

            &.uk-button-default {
                background: white;
            }

            input {
                position: absolute;
                top: 0;
                left: 0;
                opacity: 0;
            }
        }

        .uk-form-custom {
            width: 100%;
            margin-left: 2px;

            textarea {
                resize: vertical;
            }
        }
    }
</style>
