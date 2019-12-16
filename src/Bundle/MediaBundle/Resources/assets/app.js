
import { Unite } from '@unite/admin/Resources/assets/vue/plugins/unite';

import MediaFileList from "./vue/components/Fields/List/MediaFile";
import MediaFileForm from "./vue/components/Fields/Form/MediaFile";

Unite.$emit('registerListFieldType', 'mediaFile', MediaFileList);
Unite.$emit('registerFormFieldType', 'mediaFile', MediaFileForm);
