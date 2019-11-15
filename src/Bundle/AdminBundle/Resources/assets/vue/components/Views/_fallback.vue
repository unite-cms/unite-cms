<template>
  <div class="uk-alert-warning" uk-alert>{{ $t('content.list.view.fallback_warning', view) }}</div>
</template>
<script>
    import _abstract from "./_abstract";

    export default {
        extends: _abstract
    }
</script>
