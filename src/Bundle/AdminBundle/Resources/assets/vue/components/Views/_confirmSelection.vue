<template>
    <div class="uk-position-fixed uk-position-bottom uk-background-primary uk-dark uk-padding-small uk-text-center">
        <button class="uk-button uk-button-default uk-button-small" @click.prevent="$emit('confirmSelection')">{{ $t('content.list.selection.confirm', { count }) }}</button>
    </div>
</template>
<script>
    export default {
        props: {
            count: Number,
        }
    }
</script>