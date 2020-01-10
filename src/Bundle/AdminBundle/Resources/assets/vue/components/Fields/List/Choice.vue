<template>
    <div>
        <template v-for="value in values">
            <span class="uk-label uk-label-muted">{{ label(value) }}</span>
            <br v-if="!isLastValue(value)" />
        </template>
    </div>
</template>
<script>
    import _abstract from "./_abstract";
    import SelectInput from "../../Views/Filter/Input/SelectInput";
    import TextInput from "../../Views/Filter/Input/TextInput";

    export default {
        extends: _abstract,

        // static abstract filter method
        filter(field, view, unite) {

            // If this is an alias field
            if(field.id !== field.type) {
                return []
            }

            return [{
                searchable: field.show_in_list,
                id: field.id,
                label: field.name.slice(0, 1).toUpperCase() + field.name.slice(1),
                operators: ['EQ', 'NEQ'],
                input: TextInput
            }];
        },

        // static filter method
        filter(field, view, unite) {

            let enumType = unite.getRawType(field.returnType);

            if(!enumType) {
                return [];
            }

            return [{
                searchable: false,
                id: field.id,
                label: field.name.slice(0, 1).toUpperCase() + field.name.slice(1),
                input: SelectInput,
                operators: ['EQ', 'NEQ'],
                inputProps: {
                    options: enumType.enumValues.map((value) => {
                        return {label: value.description, value: value.name}
                    })
                },
            }];
        },

        methods: {
            label(value) {

                if(!value) {
                    return null;
                }

                let enumType = this.$unite.getRawType(this.field.returnType);

                if(!enumType) {
                    return null;
                }

                let labels = enumType.enumValues.filter((val) => {
                    return val.name === value;
                }).map((val) => {
                    return val.description || val.name;
                });

                return labels.length > 0 ? labels[0] : value;
            }
        }
    }
</script>
