<template>
    <ul class="uk-iconnav uk-flex-right">
        <li v-if="!deleted && show_action('user_invite') && is_granted('user_invite')"><router-link uk-tooltip :target="embedded ? '_blank' : '_self'" :to="to('user_invite')" :title="$t('content.list.actions.user_invite')" class="uk-text-success"><icon name="send" /></router-link></li>
        <li v-if="!deleted && show_action('update') && is_granted('update')"><router-link uk-tooltip :target="embedded ? '_blank' : '_self'" :to="to('update')" :title="$t('content.list.actions.update')"><icon name="edit-2" /></router-link></li>
        <li v-if="view.translatable && !deleted && show_action('translate') && is_granted('update')"><router-link uk-tooltip :target="embedded ? '_blank' : '_self'" :to="to('translate')" :title="$t('content.list.actions.translate')"><icon name="globe" /></router-link></li>
        <li v-if="!deleted && show_action('revert') && is_granted('update')"><router-link uk-tooltip :target="embedded ? '_blank' : '_self'" :to="to('revert')" :title="$t('content.list.actions.revert')"><icon name="rotate-ccw" /></router-link></li>
        <li v-if="!deleted && show_action('delete') && is_granted('delete')"><router-link uk-tooltip :target="embedded ? '_blank' : '_self'" :to="to('delete')" :title="$t('content.list.actions.delete')" class="uk-text-danger"><icon name="trash-2" /></router-link></li>

        <li v-if="deleted && show_action('recover') && is_granted('update')"><router-link uk-tooltip :target="embedded ? '_blank' : '_self'" :to="to('recover')" :title="$t('content.list.actions.recover')" class="uk-text-success"><icon name="rewind" /></router-link></li>
        <li v-if="deleted && show_action('permanent_delete') && is_granted('permanent_delete')"><router-link uk-tooltip :target="embedded ? '_blank' : '_self'" :to="to('permanent_delete')" :title="$t('content.list.actions.permanent_delete')" class="uk-text-danger"><icon name="x-circle" /></router-link></li>
    </ul>
</template>
<script>
    import Icon from "../../Icon";
    import _abstract from "./_abstract";

    export default {
        extends: _abstract,
        components: { Icon },
    }
</script>
<style scoped lang="scss">
    .uk-iconnav {
        flex-wrap: nowrap;

        li {
            a {
                width: 20px;
            }
        }
    }
</style>