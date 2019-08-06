<template>
    <input class="uk-checkbox" type="checkbox" v-model="internalValue" :disabled="!editable">
</template>

<script>
    import BaseField from '../Base/AbstractRowField';
    
    export default {
        extends: BaseField,
        data() {
            return {
                internalValue: this.row.get(this.field.identifier, false),
            }
        },
        computed: {
            editable() {
                return this.field.settings ? this.field.settings.editable || false : false;
            }
        },
        watch: {
            internalValue(value) {
                let data = {};
                data[this.field.identifier] = value;
                this.config.updateRow(this.row, data);
            }
        }
    }
</script>
