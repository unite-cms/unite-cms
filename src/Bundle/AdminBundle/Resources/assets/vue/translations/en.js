
import { messages, dateTimeFormats, numberFormats, i18n } from "../plugins/i18n";
import { en } from 'vuejs-datepicker/dist/locale'

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
    dashboard: {
        basic: {
            title: 'Welcome to unite cms{{#unite._version}} version {{unite._version}}{{/unite._version}}!'
        }
    },
    content: {
        list: {
            field: {
                fallback_warning: 'Missing list field type "{fieldType}" for field "{id}".',
            },
            view: {
                fallback_warning: 'Missing view type "{viewType}" for view with id "{id}".',
            },
            deleted: {
                active: 'Active',
                deleted: 'Deleted'
            },
            actions: {
                create: 'Create',
                update: 'Update',
                translate: 'Manage translations',
                delete: 'Delete',
                permanent_delete: 'Permanent delete',
                revert: 'Manage Revisions',
                recover: 'Recover',
                user_invite: 'Invite user to unite cms'
            },
            selection: {
                select: 'Select',
                confirm: 'Confirm selection of {count}'
            },
            empty_placeholder: "No content found for this view.",
            search: {
                placeholder: "Search...",
                placeholder_filter: "Advanced filter applied..."
            },
            filter: {
                title: 'Advanced filters',
                clear: 'Clear',
                cancel: 'Cancel',
                apply: 'Apply',
                field: "Field",
                operator: 'Operator',
                placeholder: "Enter/Select value...",
                checkbox_true: 'True',
                checkbox_false: 'False',
                checkbox_null: 'Empty',
                filter: "Filter",
                AND: 'AND',
                OR: 'OR'
            }
        },
        form: {
            field: {
                fallback_warning: 'Missing form field type "{fieldType}" for field "{id}".',
            },
        },
        create: {
            headline: 'Create "{contentTitle}"',
            actions: {
                submit: 'Save'
            },
            success: '"{contentTitle}" was created.',
            errors: '"{contentTitle}" could not be created. Please see violations below.',
        },
        update: {
            headline: 'Update "{contentTitle}"',
            actions: {
                submit: 'Save'
            },
            success: '"{contentTitle}" was updated.',
            errors: '"{contentTitle}" could not be updated. Please see violations below.',
        },
        delete: {
            headline: 'Move "{contentTitle}" to trash',
            message: 'Do you really want to move "{contentTitle}" to trash?',
            actions: {
                submit: 'Move to trash'
            },
            success: '"{contentTitle}" content was moved to trash.',
        },
        recover: {
            headline: 'Recover "{contentTitle}" from trash',
            message: 'Do you really want to recover "{contentTitle}" from trash?',
            actions: {
                submit: 'Recover from trash'
            },
            success: '"{contentTitle}" was recovered.',
        },
        revert: {
            headline: 'Revisions of "{contentTitle}"',
            success: '"{contentTitle}" was reverted to {version}. revision.',
            confirm: 'Do you really want to revert "{contentTitle}" to version {version}?',
            label: {
                current: 'Current'
            },
            header: {
                version: '#',
                operation: 'Operation',
                meta: 'Meta'
            },
            actions: {
                revert: 'Revert content to this version'
            }
        },
        translate: {
            headline: 'Translations of "{contentTitle}"',
            select_translation: 'Select a content to use as a translation for this content',
            header: {
                locale: 'Locale'
            },
            no_locale_warning: 'Locale of this content is empty. Please select a locale first to manage translations.'
        },
        permanent_delete: {
            headline: 'Remove "{contentTitle}"',
            message: 'Do you really want to permanently delete "{contentTitle}"? Be careful, you cannot undo this action!',
            actions: {
                submit: 'Permanently remove'
            },
            success: '"{contentTitle}" was removed.',
        },
        user_invite: {
            default_text: 'This is an invitation to unite cms. Please click the following link to accept the invitation',
            headline: 'Invite "{contentTitle}" to unite cms',
            actions: {
                submit: 'Send invitation E-Mail'
            },
            success: '"{contentTitle}" was invited to unite cms.',
            error: 'Could not invite "{contentTitle}" to unite cms. This is most likely because of an existing invitation for this user. Please wait some time and then try to invite this user again.',
        }
    },
    field: {
        reference: {
            missing_view_warning: 'No admin view for the referenced content of this field was found. Please add one to your schema!',
            missing_required_value: 'You cannot select content for this field at the moment, because at least one field that is required for this field, is empty.',
            modal: {
                headline: 'Select a content to reference'
            }
        },
        reference_of: {
            missing_view_warning: 'No admin view for the referenced content of this field was found. Please add one to your schema!',
            no_content_id: 'A reference to this field is only possible after creation.',
            modal: {
                headline: 'Referenced {name} of "{contentTitle}"'
            }
        },
        geoLocation: {
            placeholder: {
                stairs_number: 'Stairs',
                door_number: 'Door'
            },
            modal: {
                headline: '{display_name}'
            }
        },
        embedded: {
            missing_view_warning: 'No admin view for the embedded content of this field was found. Please add one to your schema!',
            confirm: {
                clear_union_selection: 'Do you really want to select another type? Your already entered vales for this field will be deleted.'
            }
        },
        sequence: {
            no_value_message: 'The value of this field will automatically be generated on create.'
        },
        checkbox: {
            description_true: '{label}: True',
            description_false: '{label}: False',
        },

        date: {
            picker: en,
            format: 'd MMMM yyyy',
            mondayFirst: 'false'
        }
    },
    network_error: {
        401: 'Username or password incorrect.',
        500: 'Could not connect to API. Maybe your GraphQL schema is not valid?',
    },

    login: {
        headline: 'Login',
        labels: {
            username: 'Username',
            password: 'Password'
        },
        actions: {
            submit: 'Login',
            reset_password: 'Forgot password?'
        }
    },

    reset_password: {
        headline: 'Reset password',
        text: 'Please enter your username and we will send a password reset token to your E-Mail address.',
        labels: {
            username: 'Username',
        },
        actions: {
            submit: 'Reset password',
            login: 'Login'
        },
        error: 'Could not reset password. If you have already requested a password reset, please wait some time before requesting again.',
        success: 'Successfully requested a password reset token. Please check your E-Mails.'
    },

    email_confirm: {
        invite: {
            headline: 'Accept invitation',
            text: 'You have been invited to create an unite cms account as "{type}". Please choose a password for your new account.',
            labels: {
                username: 'Username (you cannot change this)',
                password: 'Password',
                password_repeat: 'Repeat password',
            },
            actions: {
                submit: 'Create account'
            },
            error: 'Could not create account. Please contact an administrator.',
            success: 'Successfully created account! You will be automatically logged in...',
            token_expired: 'Invitation token expired. Please contact an administrator and ask for a new invitation.'
        },

        reset_password: {
            headline: 'Reset password',
            text: 'You have requested a password reset for your "{type}" user. Please select a new password for your account.',
            labels: {
                username: 'Username (you cannot change this)',
                password: 'New password',
                password_repeat: 'Repeat new password',
            },
            actions: {
                submit: 'Save new password'
            },
            error: 'Could not save new password. Please contact an administrator.',
            success: 'Successfully saved the new password! You will be automatically logged in...',
            token_expired: 'Password reset token expired. Please request a new password reset token.'
        }
    }
};

// Set en part of dateTimeFormats.
dateTimeFormats.en = {
    full: {
        year: '2-digit', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', seconds: '2-digit', hour12: false
    },
    date: {
        year: '2-digit', month: '2-digit', day: '2-digit'
    }
};

// set en part of numberFormats.
numberFormats.en = {
    currency: {
        style: 'currency', currency: 'USD'
    },
    USD: {
        style: 'currency', currency: 'USD'
    },
    EUR: {
        style: 'currency', currency: 'EUR'
    },
    decimal: {
        style: 'decimal'
    },
    percent: {
        style: 'percent'
    }
};

// If we include this file, set the language to "en".
i18n.locale = 'en';
