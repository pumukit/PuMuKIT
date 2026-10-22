import { createTranslator } from '@symfony/ux-translator';
import { messages, localeFallbacks } from '@pumukit/translations';

export const { trans } = createTranslator({ messages, localeFallbacks });
