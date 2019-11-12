
export function graphQLLanguageProvider (monaco) {

    monaco.languages.registerCompletionItemProvider('graphql', {

        // TODO: Provide graphql and unite cms auto completion.
        provideCompletionItems: () => {

            let suggestions = [
                {
                    label: 'implements UniteContent',
                    kind: monaco.languages.CompletionItemKind.Function,
                    insertText: "implements UniteContent {\n\tid: ID!\n}",
                    insertTextRules: monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet
                },
            ];

            return {suggestions};
        }
    });
}

export const editorOptions = {
    automaticLayout: true,
    language: 'graphql',
    theme: 'vs-dark',
    fontSize: 14,
    model: null,
    minimap: {
        enabled: false,
    },
    rulers: [80],
};
