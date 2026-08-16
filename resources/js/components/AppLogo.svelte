<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';

    const name = $derived(page.props.name);
</script>

<div class="grid text-sm">
    <span class="mb-0.5 truncate leading-tight font-semibold">{name}</span>
</div>
<div
    class="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
>
    <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
</div>
