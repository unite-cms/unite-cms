import Vue from 'vue';
import VueApollo from 'vue-apollo';
import ApolloClient from 'apollo-boost';

import UserState from '../state/User';

Vue.use(VueApollo);

const apolloClient = new ApolloClient({
    uri: location.origin,
    request: (operation) => {
        if(UserState.token) {
            operation.setContext({
                headers: {
                    authorization: `Bearer ${UserState.token}`
                }
            })
        }
    }
});

export const apolloProvider = new VueApollo({ defaultClient: apolloClient });
export default apolloProvider;
UserState.$apolloProvider = apolloProvider;
