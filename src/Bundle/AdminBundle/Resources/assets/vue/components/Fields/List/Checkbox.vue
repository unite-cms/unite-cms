<template>
    <div>
        <template v-for="value in values">
            <icon :name="value ? 'check-circle' : 'x-circle'" :class="value ? 'uk-text-success' : 'uk-text-danger'" />
            <br v-if="!isLastValue(value)" />
        </template>
    </div>
</template>
<script>
    import _abstract from "./_abstract";
    import Icon from "../../Icon";
    import CheckboxInput from "../../Views/Filter/Input/CheckboxInput";

    export default {
        extends: _abstract,

        // static filter method
        filter(field, view, unite) {
            return {
                searchable: false,
                id: field.id,
                label: field.name.slice(0, 1).toUpperCase() + field.name.slice(1),
                operators: ['IS'],
                cast: 'BOOLEAN',
                input: CheckboxInput
            };
        },

        components: {Icon}
    }
</script>
