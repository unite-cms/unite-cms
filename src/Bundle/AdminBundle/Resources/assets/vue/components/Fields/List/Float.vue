<template>
    <div>
        <template v-for="value in values">
            {{ value }}
            <br v-if="!isLastValue(value)" />
        </template>
    </div>
</template>
<script>
    import _abstract from "./_abstract";
    import TextInput from "../../Views/Filter/Input/TextInput";
    export default {
        extends: _abstract,

        // static filter method
        filter(field, view, unite) {
            return _abstract.filter(field, view, unite).map((filter) => {
                return Object.assign(filter, {
                    searchable: false,
                    operators: ['EQ', 'NEQ', 'GT', 'LT', 'GTE', 'LTE'],
                    input: TextInput
                })
            });
        },
    }
</script>
