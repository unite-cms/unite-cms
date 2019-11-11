<template>
  <section id="app-navbar-mobile" class="uk-hidden@m uk-navbar-container" uk-navbar>
    <div class="uk-navbar-left">
      <a class="uk-navbar-toggle" uk-toggle="target: #app-navigation" href="#">
        <span uk-navbar-toggle-icon></span> <span class="uk-margin-small-left">Menu</span>
      </a>
    </div>
  </section>
</template>

<script>
    export default {
        name: "AppMobileNavbar",
    }
</script>
