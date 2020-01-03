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
        filter(field, view, unite) { return Object.assign(_abstract.filter(field, view, unite), {
            operators: ['EQ', 'NEQ', 'GT', 'LT', 'GTE', 'LTE'],
            cast: 'FLOAT',
            input: TextInput
        }); },
    }
</script>
