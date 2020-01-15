<template>
    <div class="uk-card uk-card-default uk-card-body uk-margin uk-padding uk-text-center">
        <div class="uk-placeholder">
            <icon name="maximize" :width="128" :height="128" style="opacity: 0.125" />
            <p class="uk-margin">{{ $t('content.list.empty_placeholder') }}</p>
            <p v-if="create">
                <router-link :to="createRoute" class="uk-button uk-button-default uk-button-small"><icon name="plus" /> {{ $t('content.list.actions.create') }}</router-link>
            </p>
        </div>
    </div>
</template>

<script>
    import Icon from "../Icon";

    export default {
        components: { Icon },
        props: {
            create: Boolean,
            createRoute: String,
        },
    }
</script>
