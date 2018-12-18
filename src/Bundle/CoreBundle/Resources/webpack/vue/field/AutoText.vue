<template>
    <div class="uk-margin uk-grid-small" uk-grid>
        <div class="uk-width-3-2@2">
            <label class="uk-form-label" :for="input_id + '_text'">{{ label }}</label>
            <div class="uk-form-controls">
                <div class="url-control uk-inline">
                    <input :disabled="auto" :id="input_id + '_text'" type="text" class="uk-input" :name="name + '[text]'" :value="generatedText" v-on:input="text = $event.target.value" />
                </div>
            </div>
        </div>
        <div class="uk-width-1-4@s">
            <label class="uk-form-label" :for="input_id + '_auto'">{{ autoLabel }}</label>
            <div class="uk-form-controls">
                <input :id="input_id + '_auto'" type="checkbox" class="uk-checkbox" :name="name + '[auto]'" v-model="auto" />
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
                auto: this.autoValue,
            }
        },

        props: [
            'label',
            'autoLabel',
            'name',
            'textValue',
            'autoValue',
        ],

        computed: {
            generatedText() {
                return this.auto ? 'TODO: Ask server' : this.text;
            }
        }
    }
</script>

<style lang="scss">
    @import "../../sass/base/variables";
</style>
