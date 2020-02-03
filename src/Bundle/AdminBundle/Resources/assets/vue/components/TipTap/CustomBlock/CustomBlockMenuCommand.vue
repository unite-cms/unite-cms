<script>
    import AbstractMenuCommand from '../Command/BlockMenuCommand';
    import { allowChildren } from "./CustomBlockNode";

    export default {
        name: "CustomBlockMenuCommand",
        extends: AbstractMenuCommand,
        computed: {

            adminView() {
                let views = Object.values(this.$unite.adminViews).filter((adminView) => {
                    return adminView.type === this.config.type.name;
                });
                return views.length > 0 ? views[0] : null;
            },

            icon() {
                return this.adminView && this.adminView.icon || 'square';
            },
            label() {
                return this.adminView ? this.adminView.name : this.config.type.name;
            },
            description() {
                if(!this.config.type.description) {
                    return '';
                }
                let parts = this.config.type.description.split("\n");
                return parts.length > 1 ? parts[1] : '';
            }
        },
        methods: {
            onClick() {

                let node = this.editor.schema.node(
                    'Unite' + this.config.type.name,
                    null,
                    allowChildren(this.config.type) ? [this.editor.schema.node('paragraph')] : null
                );

                this.editor.view.dispatch(
                    this.editor.state.tr.insert(
                        this.editor.state.selection.$anchor.pos,
                        node
                    )
                );
                this.$emit('selected');
            }
        }
    }

</script>

<style scoped>

</style>
