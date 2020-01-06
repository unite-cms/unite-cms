<script>
    import Integer from "../Fields/Form/Integer";

    export default {
        data() {
            return {
                selection: (this.initialSelection || []).slice(0)
            };
        },
        props: {
            view: Object,
            orderBy: Array,
            filter: Object,
            title: String,
            offset: {
                type: Number,
                default: 0,
            },
            initialCreateData: {
                type: Object,
                default() { return {} }
            },
            embedded: {
                type: Boolean,
                default: false,
            },
            pagination: {
                type: Boolean,
                default: true,
            },
            highlightRow: String,
            deleted: Boolean,
            initialSelection: Array,
            select: String,
        },
        methods: {
            is_granted(attribute) {
                return this.view.permissions[attribute] || false;
            },
            to(action) {
                return this.$route.path + '/' + action;
            },
            selectRow(id) {
                if(this.select === 'MULTIPLE') {
                    if(this.isSelected(id)) {
                        this.selection = this.selection.filter((sel) => { return sel !== id; });
                    } else {
                        this.selection.push(id);
                    }
                } else {
                    this.$emit('select', id);
                }
            },
            isSelected(id) {
                return this.selection.indexOf(id) >= 0;
            },
            confirmSelection() {
                this.$emit('select', this.selection);
            }
        },
    }
</script>
