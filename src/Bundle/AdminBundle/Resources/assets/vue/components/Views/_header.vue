<template>
  <div class="uk-flex uk-flex-middle uk-margin-bottom">
    <div class="uk-flex-1 uk-flex uk-flex-middle">
      <h2 class="uk-margin-remove">{{ title }}</h2>

      <ul class="uk-subnav uk-subnav-divider uk-margin-left" uk-margin>
        <li :class="{'uk-active' : !deleted }"><a href="#" @click.prevent="toggleDeleted">{{ $t('content.list.deleted.active') }}</a></li>
        <li :class="{'uk-active' : deleted}"><a href="#" @click.prevent="toggleDeleted" :class="{ 'uk-text-danger' : deleted }"><icon class="fix-line-height" name="trash-2" /> {{ $t('content.list.deleted.deleted') }}</a></li>
      </ul>
    </div>
    <router-link v-if="canCreate" :to="to('create')" class="uk-button uk-button-primary uk-margin-left"><icon class="fix-line-height" name="plus" /> {{ labelCreate }}</router-link>
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
