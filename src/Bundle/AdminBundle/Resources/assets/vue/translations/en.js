
import { messages, dateTimeFormats, i18n } from "../plugins/i18n";

// Set en part of messages.
messages.en = {
    general: {
        back: 'Back'
    },
    navigation: {
        content_types: {
            headline: 'Content Types'
        }
    },
    schema: {
        save: 'Save',
        add: 'Add schema file',
        compose: 'Create new schema file (filename without .graphql)',
        rename: 'Update filename (without .graphql)',
        delete: 'Do you really want to delete schema file "{filename}"?',
    },
    content: {
        list: {
            field: {
                fallback_warning: 'Missing list field type "{type}" for field "{id}".',
            },
            view: {
                fallback_warning: 'Missing view type "{viewType}" for view with id "{id}".',
            },
            actions: {
                create: 'Create',
                update: 'Update',
                delete: 'Delete',
                permanent_delete: 'Permanent delete',
                revert: 'Revert',
                recover: 'Recover',
                translate: 'Translate',
                toggle_deleted: 'Toggle deleted content'
            }
        },
        form: {
            field: {
                fallback_warning: 'Missing form field type "{type}" for field "{id}".',
            },
        },
        create: {
            headline: 'Create {name} content',
            actions: {
                submit: 'Save'
            },
            success: 'New "{name}" content was created.',
        },
        update: {
            headline: 'Update {name} content',
            actions: {
                submit: 'Save'
            },
            success: '"{name}" content was updated.',
        },
        delete: {
            headline: 'Move {name} content to trash',
            actions: {
                submit: 'Move to trash'
            },
            success: '"{name}" content was moved to trash.',
        },
        recover: {
            headline: 'Recover {name} content from trash',
            actions: {
                submit: 'Recover from trash'
            },
            success: '"{name}" content was recovered.',
        },
        permanent_delete: {
            headline: 'Remove {name}',
            actions: {
                submit: 'Permanently remove'
            },
            success: '"{name}" content was removed.',
        },
    }
};

// Set en part of dateTimeFormats.
dateTimeFormats.en = {
    full: {
        year: '2-digit', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', seconds: '2-digit', hour12: false
    }
};

// If we include this file, set the language to "en".
i18n.locale = 'en';
