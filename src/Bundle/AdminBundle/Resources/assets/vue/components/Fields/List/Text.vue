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
    export default {
        extends: _abstract,

        // static filter method
        filter(field, view, unite) { return _abstract.filter(field, view, unite); },
    }
</script>
