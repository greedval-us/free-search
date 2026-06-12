import { computed } from 'vue';
import type { ComputedRef, Ref } from 'vue';

export const useStageLabel = <TStage extends string>(
    stage: Ref<TStage>,
    resolveLabel: (stage: TStage) => string
): ComputedRef<string> => computed(() => resolveLabel(stage.value));
