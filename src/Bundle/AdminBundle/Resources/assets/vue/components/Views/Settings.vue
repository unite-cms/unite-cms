<template>
  <section class="uk-position-relative">
    <div class="uk-placeholder" style="margin: 20px;">TODO: Implement</div>
    <div class="uk-overlay-default uk-position-cover" v-if="$apollo.loading">
      <div uk-spinner class="uk-position-center"></div>
    </div>
  </section>
</template>

<script>
    import _abstract from "./_abstract";

    export default {
        extends: _abstract
    }
</script>
