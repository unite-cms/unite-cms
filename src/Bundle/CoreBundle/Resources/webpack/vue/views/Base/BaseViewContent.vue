<template>
    <div class="uk-alert-warning" uk-alert><strong>{{ rows.length }} Rows</strong>. in abstract view. You must implement contentComponent to create a real view. </div>
</template>

<script>
    import Field from './Field.vue';

    export default {
        props: ['rows'],
        components: {
            'view-field': Field
        }
    }
</script>