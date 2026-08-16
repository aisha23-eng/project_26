<script module lang="ts">
    import { alerts } from '@/routes';

    export const layout = {
        breadcrumbs: [{ title: 'التنبيهات', href: alerts() }],
    };
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
    import { Empty, EmptyDescription, EmptyTitle } from '@/components/ui/empty';
    import { formatCurrency } from '@/lib/currency';
    import { formatDate } from '@/lib/date';
    import { toUrl } from '@/lib/utils';
    import { budgets, expenses } from '@/routes';
    import AlertTriangle from 'lucide-svelte/icons/alert-triangle';
    import CalendarClock from 'lucide-svelte/icons/calendar-clock';
    import CircleDot from 'lucide-svelte/icons/circle-dot';
    import TrendingUp from 'lucide-svelte/icons/trending-up';
    import type { Component, SvelteComponent } from 'svelte';

    type BudgetCategory = {
        id: number;
        name: string;
        color: string | null;
        icon: string | null;
    };

    type BudgetAlert = {
        id: number;
        type: 'over_budget' | 'near_budget';
        category: BudgetCategory | null;
        spent: number;
        amount: number;
        remaining: number;
        percentage: number;
        currency: string;
    };

    type BillReminder = {
        id: number;
        description: string;
        vendor: string | null;
        amount: number;
        currency: string;
        due_date: string;
        due_in_days: number;
        overdue: boolean;
        category: { name: string; color: string | null } | null;
    };

    let {
        budget_alerts,
        bill_reminders,
        count,
        currency,
    }: {
        budget_alerts: BudgetAlert[];
        bill_reminders: BillReminder[];
        count: number;
        currency: string;
    } = $props();

    type CategoryIcon =
        | Component<{ class?: string }>
        | (new (...args: any[]) => SvelteComponent<{ class?: string }>);

    const ICONS: Record<string, CategoryIcon> = {
        code: CircleDot,
        settings: CircleDot,
        'utensils-crossed': CircleDot,
        plane: CircleDot,
        building: CircleDot,
        users: CircleDot,
        megaphone: CircleDot,
        zap: CircleDot,
        wrench: CircleDot,
        shield: CircleDot,
        briefcase: CircleDot,
        'shopping-cart': CircleDot,
        'trending-up': CircleDot,
        'more-horizontal': CircleDot,
    };
    const DEFAULT_ICON: CategoryIcon = CircleDot;

    function resolveIcon(icon: string | null): CategoryIcon {
        return (icon && ICONS[icon]) || DEFAULT_ICON;
    }
</script>

<AppHead title="التنبيهات" />

<div class="flex h-full flex-1 flex-col gap-5 p-4 md:p-6" dir="rtl">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">التنبيهات</h1>
            <p class="text-sm text-muted-foreground">تنبيهات الميزانيات وتذكيرات الفواتير</p>
        </div>
        {#if count > 0}
            <Badge variant="destructive">{count} تنبيه نشط</Badge>
        {:else}
            <Badge variant="outline" class="text-success">لا توجد تنبيهات</Badge>
        {/if}
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <Card class="border border-border bg-card shadow-sm">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <TrendingUp class="size-4 text-primary" />
                    تنبيهات الميزانيات
                </CardTitle>
                <CardDescription>فئات اقتربت أو تجاوزت سقف ميزانيتها هذا الشهر</CardDescription>
            </CardHeader>
            <CardContent>
                {#if budget_alerts.length === 0}
                    <Empty>
                        <EmptyTitle>لا توجد تنبيهات ميزانية</EmptyTitle>
                        <EmptyDescription>كل ميزانياتك هذا الشهر ضمن الحدود.</EmptyDescription>
                    </Empty>
                {:else}
                    <div class="space-y-3">
                        {#each budget_alerts as alert}
                            {@const Icon = resolveIcon(alert.category?.icon ?? null)}
                            <div class="flex items-center justify-between gap-3 rounded-lg border border-border bg-card p-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div
                                        class="flex size-9 shrink-0 items-center justify-center rounded-lg"
                                        style:background-color={alert.category?.color ?? '#6B1A2A'}
                                    >
                                        <Icon class="size-4 text-white" />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="truncate text-sm font-medium">{alert.category?.name ?? 'بدون فئة'}</p>
                                            {#if alert.type === 'over_budget'}
                                                <Badge variant="destructive" class="shrink-0 text-[10px]">تجاوز</Badge>
                                            {:else}
                                                <Badge variant="outline" class="shrink-0 border-amber-500 text-[10px] text-amber-600 dark:text-amber-400">
                                                    {alert.percentage}%
                                                </Badge>
                                            {/if}
                                        </div>
                                        <p class="mt-0.5 text-xs text-muted-foreground">
                                            {formatCurrency(alert.spent, alert.currency)} من {formatCurrency(alert.amount, alert.currency)}
                                            &middot; متبقي {formatCurrency(alert.remaining, alert.currency)}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        {/each}
                    </div>
                {/if}
            </CardContent>
        </Card>

        <Card class="border border-border bg-card shadow-sm">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <CalendarClock class="size-4 text-primary" />
                    تذكيرات الفواتير
                </CardTitle>
                <CardDescription>مصروفات متكررة مستحقة خلال الأيام السبعة القادمة</CardDescription>
            </CardHeader>
            <CardContent>
                {#if bill_reminders.length === 0}
                    <Empty>
                        <EmptyTitle>لا توجد تذكيرات</EmptyTitle>
                        <EmptyDescription>لا توجد فواتير متكررة مستحقة قريباً.</EmptyDescription>
                    </Empty>
                {:else}
                    <div class="space-y-3">
                        {#each bill_reminders as bill}
                            <div class="flex items-center justify-between gap-3 rounded-lg border border-border bg-card p-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                        <AlertTriangle
                                            class="size-4 {bill.overdue ? 'text-destructive' : 'text-amber-500'}"
                                        />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium">{bill.description || bill.vendor || 'فاتورة متكررة'}</p>
                                        <p class="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground">
                                            {#if bill.category}
                                                <span class="flex items-center gap-1.5">
                                                    <span class="size-2 rounded-full" style:background-color={bill.category.color ?? '#9ca3af'}></span>
                                                    {bill.category.name}
                                                </span>
                                            {/if}
                                            <span>&middot; {formatDate(bill.due_date)}</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-1">
                                    <span class="text-sm font-semibold">{formatCurrency(bill.amount, bill.currency)}</span>
                                    {#if bill.overdue}
                                        <Badge variant="destructive" class="text-[10px]">متأخرة</Badge>
                                    {:else}
                                        <Badge variant="outline" class="text-[10px] text-muted-foreground">
                                            بعد {bill.due_in_days} يوم
                                        </Badge>
                                    {/if}
                                </div>
                            </div>
                        {/each}
                    </div>
                {/if}
            </CardContent>
        </Card>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <Button variant="outline" asChild>
            <a href={toUrl(budgets())}>إدارة الميزانيات</a>
        </Button>
        <Button variant="outline" asChild>
            <a href={toUrl(expenses())}>عرض المصروفات</a>
        </Button>
    </div>
</div>
