<template>
  <ul class="uk-iconnav">
    <template v-if="row._meta.permissions.mutation">
      <li v-if="!deleted && is_granted('update')"><router-link uk-tooltip :to="to('update')" :title="$t('content.list.actions.update')"><icon name="edit-2" /></router-link></li>
      <li v-if="!deleted && is_granted('update')"><router-link uk-tooltip :to="to('revert')" :title="$t('content.list.actions.revert')"><icon name="rotate-ccw" /></router-link></li>
      <li v-if="!deleted && is_granted('update')"><router-link uk-tooltip :to="to('translate')" :title="$t('content.list.actions.translate')"><icon name="globe" /></router-link></li>
      <li v-if="!deleted && is_granted('delete')"><router-link uk-tooltip :to="to('delete')" :title="$t('content.list.actions.delete')" class="uk-text-danger"><icon name="trash-2" /></router-link></li>

      <li v-if="deleted && is_granted('update')"><router-link uk-tooltip :to="to('recover')" :title="$t('content.list.actions.recover')" class="uk-text-success"><icon name="rewind" /></router-link></li>
      <li v-if="deleted && is_granted('permanent_delete')"><router-link uk-tooltip :to="to('permanent_delete')" :title="$t('content.list.actions.permanent_delete')" class="uk-text-danger"><icon name="x-circle" /></router-link></li>
    </template>
  </ul>
</template>
<script>
  import Icon from "../../Icon";
  import _abstract from "./_abstract";

  export default {
      extends: _abstract,
      components: { Icon },
      methods: {
          to(action) {
              return this.$route.path + '/' + this.id + '/' + action;
          }
      }
  }
</script>
