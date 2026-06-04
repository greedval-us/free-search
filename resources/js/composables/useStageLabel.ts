import { computed, type ComputedRef, type Ref } from 'vue';

export const useStageLabel = <TStage extends string>(
    stage: Ref<TStage>,
    resolveLabel: (stage: TStage) => string
): ComputedRef<string> => computed(() => resolveLabel(stage.value));
