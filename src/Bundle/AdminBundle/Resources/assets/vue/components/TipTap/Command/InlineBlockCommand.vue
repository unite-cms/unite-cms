<template>
    <div style="line-height: 100%" v-if="!isList">
        <button class="uk-button uk-button-link" type="button">
            {{ activeLabel }}
            <icon name="chevron-down" />
        </button>
        <div uk-dropdown="mode: click">
            <ul class="uk-nav uk-dropdown-nav">
                <li :class="{ 'uk-active': isActive.paragraph() }" >
                    <a href="#" @click.prevent="toggleParagraph">
                        <p>Paragraph</p>
                    </a>
                </li>
                <li :class="{ 'uk-active': isActive.heading({ level }) }" v-for="level in allowedLevels">
                    <a href="#" @click.prevent="toggleHeadline(level)">
                        <component :is="'h' + level">Headline {{ level }}</component>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <span v-else></span>
</template>

<script>
    import Icon from "../../Icon";

    export default {
        name: "InlineBlockCommand",
        components: {Icon},
        props: {
            editor: {
                type: Object,
            },
            isActive: {
                type: Object,
                default: () => {},
            },
            config: {
                type: Object
            }
        },
        computed: {
            isList() {
                return this.isActive.bullet_list() || this.isActive.ordered_list();
            },
            allowedLevels() {
                return this.config.levels || [];
            },
            activeLabel() {
                if(this.isActive.paragraph()) {
                    return 'P';
                }

                let activeHeading = this.allowedLevels.filter((level) => {
                    return this.isActive.heading({ level })
                });

                if(activeHeading) {
                    return 'H' + activeHeading;
                }

                return '';
            }
        },
        methods: {
            toggleParagraph() { this.editor.commands.paragraph(); },
            toggleHeadline(level) { this.editor.commands.heading({ level }); }
        }
    }
</script>

<style scoped>

</style>