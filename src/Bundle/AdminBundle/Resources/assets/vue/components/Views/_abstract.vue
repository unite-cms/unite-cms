<script>
    export default {
        data() {
            return {
                selection: []
            };
        },
        props: {
            view: Object,
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
