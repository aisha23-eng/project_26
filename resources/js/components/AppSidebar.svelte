<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import BarChart3 from 'lucide-svelte/icons/bar-chart-3';
    import Bell from 'lucide-svelte/icons/bell';
    import Bot from 'lucide-svelte/icons/bot';
    import LayoutGrid from 'lucide-svelte/icons/layout-grid';
    import Monitor from 'lucide-svelte/icons/monitor';
    import Moon from 'lucide-svelte/icons/moon';
    import Receipt from 'lucide-svelte/icons/receipt';
    import Settings from 'lucide-svelte/icons/settings';
    import Sun from 'lucide-svelte/icons/sun';
    import Tags from 'lucide-svelte/icons/tags';
    import TrendingUp from 'lucide-svelte/icons/trending-up';
    import WalletCards from 'lucide-svelte/icons/wallet-cards';
    import type { Snippet } from 'svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import NavFooter from '@/components/NavFooter.svelte';
    import NavMain from '@/components/NavMain.svelte';
    import NavUser from '@/components/NavUser.svelte';
    import {
        Sidebar,
        SidebarContent,
        SidebarFooter,
        SidebarHeader,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
        SidebarGroup,
        SidebarGroupContent,
    } from '@/components/ui/sidebar';
    import { themeState } from '@/lib/theme.svelte';
    import { toUrl } from '@/lib/utils';
    import { dashboard, expenses, income, assistant, reports, categories, budgets, alerts } from '@/routes';
    import { edit as editProfile } from '@/routes/profile';
    import type { NavItem, Appearance } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const { appearance, updateAppearance } = themeState();

    const brandName = $derived(page.props.name);

    const mainNavItems: NavItem[] = [
        { title: 'لوحة التحكم', href: dashboard(), icon: LayoutGrid },
        { title: 'المصروفات', href: expenses(), icon: Receipt },
        { title: 'الدخل', href: income(), icon: TrendingUp },
        { title: 'التقارير', href: reports(), icon: BarChart3 },
        { title: 'الفئات', href: categories(), icon: Tags },
        { title: 'المساعد الذكي', href: assistant(), icon: Bot },
        { title: 'الميزانيات', href: budgets(), icon: WalletCards },
        { title: 'التنبيهات', href: alerts(), icon: Bell },
        { title: 'الإعدادات', href: editProfile(), icon: Settings },
    ];

    const footerNavItems: NavItem[] = [
        { title: 'مركز المساعدة', href: '/docs' },
    ];

    const themeOptions: { value: Appearance; icon: typeof Sun }[] = [
        { value: 'light', icon: Sun },
        { value: 'dark', icon: Moon },
        { value: 'system', icon: Monitor },
    ];
</script>

<Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
        <SidebarMenu>
            <SidebarMenuItem>
<SidebarMenuButton asChild class="px-3 py-1">
    {#snippet children(props)}
        <Link
            href={toUrl(dashboard())}
            class="relative flex w-full items-center"
        >
            <span class="mx-auto text-sm font-semibold">{brandName}</span>
            <div class="absolute right-3 top-1/2 flex size-8 -translate-y-1/2 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                <AppLogoIcon class="size-5 fill-current text-white dark:text-black" />
            </div>
        </Link>
    {/snippet}
</SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
        <NavMain items={mainNavItems} />
        <SidebarGroup class="px-3 py-2 mt-auto">
            <SidebarGroupContent>
                <div class="flex items-center justify-center gap-1 rounded-lg bg-sidebar-accent/50 p-1">
                    {#each themeOptions as opt (opt.value)}
                        <button
                            onclick={() => updateAppearance(opt.value)}
                            class="flex h-10 flex-1 items-center justify-center rounded-md transition-colors {appearance.value === opt.value ? 'bg-sidebar-primary text-sidebar-primary-foreground shadow-sm' : 'text-sidebar-foreground/60 hover:text-sidebar-foreground hover:bg-sidebar-accent'}"
                            title={opt.value === 'light' ? 'فاتح' : opt.value === 'dark' ? 'داكن' : 'النظام'}
                        >
                            <opt.icon class="size-4" />
                        </button>
                    {/each}
                </div>
            </SidebarGroupContent>
        </SidebarGroup>
    </SidebarContent>

    <SidebarFooter>
        <NavFooter items={footerNavItems} />
        <NavUser />
    </SidebarFooter>
</Sidebar>
{@render children?.()}