
import Vue from 'vue';
import Cookies from 'js-cookie';
import gql from 'graphql-tag';

const COOKIE_NAME = 'UNITE_ADMIN_USER';

export const UserState = new Vue({
    data() {
        return {
            user: Cookies.getJSON(COOKIE_NAME) || {},
        }
    },
    created() {

        this.$on('login', (data, success, fail, fin) => {

            this.$apollo.mutate({
                context: {
                    headers: {
                        authorization: 'Basic ' + btoa(data.type + '/' + data.username + ':' + data.password)
                    }
                },
                mutation: gql`mutation {
                    unite {
                        generateLongLivingJWT
                    }
                }`,
            }).then((data) => {

                // Save token
                this.user = { token: data.data.unite.generateLongLivingJWT };

                // Get user information
                this.$apollo.query({
                    query: gql`query {
                        unite {
                            me {
                                id
                                username
                            }
                        }
                    }`
                }).then((data) => {
                    this.user.id = data.data.unite.me.id;
                    this.user.username = data.data.unite.me.username;
                }).then(success).catch(fail).finally(fin);
            }).catch(fail).catch(fin);
        });

        this.$on('logout', (data, success, fail, fin) => {
            this.user = {};
            success ? success() : null;
            fin ? fin() : null;
        });
    },
    watch: {
        user: {
            deep: true,
            handler(user) {
                Cookies.set(COOKIE_NAME, user);
            }
        }
    },
    computed: {
        isAuthenticated() {
            return !!this.user.token;
        },
        token() {
            return this.isAuthenticated ? this.user.token : null;
        }
    }
});
export default UserState;
