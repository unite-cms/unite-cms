import Vue from 'vue';
import VueApollo from 'vue-apollo';
import ApolloClient from 'apollo-boost';
import User from "../state/User";
import Alerts from "../state/Alerts";

Vue.use(VueApollo);

const apolloClient = new ApolloClient({
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
