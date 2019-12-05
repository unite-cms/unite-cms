
import Vue from 'vue';
import Cookies from 'js-cookie';
import gql from 'graphql-tag';

const COOKIE_NAME = 'UNITE_ADMIN_USER';

export const User = new Vue({
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
                        authorization: 'Basic ' + btoa(data.username + ':' + data.password)
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
                this.$apollo.query({ query: gql`query { unite { me { __typename, id, username } }}` }).then((data) => {
                    this.user = {
                        token: this.user.token,
                        id: data.data.unite.me.id,
                        username: data.data.unite.me.username,
                        type: data.data.unite.me.__typename,
                    };
                }).then(success).catch(fail).finally(fin);
            }).catch((error) => { fail(error); fin(); });
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
        hasUserInformation() {
            return !!this.user.token && !!this.user.username;
        },
        token() {
            return this.isAuthenticated ? this.user.token : null;
        }
    }
});
export default User;
