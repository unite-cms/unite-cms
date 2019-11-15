import Vue from 'vue';
import VueApollo from 'vue-apollo';
import ApolloClient from 'apollo-boost';
import User from "../state/User";
import Alerts from "../state/Alerts";
import { Unite } from "./unite";
import { InMemoryCache } from 'apollo-cache-inmemory';

Vue.use(VueApollo);

const cache = new InMemoryCache({ fragmentMatcher: Unite.fragmentMatcher });
const apolloClient = new ApolloClient({
    cache,
    uri: location.origin + UNITE_ADMIN_CONFIG.baseurl,
    request: (operation) => {
        if(User.token) {
            operation.setContext({
                headers: {
                    authorization: `Bearer ${ User.token }`
                }
            })
        }
    }
});


export const apolloProvider = new VueApollo({
    defaultClient: apolloClient,
    watchLoading: () => { Alerts.$emit('clear'); },
    errorHandler: Alerts.apolloErrorHandler
});
export default apolloProvider;
