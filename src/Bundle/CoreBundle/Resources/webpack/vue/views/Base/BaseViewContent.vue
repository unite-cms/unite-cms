<template>
    <div class="uk-alert-warning" uk-alert><strong>{{ rows.length }} Rows</strong>. in abstract view. You must implement contentComponent to create a real view. </div>
</template>

<script>
    export default {
        props: [
            'fields',
            'sort',
            'rows',
            'selectable',
            'updateable',
            'urls',
        ]
    }
</script>
