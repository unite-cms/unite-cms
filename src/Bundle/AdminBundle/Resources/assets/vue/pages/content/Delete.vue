<script>
    import _contentDetail from "./_contentDetail";
    import gql from 'graphql-tag';

    export default {
        extends: _contentDetail,
        computed: {
            labelKey() {
                return 'delete';
            },
            mutation() {
                return {
                    mutation: gql`mutation($id: ID!, $persist: Boolean!) {
                        delete${ this.view.type }(id: $id, persist:$persist) {
                            id
                        }
                    }`,
                    variables: {
                        id: this.$route.params.id,
                        persist: true,
                    }
                }
            },
            backLinkQuery() {
                return {
                    updated: this.$route.params.id,
                    deleted: true
                };
            },
        }
    }
</script>
