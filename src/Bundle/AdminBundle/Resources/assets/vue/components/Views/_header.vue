<template>
  <div class="uk-flex uk-flex-middle uk-margin-bottom">
    <div class="uk-flex-1 uk-flex uk-flex-middle">
      <h2 class="uk-margin-remove">{{ title }}</h2>
      <a href="" class="uk-icon-button uk-margin-small-left" uk-tooltip :title="labelTitle" :class="{ 'uk-button-danger': deleted }" @click.prevent="toggleDeleted"><icon name="trash-2" /></a>
    </div>
    <router-link v-if="canCreate" :to="to('create')" class="uk-button uk-button-primary uk-margin-left"><icon name="plus" /> {{ labelCreate }}</router-link>
  </div>
</template>
<script>

  import Icon from '../../components/Icon';

  export default {
      components: { Icon },
      props: {
          deleted: Boolean,
          canCreate: Boolean,
          title: String,
          labelTitle: {
              type: String,
              default() { return this.$t('content.list.actions.toggle_deleted'); }
          },
          labelCreate: {
              type: String,
              default() { return this.$t('content.list.actions.create'); }
          }
      },
      methods: {
          to(action) {
              return this.$route.path + '/' + action;
          },
          toggleDeleted() {
              this.$emit('toggleDeleted');
          }
      }
  }
</script>
