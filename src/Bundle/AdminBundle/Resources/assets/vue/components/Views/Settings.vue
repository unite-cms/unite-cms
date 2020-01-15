<template>
  <section class="uk-position-relative">
    <loading-overlay v-if="$apollo.loading" />
  </section>
</template>

<script>
    import _abstract from "./_abstract";
    import LoadingOverlay from "../LoadingOverlay";

    export default {
        extends: _abstract,
        components: { LoadingOverlay }
    }
</script>
