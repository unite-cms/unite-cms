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

    export default {
        extends: _abstract,

        // static filter method
        filter(field, view, unite) {
            return {
                searchable: false,
                type: 'checkbox',
                id: field.id,
                label: field.name.slice(0, 1).toUpperCase() + field.name.slice(1),
            };
        },

        components: {Icon}
    }
</script>
