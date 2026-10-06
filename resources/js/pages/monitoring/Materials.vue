<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useI18n } from '@/composables/useI18n';
import { materials as materialsRoute } from '@/routes/monitoring';
import { show } from '@/routes/monitoring/projects';
import MaterialCard from './MaterialCard.vue';
import Pagination from './Pagination.vue';
import { platforms } from './types';
import type { Material, Pagination as Page } from './types';
const props = defineProps<{
    project: { id: number; name: string; timezone: string };
    materials: Page<Material>;
    filters: { search?: string; platform?: string };
}>();
const { t } = useI18n();
const filters = ref({
    search: props.filters.search ?? '',
    platform: props.filters.platform ?? '',
});
const search = () =>
    router.get(materialsRoute.url(props.project.id), filters.value, {
        preserveState: true,
        replace: true,
    });
</script>
<template>
    <Head :title="t('monitoring.materials')" />
    <div class="mx-auto grid w-full max-w-5xl gap-6 p-4 sm:p-6">
        <div class="flex flex-wrap justify-between gap-3">
            <Heading
                :title="t('monitoring.materials')"
                :description="project.name"
            /><Button as-child variant="outline"
                ><Link :href="show(project.id)">{{
                    t('monitoring.backProject')
                }}</Link></Button
            >
        </div>
        <form class="flex flex-wrap gap-3" @submit.prevent="search">
            <Input
                v-model="filters.search"
                :aria-label="t('monitoring.search')"
                :placeholder="t('monitoring.searchMaterials')"
                class="min-w-48 flex-1"
            /><select
                v-model="filters.platform"
                :aria-label="t('monitoring.platformLabel')"
                class="rounded-md border border-input bg-background p-2"
            >
                <option value="">{{ t('monitoring.allPlatforms') }}</option>
                <option
                    v-for="platform in platforms"
                    :key="platform"
                    :value="platform"
                >
                    {{ t(`monitoring.platform.${platform}`) }}
                </option></select
            ><Button type="submit">{{ t('monitoring.search') }}</Button>
        </form>
        <p v-if="!materials.data.length" class="text-sm text-muted-foreground">
            {{ t('monitoring.noMaterials') }}
        </p>
        <MaterialCard
            v-for="item in materials.data"
            :key="item.id"
            :material="item"
            :timezone="project.timezone"
        /><Pagination :page="materials" />
    </div>
</template>
