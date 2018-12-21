<template>
    <div class="uk-margin">
        <div class="uk-form-controls">
            <label :for="input_id + '_auto'" class="uk-button uk-button-small" :class="{ 'uk-button-secondary': auto, 'uk-button-default': !auto }">
                <input :id="input_id + '_auto'" type="checkbox" class="uk-checkbox" :name="name + '[auto]'" v-model="auto" />
                {{ auto ? autoLabel : autoLabelAlternative }}
                <span v-if="auto" v-html="feather.icons['check'].toSvg({ width: 16, height: 16 })"></span>
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
                this.text = '';

                if(val) {
                    // TODO: Load text from server
                    setTimeout(() => {
                        this.text = '// TODO: Load text from server';
                    }, 300);
                }
            }
        }

    }
</script>

<style lang="scss" scoped>
    .uk-form-controls {
        label.uk-button {
            position: relative;
            padding: 0 5px 0 10px;
            flex-shrink: 0;
            margin-bottom: 5px;
            font-size: 12px;
            text-transform: uppercase;

            &.uk-button-default {
                background: white;
                padding: 0 10px;
                box-shadow: none;
                border-color: #e5e5e5;
                color: #999;
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

            textarea {
                resize: vertical;
            }
        }

        @media (min-width: 600px) {
            .uk-form-controls {
                display: flex;
                align-items: flex-start;

                label.uk-button {
                    margin: 4px 5px 0 0;
                }
            }
        }
    }
</style>
