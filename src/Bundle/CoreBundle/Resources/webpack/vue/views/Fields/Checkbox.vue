<template>
    <input class="uk-checkbox" type="checkbox" :checked="value" readonly>
</template>

<script>
    import BaseField from '../Base/AbstractRowField';
    
    export default {
        extends: BaseField,
    }
</script>
